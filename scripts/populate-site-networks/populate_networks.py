import os
import csv
import logging
from dotenv import load_dotenv
import mysql.connector
from mysql.connector import errorcode

CSV_FILE = os.getenv('CSV_FILE', 'networks.csv')

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
                net = row['net'].strip()
                csv_name = row['netname'].strip()
                csv_assoc = row['regional_association'].strip()

                # Skip rows with missing data
                if not csv_name and not csv_assoc:
                    logging.debug(f"Skipping {net}: no data in CSV")
                    continue

                # Lookup existing record (get both fields)
                cursor.execute(
                    """
                    SELECT netname, regional_association 
                    FROM network 
                    WHERE net = %s
                    """,
                    (net,)
                )
                result = cursor.fetchone()

                if result:
                    existing_name = (result[0] or '').strip()
                    existing_assoc = (result[1] or '').strip()

                    updates = []
                    params = []

                    # Only update netname if it's blank in DB but present in CSV
                    if not existing_name and csv_name:
                        updates.append("netname = %s")
                        params.append(csv_name)

                    # Only update regional_association if blank in DB but present in CSV
                    if not existing_assoc and csv_assoc:
                        updates.append("regional_association = %s")
                        params.append(csv_assoc)

                    if updates:
                        sql = f"UPDATE network SET {', '.join(updates)} WHERE net = %s"
                        params.append(net)
                        cursor.execute(sql, tuple(params))
                        logging.info(f"Updated `{net}` → "
                                     f"netname='{csv_name}', "
                                     f"regional_assoc='{csv_assoc}'")
                    else:
                        logging.debug(f"No update needed for `{net}`: "
                                      f"netname='{existing_name}', "
                                      f"regional_assoc='{existing_assoc}'")

                else:
                    logging.warning(
                        f"No network row found for `{net}`; inserting new row")

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
