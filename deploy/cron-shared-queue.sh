#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="${1:?Pass the absolute Laravel application path as the first argument}"
PHP_BINARY="${PHP_BINARY:-/usr/bin/php}"

[[ "$PROJECT_DIR" == /* && "$PROJECT_DIR" != "/" && -f "$PROJECT_DIR/artisan" ]] || {
    echo "Invalid Laravel application path." >&2
    exit 64
}

cd "$PROJECT_DIR"
command -v flock >/dev/null 2>&1 || { echo "flock is required to prevent overlapping jobs." >&2; exit 1; }
# Deployment takes an exclusive lock; active cron processes hold a shared lock.
exec 9>"$PROJECT_DIR/storage/framework/deploy.lock"
flock -s -n 9 || exit 0

worker=(
    "$PHP_BINARY" artisan queue:work database
    --queue=default
    --stop-when-empty
    --sleep=2
    --tries=2
    --timeout=900
    --max-time=900
    --no-interaction
)

exec flock -n -E 0 "$PROJECT_DIR/storage/framework/shared-queue.lock" "${worker[@]}"
