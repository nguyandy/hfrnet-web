#!/usr/bin/env python3
"""
This script processes radial files (.ruv) from S3 for metrics calculation,
tracking processing state in MySQL to ensure idempotency and prevent duplicate processing.
"""
import os
import sys
import logging
import argparse
from dotenv import load_dotenv

import boto3
from botocore.exceptions import ClientError
import s3fs
import pymysql.cursors

from streamingmetric import get_config_options, process_single_file
from process_utils import (
    DEFAULT_RECENT_HOURS,
    FileProcessingTracker,
    ProcessingStats,
    setup_logging,
    validate_environment,
    validate_metrics_script,
    health_check_s3,
    generate_month_prefixes,
    get_recent_month_prefixes,
    filter_files_by_time,
    should_process_month,
    extract_site_code,
)

# Load environment variables
load_dotenv()


def process_file(s3_key, s3_obj, bucket, tracker, stats, 
                 s3fs_client, metrics_db_cursor, metrics_db_conn, 
                 streaming_config, basemap_cache, dry_run=False):
    """Process a single file through the metrics script.
    
    :param s3_key: S3 object key
    :param s3_obj: S3 object metadata
    :param bucket: S3 bucket name
    :param tracker: FileProcessingTracker instance
    :param stats: ProcessingStats instance
    :param s3fs_client: s3fs.S3FileSystem instance for file access
    :param metrics_db_cursor: pymysql cursor for metrics database
    :param metrics_db_conn: pymysql connection for metrics database
    :param streaming_config: Config dict from streamingmetric.get_config_options()
    :param basemap_cache: Dict to cache Basemap instances by (lon, lat)
    :param dry_run: If True, don't actually process
    :return: True if successful, False otherwise
    """
    filename = os.path.basename(s3_key)
    s3_etag = s3_obj.get("ETag", "").strip('"')
    s3_last_modified = s3_obj.get("LastModified")
    site_code = extract_site_code(s3_key)
    
    stats.total_files += 1
    
    # Check if already processed or skipped
    status = tracker.check_file_processed(s3_key, s3_etag)
    if status:
        if status == 'success':
            logging.debug(f"- Already successfully processed: {filename}")
        elif status == 'skipped':
            logging.debug(f"- Previously marked as invalid (skipping): {filename}")
        stats.already_processed += 1
        return True
    
    if dry_run:
        logging.info(f"- [DRY RUN] Would process: s3://{bucket}/{s3_key}")
        stats.processed_success += 1
        return True
    
    # Mark as processing
    if not tracker.mark_file_processing(
        bucket, s3_key, s3_etag, s3_last_modified,
        site_code, filename
    ):
        logging.error(f"Failed to mark file as processing: {filename}")
        stats.skipped += 1
        return False
    
    logging.info(f"Processing s3://{bucket}/{s3_key}")
    
    try:
        success, error_msg = process_single_file(
            filename=filename,
            s3=s3fs_client,
            bucket=bucket,
            metrics_db_cursor=metrics_db_cursor,
            metrics_db_conn=metrics_db_conn,
            config=streaming_config,
            s3_last_modified=s3_last_modified,
            basemap_cache=basemap_cache
        )
        
        if success:
            tracker.mark_file_success(s3_key, s3_etag)
            stats.processed_success += 1
            logging.debug(f"- Successfully processed {filename}")
            return True
        else:
            # Check if this is an invalid/corrupted file that should be skipped
            skip_patterns = [
                "No radial data found",
                "no radial data",
                "corrupted file",
                "invalid file format",
                "ignored for site",
                "Error reading file",
                "File is empty",
                "bad data",
            ]
            
            error_msg_str = error_msg or ""
            should_skip = any(pattern.lower() in error_msg_str.lower() for pattern in skip_patterns)
            
            if should_skip:
                logging.info(f"- SKIPPING invalid file {filename}: {error_msg}")
                tracker.mark_file_skipped(s3_key, s3_etag, error_msg, "InvalidFile")
                stats.skipped += 1
            else:
                logging.error(f"- ERROR processing {filename}: {error_msg}")
                tracker.mark_file_failed(s3_key, s3_etag, error_msg, "ProcessingError")
                stats.processed_failed += 1
            return False
        
    except Exception as e:
        error_msg = str(e)
        error_type = type(e).__name__
        logging.error(f"EXCEPTION processing {filename}: {error_type} - {error_msg}")
        tracker.mark_file_failed(s3_key, s3_etag, error_msg, error_type)
        stats.processed_failed += 1
        return False


