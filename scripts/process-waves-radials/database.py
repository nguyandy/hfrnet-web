import logging
import os

import pymysql

logger = logging.getLogger(__name__)

class DataBase:
    """Class containing database-related utilities for the acquisition script"""

    def __init__(self):
        # get env for database connection
        self.db_user = os.getenv('DB_USER')
        self.db_password = os.getenv('DB_PASSWD')
        self.db_host = os.getenv('DB_HOST')
        self.db_port = os.getenv('DB_PORT')
        self.db_database = os.getenv('DB_DATABASE')
        self.db_ssl_ca = os.getenv('DB_SSL_CA')
        self.db_ssl_client_key = os.getenv('DB_SSL_CLIENT_KEY')
        self.db_ssl_client_cert = os.getenv('DB_SSL_CLIENT_CERT')

    def __enter__(self):
        # init database connection
        self.connection = self.init_db()
        return self

    def init_db(self):
        """Connects to the hfradar database.

        :return: Database connection, or None if database connection failed
        """

        # Make sure that items are not missing from env
        if self.db_host is None:
            logger.error('DB_HOST environment variable not found, cannot connect to database')
            return None
        if self.db_user is None:
            logger.error('DB_USER environment variable not found, cannot connect to database')
            return None
        if self.db_password is None:
            logger.error('DB_PASSWORD environment variable not found, cannot connect to database')
            return None
        if self.db_port is None:
            logger.error('DB_PORT environment variable not found, cannot connect to database')
            return None
        if self.db_database is None:
            logger.error('DB_DATABASE environment variable not found, cannot connect to database')
            return None

        # connect to the database
        try:
            if self.db_ssl_ca is not None:
                db = pymysql.connect(user=self.db_user,
                                     passwd=self.db_password,
                                     host=self.db_host,
                                     db=self.db_database,
                                     port=int(self.db_port),
                                     ssl={'ssl': {'ca': self.db_ssl_ca,
                                                  'key': self.db_ssl_client_key,
                                                  'cert': self.db_ssl_client_cert}},
                                     cursorclass=pymysql.cursors.DictCursor)
            else:
                db = pymysql.connect(user=self.db_user,
                                     passwd=self.db_password,
                                     host=self.db_host,
                                     db=self.db_database,
                                     port=int(self.db_port),
                                     cursorclass=pymysql.cursors.DictCursor)
        except Exception as e:
            logger.error("init_db(): Unable to connect to MySQL database: " + self.db_database + ". " + str(e))
            return None

        return db

    def retrieve_network_id(self, network):
        """Checks if the netowrk exists in the database, returning its ID if it does or adding it if it does not

        :param network: network name
        :return: network id, or None if an error occurred
        """
        # check network table for current network
        with self.connection.cursor() as cur_data:
            sql = f"SELECT network_id from network where net='{network}'"
            try:
                numrows = cur_data.execute(sql)
            except pymysql.Error as e:
                code, msg = e.args
                logger.error(f"Failure to select network {network} in SQL: {msg}")
                return None

            # add the new network if it is not found, retrieve the corresponding network_id if it is found
            if numrows == 0:
                sql = f"insert ignore into network (net) values('{network}')"
                try:
                    cur_data.execute(sql)
                    self.connection.commit()
                except pymysql.Error as e:
                    code, msg = e.args
                    logger.error(f"Unable to insert network {network} into database: {msg}")
                    return None
                return cur_data.lastrowid
            else:
                rs = cur_data.fetchone()
                return rs['network_id']

    def retrieve_site_id(self, station, network, network_id):
        """Checks if the station exists in the database, returning its ID if it does or adding it if it does not

        :param station: site name
        :param network: network name
        :param network_id: network id
        :return: site id, or None if an error occurred
        """
        # Check site table for current site/station
        with self.connection.cursor() as cur_data:
            sql = (f"SELECT s.site_id from site s left join network n ON s.network_id = n.network_id "
                   f"where sta='{station}' and net='{network}'")
            try:
                numrows = cur_data.execute(sql)
            except pymysql.Error as e:
                code, msg = e.args
                logger.error(f"Unable to retrieve station id for {station}: {msg}. Skipping file.")
                return None

            # add the new site if it is not found, retrieve the corresponding site_id if it is found
            if numrows == 0:
                sql = f"insert ignore into site (network_id,sta) values({network_id},'{station}')"
                try:
                    cur_data.execute(sql)
                    self.connection.commit()
                except pymysql.Error as e:
                    code, msg = e.args
                    logger.error(f"Unable to insert sta {station} into database: {sql} {msg}.  Skipping file.")
                    return None
                return cur_data.lastrowid
            else:
                rs = cur_data.fetchone()
                return rs['site_id']

    def __exit__(self, exc_type, exc_val, exc_tb):
        # close the database connection if nessecary
        if self.connection is not None:
            self.connection.close()