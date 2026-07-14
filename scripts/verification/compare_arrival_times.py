import re
from datetime import datetime, timezone

import s3fs

BUCKET_PREFIX = 's3://rps-nccf-hfrnet-dissemination-uat/hfrtv/2025/08/19/'
FILENAME_REGEX = re.compile(r'^rtv-usegc-1km-uwls_v1r0_hfr_s(\d{15})_e\d{15}_c\d+\.nc$')

def parse_s_time(filename):
    import os
    base = os.path.basename(filename)
    match = FILENAME_REGEX.match(base)
    if match:
        s_time_str = match.group(1)
        # s_time_str: YYYYMMDDHHMMSSsss (sss = milliseconds, ignore or set to 0)        
        dt = datetime.strptime(s_time_str[:14], '%Y%m%d%H%M%S')
        return dt.replace(tzinfo=timezone.utc)
    return None

def main():
    fs = s3fs.S3FileSystem(anon=True)
    # Only list rtv-usegc-1km files for the specified day
    files = fs.glob(BUCKET_PREFIX + 'rtv-usegc-1km*.nc')
    print(f"Found {len(files)} files.")
    for file in files:
        s_time = parse_s_time(file)
        if s_time is None:
            continue
        info = fs.info(file)
        # S3 returns 'LastModified' as datetime
        upload_time = info.get('LastModified')
        if upload_time is None:
            continue
        diff = (upload_time - s_time).total_seconds() / 60.0  # minutes
        print(f"{file}\n  s_time: {s_time}  upload: {upload_time}  diff: {diff:.2f} min")

if __name__ == "__main__":
    main()
