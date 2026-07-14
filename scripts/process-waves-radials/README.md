# HFRNet File Processing Scripts

Documentation for running the file processing scripts.

Get started by copying .env.example and updating the values.
```
cp .env.example .env
```

## process_radials.py

Processes radial files (`.ruv`) from S3 with database tracking.

### Basic Usage

```bash
python process_radials.py [OPTIONS]
```

### Options

| Option | Description |
|--------|-------------|
| `--months N` | Process N months including current month.<br>Examples: `--months 1` (current month only), `--months 3` (current + 2 previous) |
| `--all` | Process all available months and all files for each site |
| `--dry-run` | Show what would be processed without actually processing files |
| *(no options)* | Default: Process files from the last 72 hours |

### Examples

```bash
# Process recent files (last 72 hours) - default behavior
python process_radials.py

# Process current month only
python process_radials.py --months 1

# Process last 3 months
python process_radials.py --months 3

# Process all available files
python process_radials.py --all

# Dry run to see what would be processed
python process_radials.py --dry-run

# Dry run for specific months
python process_radials.py --months 2 --dry-run
```

---

## process_waves.py

Processes wave files (`.wls`) from S3 with database tracking.

### Basic Usage

```bash
python process_waves.py [OPTIONS]
```

### Options

| Option | Description |
|--------|-------------|
| `--months N` | Process N months including current month.<br>Examples: `--months 1` (current month only), `--months 3` (current + 2 previous) |
| `--all` | Process all available months and all files for each site |
| `--dry-run` | Show what would be processed without actually processing files |
| *(no options)* | Default: Process files from the last 72 hours |

### Examples

```bash
# Process recent files (last 72 hours) - default behavior
python process_waves.py

# Process current month only
python process_waves.py --months 1

# Process last 3 months
python process_waves.py --months 3

# Process all available files
python process_waves.py --all

# Dry run to see what would be processed
python process_waves.py --dry-run

# Dry run for specific months
python process_waves.py --months 2 --dry-run
```

---

## acquisition.py

Processes a single radial or wave file from S3 and inserts data into the database.

This script is typically called by `process_radials.py` or `process_waves.py`, but can be run standalone.

### Basic Usage

```bash
python acquisition.py --file=FILENAME [OPTIONS]
```

### Options

| Option | Required | Description |
|--------|----------|-------------|
| `--file=FILENAME` | **Yes** | Filename to process (e.g., `RDL_SITE_2024_11_20_1200.ruv` or `WVLM_SITE_2024_11_20_1200.wls`) |
| `--loglevel=LEVEL` | No | Set log level: `DEBUG`, `INFO`, `WARNING`, `ERROR`, or `CRITICAL` |

### Examples

```bash
# Process a radial file
python acquisition.py --file=RDL_GCVE_2024_11_20_1200.ruv

# Process a wave file
python acquisition.py --file=WVLM_GCVE_2024_11_20_1200.wls

# Process with debug logging
python acquisition.py --file=RDL_GCVE_2024_11_20_1200.ruv --loglevel=DEBUG

# Process with error logging only
python acquisition.py --file=RDL_GCVE_2024_11_20_1200.ruv --loglevel=ERROR
```

### File Naming Convention

**Radial files:** `RDL[i|m| ]_SITE_yyyy_mm_dd_hhmm[ss| ].ruv`

**Wave files:** `WVL[M|R|B]_SITE_yyyy_mm_dd_hhmm.wls`

**Note:** WVLR wave files are ignored by the script as they have corresponding WVLM files.

---

## Notes

- All scripts track processing state in MySQL to prevent duplicate processing
- Invalid/corrupted files are automatically skipped and marked in the database
- Processing timeout is set to 2 minutes per file
- Scripts read site information from `siteinfo.csv` in the same directory

