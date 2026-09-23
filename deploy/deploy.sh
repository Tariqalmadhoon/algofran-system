#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$PROJECT_DIR"
PROJECT_DIR="$(pwd -P)"

if [[ ! -f artisan || ! -f composer.json || ! -f package-lock.json || ! -f .env ]]; then
    echo "The target is not a prepared Laravel production directory." >&2
    exit 1
fi

for required_command in php composer npm flock; do
    if ! command -v "$required_command" >/dev/null 2>&1; then
        echo "Required command is unavailable: $required_command" >&2
        exit 1
    fi
done

if [[ "${GOFRAN_DEPLOY_LOCK_HELD:-0}" != 1 ]]; then
    exec 9>"$PROJECT_DIR/storage/framework/deploy.lock"
    if ! flock -n 9; then
        echo "Another production deployment is already running." >&2
        exit 1
    fi
fi

# The caller may already have enabled maintenance before updating the checkout.
# On failure keep maintenance active: dependencies/schema may be only partly updated.
trap 'echo "Deployment failed; maintenance remains enabled. Review logs, complete or recover the release, then run artisan up." >&2' ERR
php artisan down --retry=60 --refresh=15
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
npm ci --no-audit --no-fund
npm run build
php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\QuranReferenceSeeder --force
php artisan storage:link
php artisan optimize
php artisan system:production-check --profile=vps
php artisan queue:restart
php artisan reverb:restart
php artisan up
trap - ERR

echo "Deployment completed successfully."
