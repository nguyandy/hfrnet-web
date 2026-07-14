import configparser
import pathlib
from os import getenv
import s3fs
from obstore.store import S3Store

root = pathlib.Path(__file__).resolve().parent
config = configparser.ConfigParser()
config.read_file(open(root / 'radialdata.ini'))
bucket = getenv('AWS_BUCKET_NAME')
s3_store = S3Store()
# obstore store may use fsspec internally but get_files uses s3fs; keep both.
s3 = s3fs.S3FileSystem(anon=False, use_listings_cache=True, listings_expiry_time=300)
