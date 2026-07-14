# Population Scripts for Site Names and Network Names 


## Scripts

### `populate_networks.py`
This script updates the `network` table with network names and regional associations from a CSV file.

**What it does:**
- Reads network data from `networks.csv`
- Updates the `network` table with `netname` and `regional_association` information
- Only updates fields that are currently blank in the database (preserves existing data)
- Processes each network by its identifier (`net` column), not id.

### `populate_sites.py`
This script updates the `site` table with proper site names from a CSV file.

**What it does:**
- Reads site data from `sites.csv`
- Updates the `site` table with proper site names (`staname`)
- Only updates sites that have the default value of `-` as their `staname`
- Processes each site by its identifier (`sta` column)

## Setup

### 1. Install Dependencies
```bash
pip install -r requirements.txt
```

### 2. Environment Configuration
Create a `.env` file in the project root with the following configuration:

```bash
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password

# Optional Configuration
LOG_LEVEL=INFO
CSV_FILE=networks.csv  # or sites.csv depending on the script
```

## Usage

### Running the Scripts

#### Update Network Information
```bash
python populate_networks.py
```

#### Update Site Information
```bash
python populate_sites.py
```


## Notes
- Both scripts use transactions and commit changes after processing each record
- The scripts are designed to be run multiple times safely
- Empty or missing values in CSV files are handled gracefully
- Does not overwrite values, however the script can be easily modified in the future to do so.