# HFRNET Database

MySQL 8.0 database container for the HFRNET application.

## Quick Start

```bash
docker-compose up -d
```

## What's Included

- **MySQL 8.0** container with persistent storage
- **Auto-initialization** with schema files in `initdb/`
- **Multiple databases**: hfradar, metrics, outages, rtvproc
- **Port 3306** exposed for local connections

## Schema Files

- `hfradar_schema.sql` - Main radar data tables
- `metrics_schema.sql` - Application metrics
- `outages_schema.sql` - System outages tracking  
- `rtvproc_schema.sql` - Real-time vector processing
- `grants.sql` - User permissions

Database initializes automatically on first run.
