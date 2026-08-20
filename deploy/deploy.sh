#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$PROJECT_DIR"

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

maintenance_enabled=0
restore_application() {
    if [[ "$maintenance_enabled" -eq 1 ]]; then
        php artisan up || true
    fi
}
trap restore_application EXIT

php artisan down --retry=60 --refresh=15
maintenance_enabled=1
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan system:production-check
php artisan queue:restart
php artisan reverb:restart
php artisan up
maintenance_enabled=0
trap - EXIT

echo "Deployment completed successfully."
