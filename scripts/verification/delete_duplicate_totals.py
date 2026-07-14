import csv
from datetime import datetime, timezone, timedelta
import s3fs
from concurrent.futures import ThreadPoolExecutor

bucket = 'rps-nccf-hfrnet-dissemination-uat'

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
                    f"rtv-{regions}-{resolution}-uwls_v1r0_hfr_s{dt.strftime('%Y%m%d%H')}00*.nc")
    elif timestep == 'a':
        # 25hour average files, filters by directory, start time, and correct resolution
        dirfiles = (f"{rootdir}/"
                    f"{dt.strftime('%Y')}/"
                    f"{dt.strftime('%m')}/"
                    f"{dt.strftime('%d')}/"
                    f"averages/"
                    f"rtv-{regions}-{resolution}-25h-avg_v1r0_hfr_s{dt.strftime('%Y%m%d%H')}00*.nc")
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


def delete_files(rootdir, req_time):
#    deleted_files_csv = f'hfrtv_deleted_files.csv'
    deleted_files_list = []
    for region, resolutions in products.items():
        for resolution in resolutions:
            for time_type in times:                    
                files = get_files(rootdir, region, time_type, resolution, req_time)
                files.sort(reverse=True)  # Sort files in reverse order
                keep_file = files[0] if files else None
                to_delete = files[1:] if len(files) > 1 else []
                deleted_count = 0
                for file in to_delete:
                    try:
                        # Ensure file is a full S3 URI for s3fs
                        if not file.startswith('s3://'):
                            file_path = f's3://{file}'
                        else:
                            file_path = file
                        s3.rm(file_path)
                        deleted_files_list.append([region, resolution, time_type, file])
                        deleted_count += 1
                    except Exception as e:
                        print(f"Failed to delete {file}: {e}")
                print(f"{region} {resolution} {time_type}: {len(files)} files found, {deleted_count} deleted, kept: {keep_file}")

    # TODO: this doesn't work anyway multithreaded
    # Write deleted files to a separate CSV
#    with open(deleted_files_csv, mode='w', newline='') as delcsv:
#        delwriter = csv.writer(delcsv)
#        delwriter.writerow(['Region', 'Resolution', 'TimeType', 'DeletedFile'])
#        for row in deleted_files_list:
#            delwriter.writerow(row)
#    print(f"Deleted files CSV written to {deleted_files_csv}")


if __name__ == '__main__':
    import csv
    from datetime import datetime, timedelta
    from concurrent.futures import ThreadPoolExecutor
    s3 = s3fs.S3FileSystem()
    rootdir = 's3://rps-nccf-hfrnet-dissemination-uat/hfrtv'    
    start_time = datetime.strptime('2025063000', '%Y%m%d%H')
    req_times = [(start_time + timedelta(hours=hour)).strftime('%Y%m%d%H') for hour in range(24)]

    def process_hour(req_time):
        print(f"Processing hour: {req_time}")
        delete_files(rootdir, req_time)

    with ThreadPoolExecutor(max_workers=8) as executor:
        executor.map(process_hour, req_times)