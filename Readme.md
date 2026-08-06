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
* use deterministic AI fallbacks unless you set a real `OPENAI_API_KEY` in your local `.env`

### Email verification and password reset

Authentication emails use the Mailgun HTTP API. Add a Mailgun private API key, a verified sending domain, and a verified sender address to `.env`:

```env
MAILGUN_API_KEY=your-private-api-key
MAILGUN_DOMAIN=mg.example.com
MAILGUN_FROM_EMAIL=security@example.com
MAILGUN_FROM_NAME="GitHub Repository Security Analyzer"
MAILGUN_BASE_URL=https://api.mailgun.net
FRONTEND_URL=http://localhost:5174
```

For EU-region Mailgun domains, use `MAILGUN_BASE_URL=https://api.eu.mailgun.net`. The API key remains server-side and is sent using HTTP Basic Auth with the username `api`.

Run migrations before using password reset. New registrations receive a signed email-verification link. Repository and scan APIs require a verified email; users can resend verification from the frontend. Password reset links expire after 60 minutes and invalidate existing Sanctum tokens after a successful reset.

### OpenAI code scanning

The scan queue combines deterministic scanners (Semgrep, Gitleaks, Composer Audit, and npm Audit) with an optional OpenAI source-code review. The AI scanner is bounded by file count and byte limits, excludes secrets and dependency/build directories, and stores its output as normal findings so scores, recommendations, analytics, and PDF reports include it automatically.

Copy `.env.example` to `.env`, generate an application key, and set `OPENAI_API_KEY`. The default `gpt-4.1-mini` model can be changed with `OPENAI_MODEL`. Set `OPENAI_CODE_SCAN_ENABLED=false` to disable AI scanning while keeping the deterministic scanners active. The scan is asynchronous, so start a queue worker with:

```bash
php artisan queue:work --tries=3 --timeout=900
```

The API key remains server-side; it is never exposed to the Vue frontend.

### External API logs

OpenAI and Mailgun requests are recorded in `external_api_logs` with provider, operation, HTTP status, duration, errors, and bounded responses. Request bodies are disabled by default because they may contain repository source code or email content. Enable them only when needed with `EXTERNAL_API_LOG_REQUEST_PAYLOADS=true`.


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
