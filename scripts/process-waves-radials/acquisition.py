import csv
import datetime
import getopt, sys
import logging
import os

import s3fs
from dotenv import load_dotenv

from radialfile import RadialFile
from wavefile import WaveFile
from database import DataBase

load_dotenv()

logger = logging.getLogger(__name__)


def _decode_text_line(raw_line):
    """Decode a line from S3; RUV/WLS are usually UTF-8 but some sites (e.g MAN1) use Latin-1 bytes (e.g. 0xa1)."""
    try:
        return raw_line.decode('utf-8')
    except UnicodeDecodeError:
        return raw_line.decode('latin-1')


class AcquisitionError(Exception):
    """Raised when file processing fails (file should be marked as failed)."""
    pass


class InvalidFileError(AcquisitionError):
    """Raised when a file is invalid/skippable (file should be marked as skipped)."""
    pass

def read_file(s3_filesystem, bucketname, fileinfo):
    """Reads the radial/wave file from S3.

    :param s3_filesystem: s3fs file system object
    :param bucketname: the name of the s3 bucket the files are in
    :param fileinfo: filename info dictionary
    :return: file data as array of lines, file modification timestamp
    """

    # open file in s3
    if fileinfo['filetype'] == 'wave':
        dirfile = bucketname + (f"/waves/{fileinfo['site']}/"
                                     f"{fileinfo['year']}-{fileinfo['month']}/"
                                     f"{fileinfo['filename']}")
    elif fileinfo['filetype'] == 'radial':
        dirfile = bucketname + (f"/radials/{fileinfo['site']}/"
                                     f"{fileinfo['year']}-{fileinfo['month']}/"
                                     f"{fileinfo['filename']}")
    else:
        raise AcquisitionError(f'Unknown file type for read_file(): {fileinfo.get("filetype")}')

    if s3_filesystem.exists(dirfile) is False:
        raise AcquisitionError(f'File {dirfile} was not found in S3')

    # get file last modified timestamp
    modified_timestamp = s3_filesystem.info(dirfile)['LastModified'].timestamp()

    # read the file
    lines = []
    with s3_filesystem.open(dirfile, 'rb') as rad_data:
        raw_lines = rad_data.readlines()

        for raw_line in raw_lines:
            lines.append(_decode_text_line(raw_line))

    return lines, modified_timestamp


def get_cli_args():
    """Parses the cli args for the filename and log level

    :return: filename string, log level string
    """
    try:
        opts, _ = getopt.getopt(sys.argv[1:], '', ['file=', 'loglevel='])
    except getopt.GetoptError as err:
        logger.error(str(err))
        sys.exit(2)

    filename, loglevel = None, None
    for opt, arg in opts:
        if opt == '--file':
            filename = arg
        if opt == '--loglevel':
            loglevel = arg

    if filename is None:
        logger.error('Please specify a filename using --file=FILENAME')
        sys.exit(2)

    return filename, loglevel


_siteinfo_cache = None


def _load_siteinfo():
    """Load and cache the siteinfo.csv data."""
    global _siteinfo_cache
    if _siteinfo_cache is None:
        scripts_dir = os.getenv('SCRIPTS_DIR', '.')
        siteinfo_path = os.path.join(scripts_dir, 'data', 'siteinfo.csv')
        with open(siteinfo_path, 'r') as f:
            _siteinfo_cache = list(csv.DictReader(f, delimiter=','))
    return _siteinfo_cache


