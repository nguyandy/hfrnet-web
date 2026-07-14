# HFRNET Streaming Metrics Script

This repository contains the script to calculate metrics on the HFRNET raw radial files. This script is designed to be
used "on the fly" for each new radial file that is received, as opposed to a batch approach for calculating metrics.

## Features
- Calculate metrics and populate metrics database for a given input radial file.
- Connects to S3 to access the raw radial files directly (instead of from the local filesystem).
- Configurable using environment variables and/or ini config file.

---

## Running the Project
#### Prerequisites:
- Ensure Python 3.9 is installed.
- Install dependencies:
  ```bash
  pip install -r requirements.txt
  ```
#### Setup:
1. Create a `.env` file based on the `.env.example` file:
   ```bash
   cp .env.example .env
   ```
   Update the variables in `.env` as needed (e.g., `DB_USER/PASSWD,`, S3 information).

2. Start the application:
   ```bash
   python streamingmetric.py --file=file_name.ruv
   ```
   Metrics will be calculated and stored in the specified metrics database. If the specified file is already in the metricFiles table of the database, the script assumes that the metrics for that file have already been calculated and they will not be ran again.

---

## File Descriptions
- `streamingmetric.py`: Main application script to calculate the metrics.
- `requirements.txt`: Python dependencies.
- `siteinfo.csv`: CSV file containing all known sites/affiliations in HFRNet. Sites/affiliations may need to be updated periodically if there are any changes.
- `.env.example`: Example environment variables configuration file.
- `streamingmetricconfig.ini`: Configuration file for the script.

---

## Environment Variables
- `BUCKET_NAME`: S3 bucket name where raw radial files are stored. S3 directory structure is currently {BUCKET_NAME}/radials/{SITE}/{YYYY}/{MM}/file.ruv
- `AWS_ACCESS_KEY_ID`: AWS key.
- `AWS_SECRET_ACCESS_KEY`: AWS secret.
- `AWS_REGION`: Region of S3 bucket.
- `DB_USER`: Username for metrics database.
- `DB_PASSWD`: Password for metrics database.

---

## Config Sections
- `database`: Database hostname, database name, database port
- `sites`: Sites with special thresholds, mislabelled sites, sites to ignore
- `thresholds`: Threshold of good radials in a file to make the file "good" in each category

---