def main():
    """Main execution function."""
    parser = argparse.ArgumentParser(
        description='Process radial files for metrics calculation from S3 with database tracking',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Environment Variables Required:
  AWS_ACCESS_KEY_ID              AWS access key
  AWS_SECRET_ACCESS_KEY          AWS secret key
  AWS_SESSION_TOKEN              AWS session token (optional)
  BUCKET_NAME                    S3 bucket name
  METRICS_SCRIPT_PATH            Path to streamingmetric.py script
  DB_USER                        Database username
  DB_PASSWD                      Database password
  DB_HOST                        Database host
  DB_PORT                        Database port
  FILEPROCESSING_DB_DATABASE     Database name (default: fileprocessing)
  LOG_FILE_PATH                  Log file path (optional)
        """
    )
    
    parser.add_argument('--months', type=int, metavar='N',
                        help='Number of months to process including current month (1=current only, 3=current+2 previous)')
    parser.add_argument('--month', type=str, metavar='YYYY-MM',
                        help='Process a specific month (e.g., 2025-09)')
    parser.add_argument('--all', action='store_true',
                        help='Process all available months and all files for each site')
    parser.add_argument('--dry-run', action='store_true',
                        help='Show what would be processed without actually processing')
    
    args = parser.parse_args()
    
    # Validate environment and get configuration
    try:
        config = validate_environment('process_metrics.log')
    except RuntimeError as e:
        print(f"Configuration error: {e}", file=sys.stderr)
        return 1
    
    # Setup logging
    setup_logging(config['log_file_path'])
    
    logging.info("=" * 70)
    logging.info("METRIC FILE PROCESSOR")
    logging.info("=" * 70)
    
    if args.dry_run:
        logging.info("DRY RUN MODE - No files will be processed")
    
    # Determine which months to process
    if args.all:
        target_months = "all"
        process_all_files = True
        logging.info("Processing all available months and all files")
    elif args.month is not None:
        target_months = [args.month]
        process_all_files = True
        logging.info(f"Processing specific month: {args.month}")
    elif args.months is not None:
        target_months = generate_month_prefixes(args.months)
        process_all_files = True
        logging.info(f"Processing {len(target_months)} months: {', '.join(target_months)}")
    else:
        target_months = get_recent_month_prefixes(DEFAULT_RECENT_HOURS)
        process_all_files = False
        logging.info(f"Processing files from the last {DEFAULT_RECENT_HOURS} hours (months: {', '.join(target_months)})")
    
    # Validate metrics script
    if not validate_metrics_script(config['metrics_script']):
        logging.error("Metrics script validation failed")
        return 1
    
    # Initialize database tracker
    logging.info("Initializing database tracker...")
    tracker = FileProcessingTracker(config['db_config'], 'metric_file_processing')
    logging.info("Attempting database connection...")
    if not tracker.connect():
        logging.error("Failed to connect to database")
        return 1
    
    logging.info("Running database health check...")
    if not tracker.health_check():
        logging.error("Database health check failed")
        tracker.close()
        return 1
    
    logging.info("Database connection established and healthy")
    
    # Initialize S3 client
    try:
        s3 = boto3.client(
            "s3",
            aws_access_key_id=config['aws_key'],
            aws_secret_access_key=config['aws_secret'],
            aws_session_token=config['aws_token'],
        )
    except Exception as e:
        logging.error(f"Failed to create S3 client: {e}")
        tracker.close()
        return 1
    
    # S3 health check
    if not health_check_s3(s3, config['bucket']):
        logging.error("S3 health check failed")
        tracker.close()
        return 1
    
    # Create s3fs filesystem for direct file access (reused across all files)
    s3fs_client = s3fs.S3FileSystem(anon=False)
    
    # Load streamingmetric config once
    streaming_config = get_config_options()
    
    # Connect to metrics database (separate from tracker DB)
    try:
        metrics_db_conn = pymysql.connect(
            host=streaming_config['db_host'],
            user=config['db_config']['user'],
            password=config['db_config']['password'],
            database=streaming_config['db'],
            port=streaming_config['db_port'],
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        metrics_db_cursor = metrics_db_conn.cursor()
    except pymysql.Error as e:
        logging.error(f"Failed to connect to metrics database: {e}")
        tracker.close()
        return 1
    
    # Basemap cache (keyed by lon,lat)
    basemap_cache = {}
    
    # Initialize statistics
    stats = ProcessingStats()
    
    # Process files from radials/ prefix
    prefix_root = "radials/"
    paginator = s3.get_paginator("list_objects_v2")
    
    try:
        pages = paginator.paginate(Bucket=config['bucket'], Prefix=prefix_root, Delimiter="/")
    except ClientError as e:
        logging.error(f"Error listing S3 bucket {config['bucket']}/{prefix_root}: {e}")
        tracker.close()
        return 1
    
    logging.info(f"Starting file discovery in s3://{config['bucket']}/{prefix_root}")
    
    try:
        for page in pages:
            for site in page.get("CommonPrefixes", []):
                site_prefix = site["Prefix"]
                site_code = extract_site_code(site_prefix)
                logging.debug(f"Processing site: {site_code}")
                
                # List year-month under this site
                try:
                    mp = s3.get_paginator("list_objects_v2").paginate(
                        Bucket=config['bucket'], Prefix=site_prefix, Delimiter="/"
                    )
                except ClientError as e:
                    logging.error(f"Error listing months for {site_prefix}: {e}")
                    continue
                
                for mpage in mp:
                    for month in mpage.get("CommonPrefixes", []):
                        month_prefix = month["Prefix"]
                        
                        # Check if this month should be processed
                        if not should_process_month(month_prefix, target_months):
                            logging.debug(f"Skipping month: {month_prefix}")
                            continue
                        
                        logging.info(f"Processing month: {month_prefix}")
                        
                        # List all files under this month
                        try:
                            fp = s3.get_paginator("list_objects_v2").paginate(
                                Bucket=config['bucket'], Prefix=month_prefix
                            )
                        except ClientError as e:
                            logging.error(f"Error listing files for {month_prefix}: {e}")
                            continue
                        
                        # Collect all .ruv files for this site/month
                        all_files = []
                        for fpage in fp:
                            for obj in fpage.get("Contents", []):
                                key = obj["Key"]
                                filename = os.path.basename(key)
                                if filename.endswith(".ruv"):
                                    all_files.append(obj)
                        
                        # Determine which files to process
                        if process_all_files:
                            files_to_process = all_files
                            logging.info(f"Processing {len(files_to_process)} files")
                        else:
                            files_to_process = filter_files_by_time(all_files, hours=DEFAULT_RECENT_HOURS)
                            logging.info(f"- Found {len(files_to_process)} files from last {DEFAULT_RECENT_HOURS} hours")
                        
                        # Process the selected files
                        for obj in files_to_process:
                            key = obj["Key"]
                            process_file(
                                key, obj,
                                config['bucket'],
                                tracker,
                                stats,
                                s3fs_client,
                                metrics_db_cursor,
                                metrics_db_conn,
                                streaming_config,
                                basemap_cache,
                                dry_run=args.dry_run
                            )
    
    except KeyboardInterrupt:
        logging.warning("Processing interrupted by user")
        stats.print_summary()
        metrics_db_conn.close()
        tracker.close()
        return 130
    
    except Exception as e:
        logging.error(f"Unexpected error during processing: {e}", exc_info=True)
        stats.print_summary()
        metrics_db_conn.close()
        tracker.close()
        return 1
    
    # Print summary
    stats.print_summary()
    
    # Cleanup
    metrics_db_conn.close()
    tracker.close()
    
    # Return non-zero exit code if there were failures
    if stats.processed_failed > 0:
        logging.warning(f"Completed with {stats.processed_failed} failures")
        return 1
    
    logging.info("Processing completed successfully")
    return 0


if __name__ == "__main__":
    sys.exit(main())



