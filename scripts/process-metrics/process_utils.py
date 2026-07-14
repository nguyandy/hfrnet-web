#!/usr/bin/env python3
"""
Shared utilities for processing metric files from S3.
"""
import os
import sys
import logging
from datetime import datetime, timedelta, timezone
from logging.handlers import RotatingFileHandler

import pymysql
from botocore.exceptions import ClientError, BotoCoreError

# Default number of hours to look back for recent files
DEFAULT_RECENT_HOURS = 2


class FileProcessingTracker:
    """Manages database tracking of file processing status."""
    
    def __init__(self, db_config, table_name):
        """Initialize the tracker with database configuration.
        
        :param db_config: Dictionary with db connection parameters
        :param table_name: Name of the processing table
        """
        self.db_config = db_config
        self.connection = None
        self.table_name = table_name
        
    def connect(self):
        """Establish database connection."""
        try:
            connect_params = {
                'user': self.db_config['user'],
                'passwd': self.db_config['password'],
                'host': self.db_config['host'],
                'db': self.db_config['database'],
                'port': int(self.db_config['port']),
                'cursorclass': pymysql.cursors.DictCursor
            }
            
            self.connection = pymysql.connect(**connect_params)
            return True
        except pymysql.Error as e:
            logging.error(f"Failed to connect to database: {e}")
            return False
    
    def close(self):
        """Close database connection."""
        if self.connection:
            self.connection.close()
            self.connection = None
    
    def health_check(self):
        """Verify database connectivity and table existence.
        
        :return: True if healthy, False otherwise
        """
        try:
            if not self.connection:
                if not self.connect():
                    return False
            
            with self.connection.cursor() as cursor:
                cursor.execute("SELECT 1")
                cursor.fetchone()
            
            # Verify table exists
            with self.connection.cursor() as cursor:
                cursor.execute(f"SHOW TABLES LIKE '{self.table_name}'")
                if not cursor.fetchone():
                    logging.error(f"Table {self.table_name} does not exist in database")
                    return False
            
            return True
        except pymysql.Error as e:
            logging.error(f"Database health check failed: {e}")
            return False
    
    def check_file_processed(self, s3_key, s3_etag):
        """Check if a file has already been processed or should be skipped.
        
        :param s3_key: S3 object key
        :param s3_etag: S3 ETag for the file
        :return: Status string ('success', 'skipped') if already processed, None otherwise
        """
        try:
            with self.connection.cursor() as cursor:
                sql = f"""
                    SELECT status FROM {self.table_name}
                    WHERE s3_key = %s AND s3_etag = %s 
                    AND status IN ('success', 'skipped')
                """
                cursor.execute(sql, (s3_key, s3_etag))
                result = cursor.fetchone()
                if result:
                    return result['status']
                return None
        except pymysql.Error as e:
            logging.error(f"Error checking file status for {s3_key}: {e}")
            return None
    
    def mark_file_processing(self, s3_bucket, s3_key, s3_etag, s3_last_modified, 
                            site_code, file_name):
        """Mark a file as currently being processed."""
        try:
            with self.connection.cursor() as cursor:
                sql = f"""
                    INSERT INTO {self.table_name}
                    (s3_bucket, s3_key, s3_etag, s3_last_modified,
                     site_code, file_name, status)
                    VALUES (%s, %s, %s, %s, %s, %s, 'processing')
                    ON DUPLICATE KEY UPDATE
                        status = 'processing',
                        error_message = NULL,
                        error_type = NULL
                """
                cursor.execute(sql, (s3_bucket, s3_key, s3_etag, s3_last_modified,
                                    site_code, file_name))
                self.connection.commit()
                return True
        except pymysql.Error as e:
            logging.error(f"Error marking file as processing {s3_key}: {e}")
            self.connection.rollback()
            return False
    
    def mark_file_success(self, s3_key, s3_etag):
        """Mark a file as successfully processed."""
        try:
            with self.connection.cursor() as cursor:
                sql = f"""
                    UPDATE {self.table_name}
                    SET status = 'success',
                        processing_completed_at = NOW(),
                        error_message = NULL,
                        error_type = NULL
                    WHERE s3_key = %s AND s3_etag = %s
                """
                cursor.execute(sql, (s3_key, s3_etag))
                self.connection.commit()
                return True
        except pymysql.Error as e:
            logging.error(f"Error marking file as success {s3_key}: {e}")
            self.connection.rollback()
            return False
    
    def mark_file_failed(self, s3_key, s3_etag, error_message, error_type):
        """Mark a file as failed processing."""
        try:
            with self.connection.cursor() as cursor:
                sql = f"""
                    UPDATE {self.table_name}
                    SET status = 'failed',
                        processing_completed_at = NOW(),
                        error_message = %s,
                        error_type = %s
                    WHERE s3_key = %s AND s3_etag = %s
                """
                cursor.execute(sql, (error_message, error_type, s3_key, s3_etag))
                self.connection.commit()
                return True
        except pymysql.Error as e:
            logging.error(f"Error marking file as failed {s3_key}: {e}")
            self.connection.rollback()
            return False
    
    def mark_file_skipped(self, s3_key, s3_etag, error_message, error_type):
        """Mark a file as skipped (invalid/corrupted, should not be retried)."""
        try:
            with self.connection.cursor() as cursor:
                sql = f"""
                    UPDATE {self.table_name}
                    SET status = 'skipped',
                        processing_completed_at = NOW(),
                        error_message = %s,
                        error_type = %s
                    WHERE s3_key = %s AND s3_etag = %s
                """
                cursor.execute(sql, (error_message, error_type, s3_key, s3_etag))
                self.connection.commit()
                return True
        except pymysql.Error as e:
            logging.error(f"Error marking file as skipped {s3_key}: {e}")
            self.connection.rollback()
            return False


