import os
from datetime import datetime, timedelta, timezone


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
# Set the directory or S3 prefix to check
BASE_PATH = 's3://rps-nccf-hfrnet-dissemination-uat/hfrtv/'
FILENAME_PATTERN = 'rtv-usegc-1km-uwls_v1r0_hfr_s{start}_e*'

def get_expected_filename(dt):
    # dt should be a datetime object in UTC
    # Format: sYYYYMMDDHHMMSSS_eYYYYMMDDHHMMSSS
    start = dt.strftime('%Y%m%d%H') + '00000' # MMSSS
    return FILENAME_PATTERN.format(start=start)

def list_files(prefix):
    import s3fs
    fs = s3fs.S3FileSystem(anon=True)
    print(f"Listing files in: {prefix}")
    return fs.glob(prefix)

def main():
    now = datetime.now(timezone.utc)
    one_hour_ago = now - timedelta(hours=2)
    date_path = one_hour_ago.strftime('%Y/%m/%d')
    expected_pattern = os.path.join(BASE_PATH, date_path, get_expected_filename(one_hour_ago))
    print(f"Checking for files matching: {expected_pattern}")
    files = list_files(expected_pattern)
    if files:
        print(f"Found {len(files)} file(s) for 1 hour ago:")
        for f in files:
            print(f"  {f}")
    else:
        print("No files found for 1 hour ago! Possible missing data.")

if __name__ == "__main__":
    main()
