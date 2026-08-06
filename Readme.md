# AI-Powered GitHub Repository Security Analyzer

## Overview

This repository now contains the backend foundation for the security analyzer plus the matching Vue frontend scaffold and Python analytics helpers.

## Layout

* `app/`, `routes/`, `database/`, `resources/` - Laravel API, jobs, services, migrations, and report templates
* `frontend/` - Vue 3 dashboard shell, routing, and chart components
* `backend/python-analytics/` - trend and risk classification helpers
* `docker/` and `docker-compose.yml` - local runtime containers

## Backend Responsibilities

* Authentication
* GitHub repository validation
* Repository cloning
* Security scanning
* Risk scoring
* AI recommendation generation
* PDF report generation

## Frontend Responsibilities

* Authentication pages
* Dashboard and repository views
* Scan and finding detail views
* Reports navigation
* Chart-ready visualization shell

## Analytics Responsibilities

* Trend direction analysis
* Moving averages
* Risk classification
* Graph-ready payload generation

## Local Run

```bash
docker compose up --build
```

The backend container will:

* install Composer dependencies on first boot if `vendor/` is missing
* copy `.env.example` to `.env` if needed
* generate `APP_KEY` if it is not set
* wait for MySQL and run migrations when `RUN_MIGRATIONS=true`


## Queue Worker

```bash
php artisan queue:work
```

### Authentication

* Register
* Login
* Logout

### Scanning

* Create Scan
* Get Scan Results
* View Findings

### Reports

* Generate PDF Report
* Download Reports
