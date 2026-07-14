# HFRNET Frontend

This repository contains the frontend for the HFRNET application. The frontend is built using PHP with some JavaScript components requiring Node.js for development.
The frontend can be run locally using Docker.

---

## Running the Project Locally

### 1. Using Docker

#### Prerequisites
- Docker and Docker Compose installed.

#### Setup
1. At the project repo root directory, create a `.env` file based on the `.env.example` file:
   ```bash
   cp .env.example .env
   ```
   Update the following variables in `.env`:
   - `GOOGLE_MAPS_API_KEY`: Your Google Maps API Key.
   - `API_URL`: The URL of the backend API.

   For ease of running this locally, keep the other env variables as is.

2. Start the database container in `hfrnet-app/database`
   ```
   docker-comose up -d
   ```

2. Start the client container in `hfrnet-app/client`:
   ```bash
   docker-compose up --build
   ```

3. Access the application at `http://localhost:3000`.

---

### 2. For PHP Development (Without Docker)

#### Prerequisites
- PHP 5.6 installed locally.
- A web server such as Apache or Nginx configured to serve the files in this directory.

#### Setup
1. Set up a virtual host to serve the files in this directory.
2. Create a `.env` file as described above and ensure the environment variables are configured.

3. Start your local web server and access the application.

---

## JavaScript Development

The frontend contains JavaScript that must be compiled before changes are reflected. This requires Node.js.

#### Prerequisites
- Node.js and npm installed.

#### Setup
1. Install dependencies:
   ```bash
   npm install
   ```

2. Watch for changes and recompile automatically:
   ```bash
   npm run watch
   ```

3. Alternatively, manually minify JavaScript:
   ```bash
   npm run minify
   ```

4. Reload the browser to view changes.

---


## Database Configuration

The application uses `hfrnet_db.ini` for database configuration. This file contains connection settings for multiple databases:

- **hfradar**: Main radar data database
- **metrics**: Application metrics database  
- **outages**: Outages tracking database

### Configuration File Location
`./www/lib/diagnostics/hfrnet_db.ini`

**Note**: Leave this file as is to easily spin up local development.

---


## File Descriptions
- `index.php`: The main entry point for the application.
- `docker-compose.yml`: Configuration for running the frontend with Docker.
- `.env.example`: Example environment variables configuration file.
- `package.json`: Node.js dependencies and scripts for JavaScript development.
- `webpack.config.js`: Webpack configuration for JavaScript bundling.
- `./js/index.js`: Powers the interactive map for the HFRNET frontend application. It integrates Google Maps with real-time vector data visualization and provides functionalities for user interactions 

---