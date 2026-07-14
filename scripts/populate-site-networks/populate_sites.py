import os
import csv
import logging
from dotenv import load_dotenv
import mysql.connector
from mysql.connector import errorcode

CSV_FILE = os.getenv('CSV_FILE', 'sites.csv')

load_dotenv()

LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO').upper()
numeric_level = getattr(logging, LOG_LEVEL, None)
if not isinstance(numeric_level, int):
    raise ValueError(f'Invalid log level: {LOG_LEVEL}')
logging.basicConfig(
    level=numeric_level,
    format='(%(asctime)s) %(levelname)s -- %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

DB_CONFIG = {
    'user':            os.getenv('DB_USER'),
    'password':        os.getenv('DB_PASS'),
    'database':        os.getenv('DB_NAME'),
    'port':            int(os.getenv('DB_PORT', 3306)),
    'host':            os.getenv('DB_HOST', 'localhost'),
    'raise_on_warnings': True
}


def main():
    try:
        cnx = mysql.connector.connect(**DB_CONFIG)
        cursor = cnx.cursor()

        with open(CSV_FILE, newline='', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                sta = row['sta'].strip()
                csv_staname = row['staname'].strip()

                if not csv_staname:
                    logging.debug(f"Skipping {sta}: no staname in CSV")
                    continue

                # Since it is possible to have multiple sites with same name, bulk‐update and only those still at the default '-' staname
                cursor.execute(
                    """
                    UPDATE site
                    SET staname = %s
                    WHERE sta = %s
                    AND staname = '-'
                    """,
                    (csv_staname, sta)
                )
                updated = cursor.rowcount

                if updated > 0:
                    logging.info(f"Updated `{sta}` → `{csv_staname}`")
                else:
                    logging.debug(
                        f"No default-staname rows to update for `{sta}` already set)")

                cnx.commit()

    except mysql.connector.Error as err:
        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            logging.error("Error: Access denied (check username/password)")
        elif err.errno == errorcode.ER_BAD_DB_ERROR:
            logging.error("Error: Database does not exist")
        else:
            logging.error(f"MySQL error: {err}")
    finally:
        if 'cursor' in locals():
            cursor.close()
        if 'cnx' in locals() and cnx.is_connected():
            cnx.close()


if __name__ == "__main__":
    main()
