import configparser
import csv
import getopt, sys
import logging
from os import getenv
from os.path import dirname, join, abspath
import s3fs
import datetime
import pyproj
import pymysql.cursors
import matplotlib
from mpl_toolkits.basemap import Basemap
import calendar

# not sure if needed but leaving it here for now
matplotlib.use('Agg')

logger = logging.getLogger(__name__)

# Get the directory where this script is located (for resolving relative paths)
SCRIPT_DIR = dirname(abspath(__file__))


class MetricProcessingError(Exception):
    """Exception raised when a file cannot be processed for metrics."""
    pass

def get_config_options():
    """Parse config options

    :return: dictionary of options
    """
    # parse config
    config_parser = configparser.ConfigParser()
    config_parser.read_file(open(join(SCRIPT_DIR, 'streamingmetricconfig.ini')))

    # create dict mapping sites with special good thresholds to their actual threshold
    special_sites = {}
    for site in config_parser['specialsites']:
        special_sites[site] = config_parser['specialsites'][site]

    regular_threshold = config_parser['thresholds']['regular_threshold']

    ignored_sites = config_parser['sites']['ignored_sites'].strip(' ').split(',')
    mislabelled_sites = config_parser['sites']['mislabelled_sites'].strip(' ').split(',')

    # metrics db
    host = config_parser['database']['host']
    db = config_parser['database']['db']
    port = int(config_parser['database']['port'])

    config_args = {
                   'regular_threshold': regular_threshold,
                   'special_sites': special_sites,
                   'ignored_sites': ignored_sites,
                   'mislabelled_sites': mislabelled_sites,
                   'db_host': host,
                   'db': db,
                   'db_port': port,
                   }

    return config_args


def get_file_info(filename):
    """Get all needed file information from the filename

    :param filename: filename
    :return: dict containing file name, type, site, affiliation, report rate,
    """
    # get type, date, site from filename
    # file format: RDL[i|m| ]_Site_yyyy_mm_dd_hhmm[ss| ]
    file_base_name, _ = filename.split('.')
    file_split = file_base_name.split('_')
    file_type, affiliation, report_rate = None, None, None

    if len(file_split) == 6:
        # RDL[i|m| ] included, interpret RDL type
        # Type can be one of either: RDLi, RDLm, or wera (RDL with no i or m in filename)
        if file_split[0] == 'RDLi':
            file_type = 'RDLi'
        elif file_split[0] == 'RDLm':
            file_type = 'RDLm'
        elif file_split[0] == 'RDL':
            file_type = 'wera'
        # remove RDL section from file split
        file_split = file_split[1:]
    elif len(file_split) == 5:
        # Some radial sites do not have the RDL_ prefix, so file lengths of 5 are acceptable for now
        # Assume these are wera files
        file_type = 'wera'
    else:
        logger.error('Error with input file format')
        raise MetricProcessingError('Error with input file format')

    if file_type is None:
        logger.error('Error extracting file type from filename')
        raise MetricProcessingError('Error extracting file type from filename')

    # ensure site is uppercase as some filenames have a lowercase site but are in an uppercase site directory
    site = file_split[0].upper()

    # use csv to figure out affiliation for provided site
    with open(join(SCRIPT_DIR, '..', '..', 'data', 'siteinfo.csv'), 'r') as f:
        siteinfo = csv.DictReader(f, delimiter=',')
        for row in siteinfo:
            if row['site'] == site:
                # grab affiliation and report rate, if report rate not found then default to 60min
                if row['affiliation']:
                    affiliation = row['affiliation']

                if row['report_rate']:
                    report_rate = int(row['report_rate'])
                else:
                    report_rate = 60

                if row['patterntype_ignore']:
                    if row['patterntype_ignore'] == file_type:
                        # ignore this file because this is the ignored pattern type for this site
                        logger.warning(f'File is of pattern type {file_type}, which is ignored for site {site} '
                                       f'as specified in siteinfo config. Exiting')
                        raise MetricProcessingError(f'File is of pattern type {file_type}, which is ignored for site {site}')

                # override RDL type if specified in config
                if row['patterntype_override']:
                    file_type = row['patterntype_override']

    if affiliation is None:
        logger.error('Error finding site:affiliation mapping in siteinfo.csv')
        raise MetricProcessingError('Error finding site:affiliation mapping in siteinfo.csv')

    # get year and month from file (to traverse directory structure faster and group file in uptime table)
    year = file_split[1]
    month = file_split[2]

    # get day,hour,min from file name (to calculate timestamp for file latency calculation)
    day = file_split[3]
    hour = file_split[4][0:2]
    minute = file_split[4][2:4]

    if len(year) != 4 or len(month) != 2 or len(day) != 2:
        logger.error('Error extracting time info from file name')
        raise MetricProcessingError('Error extracting time info from file name')

    # file times are UTC
    timestamp = datetime.datetime(int(year), int(month), int(day), int(hour), int(minute),
                                  tzinfo=datetime.timezone.utc).timestamp()

    # the date string that will be inserted into the 'date' column in the metricUptime table (basically YYYY-MM-01)
    date_group = f'{year}-{month}-01'

    return {
        'filename': filename,
        'type': file_type,
        'site': site,
        'affiliation': affiliation,
        'year': year,
        'month': month,
        'timestamp': timestamp,
        'date_group': date_group,
        'report_rate': report_rate,
    }