class ProcessingStats:
    """Track processing statistics."""
    
    def __init__(self):
        self.total_files = 0
        self.already_processed = 0
        self.processed_success = 0
        self.processed_failed = 0
        self.skipped = 0
    
    def print_summary(self):
        """Print processing summary."""
        logging.info("=" * 70)
        logging.info("PROCESSING SUMMARY")
        logging.info("=" * 70)
        logging.info(f"Total files discovered:    {self.total_files}")
        logging.info(f"Already processed:         {self.already_processed}")
        logging.info(f"Successfully processed:    {self.processed_success}")
        logging.info(f"Failed to process:         {self.processed_failed}")
        logging.info(f"Skipped:                   {self.skipped}")
        logging.info("=" * 70)


def setup_logging(log_file_path=None):
    """Configure logging with both console and file output."""
    logger = logging.getLogger()
    logger.setLevel(logging.DEBUG)
    
    logger.handlers.clear()
    
    log_level_str = os.getenv('LOG_LEVEL', 'INFO').upper()
    log_level_map = {
        'DEBUG': logging.DEBUG,
        'INFO': logging.INFO,
        'WARNING': logging.WARNING,
        'ERROR': logging.ERROR,
        'CRITICAL': logging.CRITICAL
    }
    console_log_level = log_level_map.get(log_level_str, logging.INFO)
    
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setLevel(console_log_level)
    console_formatter = logging.Formatter(
        '(%(asctime)s) %(levelname)s -- %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    console_handler.setFormatter(console_formatter)
    logger.addHandler(console_handler)
    
    if log_file_path:
        try:
            log_dir = os.path.dirname(log_file_path)
            if log_dir and not os.path.exists(log_dir):
                os.makedirs(log_dir, exist_ok=True)
            
            file_handler = RotatingFileHandler(
                log_file_path,
                maxBytes=10*1024*1024,
                backupCount=5
            )
            file_handler.setLevel(console_log_level)
            file_formatter = logging.Formatter(
                '%(asctime)s [%(levelname)s] %(name)s:%(lineno)d -- %(message)s',
                datefmt='%Y-%m-%d %H:%M:%S'
            )
            file_handler.setFormatter(file_formatter)
            logger.addHandler(file_handler)
            logging.info(f"Logging to file: {log_file_path}")
        except Exception as e:
            logging.warning(f"Could not set up file logging: {e}")


def validate_environment(default_log_filename):
    """Validate all required environment variables.
    
    :param default_log_filename: Default log filename if LOG_FILE_PATH is a directory
    :return: Dictionary of validated configuration
    :raises: RuntimeError if validation fails
    """
    config = {}
    
    # AWS credentials
    config['aws_key'] = os.getenv("AWS_ACCESS_KEY_ID")
    config['aws_secret'] = os.getenv("AWS_SECRET_ACCESS_KEY")
    config['aws_token'] = os.getenv("AWS_SESSION_TOKEN")
    
    if not config['aws_key'] or not config['aws_secret']:
        raise RuntimeError(
            "Missing AWS credentials. Please set AWS_ACCESS_KEY_ID and "
            "AWS_SECRET_ACCESS_KEY environment variables."
        )
    
    # S3 bucket
    config['bucket'] = os.getenv("BUCKET_NAME")
    if not config['bucket']:
        raise RuntimeError("Missing BUCKET_NAME environment variable.")
    
    # Metrics script (using METRICS_SCRIPT_PATH)
    config['metrics_script'] = os.getenv("METRICS_SCRIPT_PATH")
    if not config['metrics_script']:
        raise RuntimeError("Missing METRICS_SCRIPT_PATH environment variable.")
    
    # Database configuration
    db_config = {
        'user': os.getenv('DB_USER'),
        'password': os.getenv('DB_PASSWD'),
        'host': os.getenv('DB_HOST'),
        'port': os.getenv('DB_PORT'),
        'database': os.getenv('FILEPROCESSING_DB_DATABASE', 'fileprocessing')
    }
    
    missing_db_vars = []
    for key in ['user', 'password', 'host', 'port', 'database']:
        if not db_config[key]:
            missing_db_vars.append(f"DB_{key.upper()}" if key != 'database' else 'FILEPROCESSING_DB_DATABASE')
    
    if missing_db_vars:
        raise RuntimeError(
            f"Missing database configuration: {', '.join(missing_db_vars)}"
        )
    
    config['db_config'] = db_config
    
    # Optional log file path
    config['log_file_path'] = os.getenv('LOG_FILE_PATH')
    if config['log_file_path']:
        if not config['log_file_path'].endswith('.log'):
            config['log_file_path'] = os.path.join(
                config['log_file_path'], 
                default_log_filename
            )
    
    return config


def validate_metrics_script(script_path):
    """Verify metrics script exists and is readable.
    
    :param script_path: Path to metrics script
    :return: True if valid, False otherwise
    """
    if not os.path.exists(script_path):
        logging.error(f"Metrics script not found: {script_path}")
        return False
    
    if not os.path.isfile(script_path):
        logging.error(f"Metrics script is not a file: {script_path}")
        return False
    
    if not os.access(script_path, os.R_OK):
        logging.error(f"Metrics script is not readable: {script_path}")
        return False
    
    logging.info(f"Metrics script validated: {script_path}")
    return True


def health_check_s3(s3_client, bucket):
    """Verify S3 connectivity and bucket access."""
    try:
        s3_client.head_bucket(Bucket=bucket)
        logging.info(f"S3 health check passed for bucket: {bucket}")
        return True
    except ClientError as e:
        error_code = e.response.get('Error', {}).get('Code', 'Unknown')
        logging.error(f"S3 health check failed: {error_code} - {e}")
        return False
    except BotoCoreError as e:
        logging.error(f"S3 connection error: {e}")
        return False


def generate_month_prefixes(num_months=1):
    """Generate year-month prefixes based on num_months parameter."""
    prefixes = []
    current_date = datetime.now()
    
    for i in range(num_months):
        year = current_date.year
        month = current_date.month - i
        if month <= 0:
            month += 12
            year -= 1
        target_date = datetime(year, month, 1)
        prefixes.append(target_date.strftime("%Y-%m"))
    
    return prefixes


def should_process_month(month_prefix, target_months):
    """Check if a month should be processed based on target months."""
    if target_months == "all":
        return True
    
    month_str = month_prefix.rstrip("/").split("/")[-1]
    return month_str in target_months


def get_recent_month_prefixes(hours=DEFAULT_RECENT_HOURS):
    """Ensure we don't miss files that might span month boundaries."""
    now = datetime.now(timezone.utc)
    cutoff_time = now - timedelta(hours=hours)
    
    current_month = now.strftime("%Y-%m")
    prefixes = [current_month]
    
    cutoff_month = cutoff_time.strftime("%Y-%m")
    if cutoff_month != current_month:
        prefixes.append(cutoff_month)
    
    return prefixes


def filter_files_by_time(files, hours=DEFAULT_RECENT_HOURS):
    """Filter files to only those modified within the last N hours."""
    if not files:
        return []
    
    now = datetime.now(timezone.utc)
    cutoff_time = now - timedelta(hours=hours)
    
    if cutoff_time.tzinfo is None:
        cutoff_time = cutoff_time.replace(tzinfo=timezone.utc)
    
    filtered_files = []
    for file_obj in files:
        last_modified = file_obj.get("LastModified")
        if last_modified:
            if isinstance(last_modified, datetime):
                if last_modified.tzinfo is None:
                    last_modified = last_modified.replace(tzinfo=timezone.utc)
                elif last_modified.tzinfo != timezone.utc:
                    last_modified = last_modified.astimezone(timezone.utc)
                
                if last_modified >= cutoff_time:
                    filtered_files.append(file_obj)
    
    filtered_files.sort(key=lambda f: f.get("LastModified", datetime.min), reverse=True)
    return filtered_files


def extract_site_code(s3_key):
    """Extract site code from S3 key."""
    parts = s3_key.split("/")
    if len(parts) >= 2:
        return parts[1]
    return "UNKNOWN"



