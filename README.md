cat > README.md << 'EOF'
# PetCare API

Laravel 13 headless RESTful API for the Smart Pet Care & Monitoring Ecosystem.
Final-year project — BSc Computing Systems, Ulster University (COM668).

## Status

Sprint 1 in progress. See `docs/at2/` for the Challenge Definition Report.

## Local development prerequisites

- PHP 8.3+
- Composer 2.7+
- MySQL 8.0+
- Node 20 LTS+ (for asset compilation only — no frontend code lives here)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Architecture

This repository is one of three:

- `petcare-api` — this repository, the Laravel API and contract authority
- `petcare-ai` — Python perception service (YOLO26n, OpenCV)
- `petcare-frontend` — Next.js veterinarian portal + React Native owner client

## Author

Abbas Uddin (B00965263)
EOF