def get_site_bounds(lon, lat, radar_range):
    """Calculates initial min/max lat/lon for a radar site

    :param lon: site longitude
    :param lat: site latitude
    :param radar_range: radar range in km
    :return: min/max lat/lon
    """
    # create grid
    g = pyproj.Geod(ellps='WGS84')

    _, latmin, _ = g.fwd(lon, lat, 180, radar_range * 1000)
    _, latmax, _ = g.fwd(lon, lat, 0, radar_range * 1000)
    lonmin, _, _ = g.fwd(lon, lat, 270, radar_range * 1000)
    lonmax, _, _ = g.fwd(lon, lat, 90, radar_range * 1000)

    return lonmin, lonmax, latmin, latmax


def read_radial(rad_file, s3):
    """Opens and reads the input radial file

    :param: rad_file: radial file path (for s3fs.open)
    :param: s3: s3fs.S3FileSystem instance
    :return: various data from the radial file
    """

    data = []

    lon, lat, transmit_freq = '', '', ''
    bearingcol, rangecol, latcol, loncol = 0, 0, 0, 0,

    # Some files have multiple tables defined. We only care about the radials table for this script, so just look there
    # by counting the number of tables we found
    tables_found = 0

    with s3.open(rad_file, 'rb') as rad_data:
        lines = rad_data.readlines()

        for byte_line in lines:
            # decode line
            try:
                line = byte_line.decode('utf-8')
            except:
                logger.warning('Failed to decode byte line! Skipping line.')
                continue
            # find bearing column
            if line.startswith('%Origin'):
                lon = line.split()[2]
                lat = line.split()[1]
            if line.startswith('%TransmitCenterFreqMHz'):
                transmit_freq = float(line.split()[1])
            if line.startswith('%TableColumnTypes'):
                tables_found += 1
                if tables_found == 1:
                    # read first table column headers
                    columns = line.split()
                    columns.remove('%TableColumnTypes:')

                    bearingcol = columns.index('BEAR')
                    rangecol = columns.index('RNGE')
                    latcol = columns.index('LATD')
                    loncol = columns.index('LOND')
            if not line.startswith('%') and tables_found == 1:
                # Only read data from the first table
                data.append(line.split())

    return data, rangecol, bearingcol, latcol, loncol, lon, lat, transmit_freq


