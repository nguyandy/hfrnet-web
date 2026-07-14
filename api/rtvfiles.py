from datetime import datetime, timezone
from dateutil.relativedelta import relativedelta
from config import config, bucket, s3


def get_files(regions, prod, req_time):
    """Gets the file paths that satisfy the given input regions, time, and product

    :param regions: list of abbreviated regions
    :param prod: product string in the request url format e.g. 'a_6km'
    :param req_time: epoch time
    :return: list of file paths
    """
    rootdir = bucket + str(config["directories"]["rootdir"])
    files = []
    pfx, res = prod.split("_")

    # utc datetime object for epoch time
    dt = datetime.fromtimestamp(req_time, tz=timezone.utc)

    # Check if file(s) exists for each region. Because end time and created time are at the end of the file name, we can
    # just use a glob match for everything before those times (which we do not care about)
    for region in regions:
        # TODO add file name caching here. Could use region + product + request time as key for each file
        if pfx == "h":
            dirfiles = (
                f"{dt.strftime('%Y/%m/%d')}/"
                f"rtv-{region.lower()}-{res}-uwls_v1r0_hfr_s{dt.strftime('%Y%m%d%H')}00*.nc"
            )
        elif pfx == "a":
            dirfiles = (
                f"{dt.strftime('%Y/%m/%d')}/"
                f"averages/"
                f"rtv-{region.lower()}-{res}-25h-avg_v1r0_hfr_s{dt.strftime('%Y%m%d%H')}00*.nc"
            )
        elif pfx == "am" or pfx == "ma":
            dirfiles = (
                f"{dt.strftime('%Y/%m')}/"
                f"averages/"
                f"rtv-{region.lower()}-{res}-mon-avg_v1r0_hfr_s*.nc"
            )
        else:
            dirfiles = (
                f"{dt.strftime('%Y')}/"
                f"averages/"
                f"rtv-{region.lower()}-{res}-ann-avg_v1r0_hfr_s*.nc"
            )

        # TODO: can this be async?
        # glob all files for the given region + time
        all_files = s3.glob(f"{rootdir}/" + dirfiles)

        if len(all_files) > 0:
            # sort matches in descending order to get file with latest creation date
            # s3 sorts lexicographically in utf-8 order
            #all_files.sort(reverse=True)
            if all_files[-1] not in files:
                files.append(all_files[-1])

    return files


def get_hist_files(t0, t1, regions, prod):
    """Gets a list of file paths needed for a time series request

    :param t0: epoch start time
    :param t1: epoch end time
    :param regions: list of regions
    :param prod: product string in the request url format e.g. 'a_6km'
    :return: array containing the file paths for the time series
    """
    ts_files = []

    pfx, res = prod.split("_")
    t0 = datetime.fromtimestamp(t0, timezone.utc)
    t1 = datetime.fromtimestamp(t1, timezone.utc)
    ts = t0

    while ts <= t1:
        newfiles = get_files(regions, prod, ts.timestamp())
        if len(newfiles) > 0:
            # add each unique new file to files, ignoring potential duplicates
            for file in newfiles:
                if file not in ts_files:
                    ts_files.append(file)

        # increment ts by 1 year/month depending on pfx type to minimize no. of get_files calls
        if pfx == "am" or pfx == "ma":
            ts = ts + relativedelta(months=1)
        elif pfx == "ay" or pfx == "ya":
            ts = ts + relativedelta(years=1)
        else:
            ts = ts + relativedelta(hours=1)

    return ts_files
