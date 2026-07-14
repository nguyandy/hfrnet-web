import boto3
import csv
from datetime import datetime
from botocore import UNSIGNED
from botocore.client import Config

# Initialize S3 client with anonymous access
s3 = boto3.client('s3', config=Config(signature_version=UNSIGNED))
bucket = 'rps-nccf-hfrnet-dissemination-prod'
prefix = 'waves/'

# Get all stations
response = s3.list_objects_v2(Bucket=bucket, Prefix=prefix, Delimiter='/')
stations = [p['Prefix'].split('/')[-2] for p in response.get('CommonPrefixes', [])]

results = []

for station in stations:
    station_prefix = f'waves/{station}/'
    # List all year-month folders for this station
    resp = s3.list_objects_v2(Bucket=bucket, Prefix=station_prefix, Delimiter='/')
    ym_folders = [p['Prefix'] for p in resp.get('CommonPrefixes', [])]
    if not ym_folders:
        continue
    # Find the latest year-month
    latest_ym = max(ym_folders)
    # List all files in the latest year-month
    files_resp = s3.list_objects_v2(Bucket=bucket, Prefix=latest_ym)
    files = [obj['Key'].split('/')[-1] for obj in files_resp.get('Contents', []) if obj['Key'].endswith('.wls')]
    results.append({'station': station, 'latest_ym': latest_ym, 'files': files})

# Output results as CSV
with open('latest_wave_files_per_station.csv', mode='w', newline='') as csvfile:
    writer = csv.writer(csvfile)
    writer.writerow(['Station', 'LatestYearMonth', 'Files'])
    for row in results:
        print(f"Station: {row['station']}, LatestYearMonth: {row['latest_ym']}, Files: {', '.join(row['files'])}")
        writer.writerow([row['station'], row['latest_ym'], *row['files']])

print('CSV written to latest_wave_files_per_station.csv')
