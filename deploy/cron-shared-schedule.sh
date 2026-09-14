#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="${1:?Pass the absolute Laravel application path as the first argument}"
PHP_BINARY="${PHP_BINARY:-/usr/bin/php}"

[[ "$PROJECT_DIR" == /* && "$PROJECT_DIR" != "/" && -f "$PROJECT_DIR/artisan" ]] || {
    echo "Invalid Laravel application path." >&2
    exit 64
}

cd "$PROJECT_DIR"
command -v flock >/dev/null 2>&1 || { echo "flock is required to coordinate deployments." >&2; exit 1; }
exec 9>"$PROJECT_DIR/storage/framework/deploy.lock"
flock -s -n 9 || exit 0
exec "$PHP_BINARY" artisan schedule:run --no-interaction
