import boto3
import csv
from datetime import datetime, timezone
from botocore import UNSIGNED
from botocore.client import Config


# Initialize S3 client with anonymous access
s3 = boto3.client('s3', config=Config(signature_version=UNSIGNED))
bucket = 'rps-nccf-hfrnet-dissemination-prod'

# Load station info
station_lookup_csv = 'station_lookup.csv'
all_stations = {}
with open(station_lookup_csv, mode='r', newline='') as f:
    reader = csv.DictReader(f)
    for row in reader:
        station = row['station']
        all_stations[station] = {
            'ra': row['ra'],
            'operator': row['operator']
        }

# Only crawl files under the 'radials/' prefix
prefix = 'radials/'
paginator = s3.get_paginator('list_objects_v2')
latest_by_station = {}

for page in paginator.paginate(Bucket=bucket, Prefix=prefix):
    if 'Contents' not in page:
        continue

    for obj in page['Contents']:
        key = obj['Key']
        last_modified = obj['LastModified']

        # Example key: radials/AMAG/2025-05/RDLi_AMAG_2025_05_07_0100.ruv
        parts = key.split('/')
        if len(parts) < 3:
            continue  # Skip malformed paths

        station = parts[1]

        # Compare with current latest for this station
        if (station not in latest_by_station) or (last_modified > latest_by_station[station]['LastModified']):
            latest_by_station[station] = {
                'Key': key,
                'LastModified': last_modified
            }

# Write to CSV
outfile = 'latest_radials_per_station.csv'
numstations = 0
with open(outfile, mode='w', newline='') as csvfile:
    writer = csv.writer(csvfile)
    writer.writerow(['Station', 'Path', 'Timestamp', 'RA', 'Operator'])  # header

    for station_id, station_data in sorted(all_stations.items()):
        ra = station_data['ra']
        op = station_data['operator']
        if station_id in latest_by_station:
            # Get the latest file for this station
            data = latest_by_station[station_id]                
            writer.writerow([station_id, data['Key'], data['LastModified'].replace(tzinfo=None).strftime('%Y-%m-%dT%H:%M:%S'), ra, op])
            numstations += 1
        else:
            # No files found for this station
            writer.writerow([station_id, '', '', ra, op])

    # check if we have any files that aren't listed in the station lookup
    for station_id, data in latest_by_station.items():
        if station_id not in all_stations:
            # This station is not in the lookup, so we need to add it
            writer.writerow([station_id, data['Key'], data['LastModified'].replace(tzinfo=None).strftime('%Y-%m-%dT%H:%M:%S'), '[Missing]', '[Missing]'])
    
    writer.writerow({})  # blank row
    writer.writerow(['Generated on', datetime.now(timezone.utc).isoformat(timespec='minutes')])

print(f"Data found for {numstations} stations. CSV written to {outfile}")