def count_radials(rad_file, s3, basemap_cache=None):
    """Gets the input radial file from s3 and counts the number of good/bad radials inside
    
    :param rad_file: S3 path to the radial file
    :param s3: s3fs.S3FileSystem instance
    :param basemap_cache: optional dict to cache Basemap instances by (lon, lat)
    """

    try:
        data, rangecol, bearingcol, latcol, loncol, lon, lat, transmit_freq = read_radial(rad_file, s3)
    except Exception as e:
        logger.error(f"Error reading file {rad_file}")
        raise MetricProcessingError(f"Error reading file {rad_file}") from e

    # check for empty lon/lat
    if not lon or not lat:
        logger.error(f"Error reading file {rad_file}")
        raise MetricProcessingError(f"Error reading file {rad_file}")

    # find lat/lon bounds for this radar site and use for basemap
    # Cache Basemap by (lon, lat) - creating Basemap is expensive
    cache_key = (lon, lat)
    if basemap_cache is not None and cache_key in basemap_cache:
        bm = basemap_cache[cache_key]
    else:
        lonmin, lonmax, latmin, latmax = get_site_bounds(lon, lat, 100)
        bm = Basemap(projection='merc',
                     llcrnrlat=latmin,
                     urcrnrlat=latmax,
                     llcrnrlon=lonmin,
                     urcrnrlon=lonmax,
                     resolution='i',
                     area_thresh=100)
        if basemap_cache is not None:
            basemap_cache[cache_key] = bm

    # test to make sure there is data in file
    if not data[0]:
        logger.error(f"File {rad_file} has bad data")
        raise MetricProcessingError(f"File {rad_file} has bad data")

    total_rad_count = len(data)
    good_rad_count = 0
    bad_rad_count = 0

    # count number of "good" and "bad" radials in data
    for line in data:
        # check if data is over water
        try:
            point_lat = float(line[latcol])
            point_lon = float(line[loncol])
        except:
            logger.warning('Truncated datafile!')
            continue

        lonx, laty = bm(point_lon, point_lat)

        if not (bm.is_land(lonx, laty)):
            good_rad_count += 1
        else:
            bad_rad_count += 1

    return total_rad_count, good_rad_count, bad_rad_count, lon, lat, transmit_freq


# Note: a lot of duplicate code here vs the main function, but just going to keep is this way for now cause I don't want to break anything
def process_single_file(filename, s3, bucket, metrics_db_cursor, metrics_db_conn, 
                        config, s3_last_modified, basemap_cache=None):
    """Process a single file and update metrics database.
    
    :param filename: The filename to process
    :param s3: s3fs.S3FileSystem instance
    :param bucket: S3 bucket name
    :param metrics_db_cursor: pymysql cursor for the metrics database
    :param metrics_db_conn: pymysql connection for the metrics database
    :param config: Config dict from get_config_options()
    :param s3_last_modified: LastModified datetime from S3 listing (avoids redundant S3 calls)
    :param basemap_cache: optional dict to cache Basemap instances by (lon, lat)
    :return: (success: bool, error_message: str or None)
    """
    try:
        # get file info from cli + sites csv
        file_info = get_file_info(filename)

        # check if file is in metricFiles table; if it is, we have already calculated its metrics
        file_check_sql = f"SELECT * FROM metricFiles WHERE filename='{file_info['filename']}'"
        metrics_db_cursor.execute(file_check_sql)
        result = metrics_db_cursor.fetchall()
        if len(result) > 1:
            logger.warning(f"Multiple entries in metricFiles found for file {file_info['filename']}, exiting.")
            return (False, f"Multiple entries in metricFiles found for file {file_info['filename']}")
        elif len(result) == 1:
            logger.warning(f"metricFiles entry already found for file {file_info['filename']}, skipping file")
            return (False, f"metricFiles entry already found for file {file_info['filename']}, skipping file")

        # open file in s3
        dirfile = bucket + '/radials/{}/{}-{}/{}'.format(
            file_info['site'], file_info['year'], file_info['month'], file_info['filename']
        )

        # Use passed-in s3_last_modified instead of s3.exists() + s3.info() calls
        latency = s3_last_modified.timestamp() - file_info['timestamp']

        # count radials in file, get lon/lat/freq for metricUptime
        total_count, good_count, bad_count, sitelon, sitelat, freq = count_radials(dirfile, s3, basemap_cache)

        # do metricFiles insert
        sql = (f"INSERT INTO metricFiles (filename, type, filetime, latency, totalRadials, goodRadials, badRadials) VALUES "
               f"('{file_info['filename']}', '{file_info['type']}', {file_info['timestamp']}, "
               f"{latency}, {total_count}, {good_count}, {bad_count}) ON DUPLICATE KEY UPDATE "
               f"`totalRadials` = {total_count}, "
               f"`goodRadials` = {good_count}, "
               f"`badRadials` = {bad_count}")

        try:
            metrics_db_cursor.execute(sql)
        except pymysql.Error as e:
            logger.error(e)
            return (False, str(e))

        # check if file is "good"
        # TODO data driven approach for determining "special" and "very special" sites?
        good_file = False
        if file_info['site'].lower() in config['special_sites']:
            good_threshold = config['special_sites'][file_info['site'].lower()]
        else:
            good_threshold = config['regular_threshold']
        # file is good if there are enough good radials as well as a latency under 25 hours
        if good_count > int(good_threshold) and latency / 3600 < 25:
            good_file = True

        # update uptime database
        # TODO in the future, we can think about updating theo_obs based on the current month progression instead of always
        #  using the total number of monthly observations for theo_obs even only partway through the month
        theo_obs = ((calendar.monthrange(int(file_info['year']), int(file_info['month']))[1] * 24 * 60) /
                    int(file_info['report_rate']))

        # if file is good, increment numObs by one on duplicate table entry, otherwise do not increment numObs on duplicate
        if good_file:
            logger.debug('Good file! Updating metricUptime...')
            sql = (f"INSERT INTO metricUptime (site, affiliation, date, lon, lat, freq, type, numObs, theoObs) VALUES "
                     f"('{file_info['site']}', '{file_info['affiliation']}', '{file_info['date_group']}', "
                     f"{sitelon}, {sitelat}, {freq}, '{file_info['type']}', 1, {theo_obs}) ON DUPLICATE KEY UPDATE "
                     f"numObs = numObs + 1, theoObs = {theo_obs}")
        else:
            logger.info('Bad file! adding metricUptime entry if needed...')
            sql = (f"INSERT INTO metricUptime (site, affiliation, date, lon, lat, freq, type, numObs, theoObs) VALUES "
                     f"('{file_info['site']}', '{file_info['affiliation']}', '{file_info['date_group']}', "
                     f"{sitelon}, {sitelat}, {freq}, '{file_info['type']}', 0, {theo_obs}) ON DUPLICATE KEY UPDATE "
                     f"theoObs = {theo_obs}")

        try:
            metrics_db_cursor.execute(sql)
            metrics_db_conn.commit()
        except pymysql.Error as e:
            logger.error(e)
            return (False, str(e))

        logger.debug(f"- Successfully updated metrics for file {file_info['filename']}")
        return (True, None)

    except MetricProcessingError as e:
        return (False, str(e))


