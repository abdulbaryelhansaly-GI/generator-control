![CI](https://github.com/abdulbaryelhansaly-GI/generator-control/actions/workflows/ci.yml/badge.svg)

# Industrial Generator Monitoring System

A full-stack industrial generator monitoring system built with MySQL, Python, R, and Laravel.

## Stack
- **Database:** MySQL
- **Simulation:** Python (real-time telemetry + Isolation Forest anomaly detection)
- **Analytics:** R (OEE calculation + RUL regression)
- **Backend:** Laravel 12 (REST API + automated alert engine)
- **Frontend:** Blade + Chart.js (live dashboard)
- **ML:** Isolation Forest (anomaly detection) + Random Forest (failure mode classification)
- **Infrastructure:** Docker (6 containers)

## Features
- Live generator status dashboard with real-time charts
- Automated anomaly detection scoring every 2 seconds
- Remaining Useful Life prediction with predicted failure date
- Auto-generated maintenance tickets on threshold breach
- Failure mode classifier (TWF, HDF, PWF, OSF, RNF)
- REST API with 7 endpoints
- PDF and CSV export
- Full authentication
- 18 automated tests with CI pipeline

## Run with Docker
```bash
docker compose up --build
```
Visit `http://localhost:8000` — login with `admin@factory.com` / `password123`

## Run without Docker
| Terminal | Command |
|----------|---------|
| 1 | `cd generator-control && php artisan serve` |
| 2 | `cd generator-control && php artisan schedule:work` |
| 3 | `python simulate_telemetry.py` |
| 4 | `python anomaly_detection.py` |
| 5 | `python failure_classifier.py` |
