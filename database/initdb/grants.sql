-- Create user if not exists and grant privileges
CREATE USER IF NOT EXISTS 'hfrnet_user'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON hfradar.* TO 'hfrnet_user'@'%';
GRANT ALL PRIVILEGES ON metrics.* TO 'hfrnet_user'@'%';
GRANT ALL PRIVILEGES ON outages.* TO 'hfrnet_user'@'%';
GRANT ALL PRIVILEGES ON rtvproc.* TO 'hfrnet_user'@'%';
GRANT ALL PRIVILEGES ON fileprocessing.* TO 'hfrnet_user'@'%';
FLUSH PRIVILEGES;