if __name__ == '__main__':
    # parse config
    config = get_config_options()
    # set base log level
    logging.basicConfig(level=logging.INFO)

    # parse cli args
    try:
        opts, args = getopt.getopt(sys.argv[1:], '', ['file=', 'loglevel='])
    except getopt.GetoptError as err:
        logger.error(str(err))
        sys.exit(2)

    file, loglevel = None, None
    for opt, arg in opts:
        if opt == '--file':
            file = arg
        if opt == '--loglevel':
            loglevel = arg

    if file is None:
        logger.error('Please specify a filename using --file=FILENAME')
        sys.exit(2)

    if loglevel is not None:
        try:
            logger.setLevel(loglevel)
        except:
            logger.error('Incorrect log level specified! Please specify a valid log level '
                         '--loglevel=[DEBUG|INFO|WARNING|ERROR|CRITICAL]')
            sys.exit(2)

    try:
        # get file info from cli + sites csv
        file_info = get_file_info(file)

        # init s3 filesystem
        s3 = s3fs.S3FileSystem(anon=False)
        bucket = getenv('BUCKET_NAME')

        # connect to metrics db
        mycur = None
        try:
            mycon = pymysql.connect(host=config['db_host'],
                                    user=getenv('DB_USER'),
                                    password=getenv('DB_PASSWD'),
                                    database=config['db'],
                                    port=config['db_port'],
                                    charset='utf8mb4',
                                    cursorclass=pymysql.cursors.DictCursor)
            mycur = mycon.cursor()
        except pymysql.Error as e:
            try:
                logger.error(f"MySQL Error [{e.args[0]}]: {e.args[1]}")
            except IndexError:
                logger.error(f"MySQL Error: {str(e)}")
                sys.exit(2)

        # check if file is in metricFiles table; if it is, we have already calculated its metrics
        file_check_sql = f"SELECT * FROM metricFiles WHERE filename='{file_info['filename']}'"
        mycur.execute(file_check_sql)
        result = mycur.fetchall()
        if len(result) > 1:
            logger.warning(f"Multiple entries in metricFiles found for file {file_info['filename']}, exiting.")
            sys.exit(2)
        elif len(result) == 1:
            logger.warning(f"metricFiles entry already found for file {file_info['filename']}, skipping file")
            sys.exit(2)

        # open file in s3
        dirfile = bucket + '/radials/{}/{}-{}/{}'.format(
            file_info['site'], file_info['year'], file_info['month'], file_info['filename']
        )

        if s3.exists(dirfile) is False:
            logger.error(f"File {dirfile} was not found in S3")
            sys.exit(2)

        # find last modified (essentially upload) time of file, compare to actual file timestamp to get latency
        created_timestamp = s3.info(dirfile)['LastModified']
        latency = created_timestamp.timestamp() - file_info['timestamp']

        # count radials in file, get lon/lat/freq for metricUptime
        total_count, good_count, bad_count, sitelon, sitelat, freq = count_radials(dirfile, s3)

        # do metricFiles insert
        sql = (f"INSERT INTO metricFiles (filename, type, filetime, latency, totalRadials, goodRadials, badRadials) VALUES "
               f"('{file_info['filename']}', '{file_info['type']}', {file_info['timestamp']}, "
               f"{latency}, {total_count}, {good_count}, {bad_count}) ON DUPLICATE KEY UPDATE "
               f"`totalRadials` = {total_count}, "
               f"`goodRadials` = {good_count}, "
               f"`badRadials` = {bad_count}")

        try:
            mycur.execute(sql)
        except pymysql.Error as e:
            logger.error(e)
            sys.exit(2)

        # check if file is "good"
        # TODO data driven approach for determining "special" and "very special" sites?
        good_file = False
        if file_info['site'].lower() in config['special_sites']:
            good_threshold = config['special_sites'][file_info['site'].lower()]
        else:
            good_threshold = config['regular_threshold']
        # file is good if there are enough good radials as well as a latency under 25 hours
        if good_count > int(good_threshold) and latency / 3600 < 25:
            good_file = True

        # update uptime database
        # TODO in the future, we can think about updating theo_obs based on the current month progression instead of always
        #  using the total number of monthly observations for theo_obs even only partway through the month
        theo_obs = ((calendar.monthrange(int(file_info['year']), int(file_info['month']))[1] * 24 * 60) /
                    int(file_info['report_rate']))

        # if file is good, increment numObs by one on duplicate table entry, otherwise do not increment numObs on duplicate
        if good_file:
            logger.info('Good file! Updating metricUptime...')
            sql = (f"INSERT INTO metricUptime (site, affiliation, date, lon, lat, freq, type, numObs, theoObs) VALUES "
                     f"('{file_info['site']}', '{file_info['affiliation']}', '{file_info['date_group']}', "
                     f"{sitelon}, {sitelat}, {freq}, '{file_info['type']}', 1, {theo_obs}) ON DUPLICATE KEY UPDATE "
                     f"numObs = numObs + 1, theoObs = {theo_obs}")
        else:
            logger.info('Bad file! adding metricUptime entry if needed...')
            sql = (f"INSERT INTO metricUptime (site, affiliation, date, lon, lat, freq, type, numObs, theoObs) VALUES "
                     f"('{file_info['site']}', '{file_info['affiliation']}', '{file_info['date_group']}', "
                     f"{sitelon}, {sitelat}, {freq}, '{file_info['type']}', 0, {theo_obs}) ON DUPLICATE KEY UPDATE "
                     f"theoObs = {theo_obs}")

        try:
            mycur.execute(sql)
        except pymysql.Error as e:
            logger.error(e)
            sys.exit(2)

        logger.info(f"Successfully updated metrics for file {file_info['filename']}")

    except MetricProcessingError:
        sys.exit(2)
