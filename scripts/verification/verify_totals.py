import csv
from datetime import datetime, timezone
import s3fs


buckets = {
    'prod': 'rps-nccf-hfrnet-dissemination-prod',
    'uat': 'rps-nccf-hfrnet-dissemination-uat',
}

products = {
    'uswc': ['500m', '1km', '2km', '6km'],
    'usegc': ['1km', '2km', '6km'],
    'prvi' : ['2km', '6km'],
    'ushi' : ['1km', '2km', '6km'],
    'akns': ['6km'],
    'gak': ['1km', '2km', '6km'],
    'glna': ['500m', '1km', '2km', '6km']
}

times = [ 'h', 'a', 'am', 'ann' ] 
time_ext = ['uwls', '25h-avg', 'mon-avg', 'ann-avg']

# TODO: Make a service or something
# Copied from api/radialdata.py for convenience (slightly modified)
def get_files(rootdir, regions, timestep, resolution, req_time):
    """Gets the file paths that satisfy the given input regions, time, and product

    :param regions: list of abbreviated regions
    :param prod: product string in the request url format e.g. 'a_6km'
    :param req_time: string in format YYYYMMDDHH
    :return: list of file paths
    """
    files = []

    # Parse req_time string (YYYYMMDDHH) to datetime object
    dt = datetime.strptime(req_time, '%Y%m%d%H').replace(tzinfo=timezone.utc)

    # get list of relevant file paths/names to match against
    # returns all regions so that we only need to call s3fs glob one time to get all the needed files
    if timestep == 'h':
        # hourly files, filters by directory, start time, and correct resolution
        dirfiles = (f"{rootdir}/"
                    f"{dt.strftime('%Y')}/"
                    f"{dt.strftime('%m')}/"
                    f"{dt.strftime('%d')}/"
                    f"rtv-{regions}-{resolution}-uwls_v1r0_hfr_s*.nc")
    elif timestep == 'a':
        # 25hour average files, filters by directory, start time, and correct resolution
        dirfiles = (f"{rootdir}/"
                    f"{dt.strftime('%Y')}/"
                    f"{dt.strftime('%m')}/"
                    f"{dt.strftime('%d')}/"
                    f"averages/"
                    f"rtv-{regions}-{resolution}-25h-avg_v1r0_hfr_s*.nc")
    elif timestep == 'am' or timestep == 'ma':
        # monthly average files, should only be 1 per site/resolution per averages directory
        dirfiles = (f"{rootdir}/"
                    f"{dt.strftime('%Y')}/"
                    f"{dt.strftime('%m')}/"
                    f"averages/"
                    f"rtv-{regions}-{resolution}-month-avg_v1r0_hfr_s*.nc")
    else:
        # annual average files, should only be 1 per site/resolution per averages directory
        dirfiles = (f"{rootdir}/"
                    f"{dt.strftime('%Y')}/"
                    f"averages/"
                    f"rtv-{regions}-{resolution}-ann-avg_v1r0_hfr_s*.nc")

    all_files = s3.glob(dirfiles)

    return all_files


def write_csv(rootdirs, req_time):
    output_csv = 'hfrtv_file_counts.csv'
    envs = list(rootdirs.keys())
    with open(output_csv, mode='w', newline='') as csvfile:
        writer = csv.writer(csvfile)
        writer.writerow(['Date', req_time])
        header = ['Region', 'Resolution', 'TimeType'] + [f'{env.upper()} Count' for env in envs]
        writer.writerow(header)
        for region, resolutions in products.items():
            for resolution in resolutions:
                for time_type in times:
                    counts = []
                    for env in envs:
                        files = get_files(rootdirs[env], region, time_type, resolution, req_time)
                        numfiles = len(files)
                        counts.append(numfiles)
                        print(f"[{env}] {region} {resolution} {time_type}: {numfiles} files found")
                    writer.writerow([region, resolution, time_type] + counts)
    print(f"CSV written to {output_csv}")


if __name__ == '__main__':    
    s3 = s3fs.S3FileSystem(anon=True)
    rootdirs = {env: f's3://{b}/hfrtv' for env, b in buckets.items()}
    req_time = '2026030900'  # string in YYYYMMDDHH format

    write_csv(rootdirs, req_time)