def get_file_info(filename):
    """Get all needed file information from the input filename

    :param filename: input filename
    :return: dict containing file name, type, site, affiliation, report rate,
    :raises InvalidFileError: if file type should be skipped
    :raises AcquisitionError: if file format is unrecognized
    """

    # get date, site from filename
    # rdl file format: RDL[i|m| ]_Site_yyyy_mm_dd_hhmm[ss| ]
    # wave file format: WVL[M|R|B]_Site_yyyy_mm_dd_hhmm
    file_base_name, file_extension = filename.split('.')
    file_split = file_base_name.split('_')
    prefix = None

    if len(file_split) == 6:
        # for this script, we want to ignore all 'WVLR' wave files (all WVLR files have a corresponding WVLM file
        # from the same site that we want to use instead)
        if file_split[0] == 'WVLR':
            raise InvalidFileError('WVLR file type detected. Ignoring this file')

        prefix = file_split[0]
        file_split = file_split[1:]
    elif len(file_split) != 5:
        # Some radial sites do not have the RDL_ prefix, so file lengths of 5 are acceptable for now
        raise AcquisitionError(f'Error with input file format: {filename}')

    # recognized file extensions: .wls -> WaveFile, .ruv -> RadialFile
    filetype = None
    if file_extension == 'ruv':
        filetype = 'radial'
    if file_extension == 'wls':
        filetype = 'wave'

    if filetype is None:
        raise AcquisitionError(f'Unknown file extension: {file_extension}')

    # ensure site is uppercase as some filenames have a lowercase site but are in an uppercase site directory
    site = file_split[0].upper()
    affiliation = ''
    patterntype_override = None
    # use csv to figure out affiliation for provided site
    for row in _load_siteinfo():
        if row['site'] == site:
            # grab affiliation
            if row['affiliation']:
                affiliation = row['affiliation']

            # get pattern type override/ignore for radials
            if filetype == 'radial':
                if row['patterntype_override']:
                    patterntype_override = row['patterntype_override']

                if row['patterntype_ignore'] and prefix:
                    if row['patterntype_ignore'] == prefix:
                        # ignore this file because this is the ignored pattern type for this site
                        raise InvalidFileError(
                            f'File is of pattern type {prefix}, which is ignored for site {site} '
                            f'as specified in siteinfo config'
                        )

    year = file_split[1]
    month = file_split[2]

    # get day,hour,min from file name (to calculate timestamp for file latency calculation)
    day = file_split[3]
    hour = file_split[4][0:2]
    minute = file_split[4][2:4]

    if len(year) != 4 or len(month) != 2 or len(day) != 2:
        raise AcquisitionError(f'Error extracting time info from file name: {filename}')

    # file times are UTC
    f_timestamp = datetime.datetime(int(year), int(month), int(day), int(hour), int(minute),
                                  tzinfo=datetime.timezone.utc).timestamp()

    return {
        'filename': filename,
        'filetype': filetype,
        'site': site,
        'affiliation': affiliation,
        'year': year,
        'month': month,
        'timestamp': f_timestamp,
        'patterntype_override': patterntype_override,
    }


def process_acquisition_file(filename, s3_filesystem, db):
    """Process a single file through the acquisition pipeline.

    Reads the file from S3, validates it, and inserts its data into the database.

    :param filename: The base filename to process (e.g. "RDLm_RAGG_2025_06_15_1200.ruv")
    :param s3_filesystem: Shared s3fs.S3FileSystem instance
    :param db: Shared DataBase instance (already connected)
    :raises InvalidFileError: if the file should be skipped (invalid/ignored type)
    :raises AcquisitionError: if processing fails
    """
    file_info = get_file_info(filename)
    if not file_info:
        raise AcquisitionError(f'Failed to parse file info from: {filename}')

    file_data, timestamp = read_file(s3_filesystem, os.getenv('BUCKET_NAME'), file_info)

    if file_info['filetype'] == 'wave':
        file_obj = WaveFile(file_info, file_data, timestamp)
    elif file_info['filetype'] == 'radial':
        file_obj = RadialFile(file_info, file_data, timestamp)
    else:
        raise AcquisitionError(f'Unknown file type: {file_info["filetype"]}')

    if not file_obj.validate_file():
        raise InvalidFileError(f'File validation failed: {filename}')

    station, network = file_info['site'], file_info['affiliation']
    if not station or not network:
        raise AcquisitionError(f'Missing site/affiliation for: {filename}')

    network_id = db.retrieve_network_id(network)
    if not network_id:
        raise AcquisitionError(f'Failed to retrieve network for: {network}')

    logger.debug(f"network id for {network} is {network_id}")

    site_id = db.retrieve_site_id(station, network, network_id)
    if not site_id:
        raise AcquisitionError(f'Failed to retrieve site for: {station}')

    logger.debug(f"site id for {station} is {site_id}")

    result = file_obj.insert_into_db(db, site_id, network_id)
    if not result:
        raise AcquisitionError(f'Failed to insert into database: {filename}')

    logger.info(f'Successfully inserted {filename} into database')


if __name__ == '__main__':
    # get file name and type
    file_name, log_level = get_cli_args()
    if log_level is not None:
        # set log level
        try:
            logging.basicConfig(level=log_level)
        except Exception:
            logger.error('Incorrect log level specified! Please specify a valid log level '
                         '--loglevel=[DEBUG|INFO|WARNING|ERROR|CRITICAL]')
            sys.exit(2)

    # connect to s3
    s3 = s3fs.S3FileSystem(anon=False)
    # connect to database
    with DataBase() as db:
        # test sql connection
        if not db.connection:
            logger.error('Failure to connect to hfradar database')
            sys.exit(2)

        try:
            process_acquisition_file(file_name, s3, db)
        except InvalidFileError as e:
            logger.warning(str(e))
            sys.exit(2)
        except AcquisitionError as e:
            logger.error(str(e))
            sys.exit(2)
