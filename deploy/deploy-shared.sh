#!/usr/bin/env bash

# Constrained deployment path for Hostinger Web/Business/Cloud hosting.
# The Laravel application MUST live outside the web document root.

set -euo pipefail

if [[ $# -lt 2 || $# -gt 3 ]]; then
    echo "Usage: $0 /absolute/path/to/laravel-app /absolute/path/to/public_html [prebuilt-assets.tar]" >&2
    exit 64
fi

APP_DIR="$1"
PUBLIC_DIR="$2"
ASSET_ARCHIVE="${3:-}"

if [[ "$APP_DIR" != /* || "$PUBLIC_DIR" != /* || "$APP_DIR" == "/" || "$PUBLIC_DIR" == "/" ]]; then
    echo "Both paths must be absolute, non-root paths." >&2
    exit 64
fi

if [[ ! -d "$APP_DIR" || ! -d "$PUBLIC_DIR" ]]; then
    echo "Both the application and public directories must already exist." >&2
    exit 1
fi

APP_DIR="$(cd "$APP_DIR" && pwd -P)"
PUBLIC_DIR="$(cd "$PUBLIC_DIR" && pwd -P)"

if [[ "$APP_DIR" == "$PUBLIC_DIR" || "$APP_DIR" == "$PUBLIC_DIR/"* ]]; then
    echo "The Laravel application must be outside public_html." >&2
    exit 1
fi

if [[ ! -f "$PUBLIC_DIR/.alquran-public-root" ]]; then
    echo "Missing $PUBLIC_DIR/.alquran-public-root safety marker." >&2
    echo "Verify the exact Hostinger document root, then create the marker manually." >&2
    exit 1
fi

if [[ ! -f "$APP_DIR/artisan" || ! -f "$APP_DIR/composer.json" || ! -f "$APP_DIR/package-lock.json" || ! -f "$APP_DIR/.env" ]]; then
    echo "The application path is not a prepared Laravel production checkout." >&2
    exit 1
fi

for required_command in php composer rsync flock; do
    if ! command -v "$required_command" >/dev/null 2>&1; then
        echo "Required command is unavailable: $required_command" >&2
        exit 1
    fi
done

if [[ -z "$ASSET_ARCHIVE" ]] && ! command -v npm >/dev/null 2>&1; then
    echo "Pass the prebuilt CI assets archive when Node.js is unavailable on this hosting plan." >&2
    exit 1
fi

if [[ -n "$ASSET_ARCHIVE" && ! -f "$ASSET_ARCHIVE" ]]; then
    echo "The prebuilt assets archive does not exist." >&2
    exit 1
fi

if [[ "${GOFRAN_DEPLOY_LOCK_HELD:-0}" != 1 ]]; then
    exec 9>"$APP_DIR/storage/framework/deploy.lock"
    if ! flock -n 9; then
        echo "Another shared-hosting deployment is already running." >&2
        exit 1
    fi
fi

cd "$APP_DIR"

front_controller_tmp=""
cleanup_temporary_file() {
    if [[ -n "$front_controller_tmp" && -f "$front_controller_tmp" ]]; then
        rm -f -- "$front_controller_tmp"
    fi
}
trap cleanup_temporary_file EXIT
trap 'echo "Deployment failed; maintenance remains enabled. Complete or recover the release before artisan up." >&2' ERR

php artisan down --retry=60 --refresh=15
php -d disable_functions="system,exec,shell_exec,passthru,mysql_list_dbs,ini_alter,dl,symlink,link,chgrp,leak,popen,apache_child_terminate,virtual,mb_send_mail" "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progressif [[ -n "$ASSET_ARCHIVE" ]]; then
    # This archive is produced by CI from the exact tested commit. Extract only
    # build/ entries, and reject paths escaping public/ or archive symlinks.
    php -r '
        $archive = new PharData($argv[1]);
        foreach (new RecursiveIteratorIterator($archive) as $file) {
            $entry = substr($file->getPathname(), strlen("phar://".$argv[1]."/"));
            if (!str_starts_with($entry, "build/") || str_contains($entry, "..") || $file->isLink()) {
                fwrite(STDERR, "Unsafe frontend archive entry.\n"); exit(1);
            }
        }
        if (!$archive->offsetExists("build/manifest.json")) { exit(1); }
        $archive->extractTo($argv[2], null, true);
    ' "$ASSET_ARCHIVE" "$APP_DIR/public"
else
    npm ci --no-audit --no-fund
    VITE_REVERB_APP_KEY= npm run build
fi
php artisan config:clear
php artisan migrate --force
php artisan optimize

# Publish web assets without deleting uploads or previous hashed chunks still
# referenced by open browser tabs. Hidden source files and symlinks are excluded.
rsync -a \
    --no-links \
    --include='/.htaccess' \
    --exclude='/.?*' \
    --exclude='/index.php' \
    --exclude='/storage' \
    --exclude='/hot' \
    "$APP_DIR/public/" "$PUBLIC_DIR/"

public_storage_target="$APP_DIR/storage/app/public"
public_storage_link="$PUBLIC_DIR/storage"

if [[ -L "$public_storage_link" ]]; then
    if [[ "$(readlink -f "$public_storage_link")" != "$(readlink -f "$public_storage_target")" ]]; then
        echo "The existing public storage link points to an unexpected target." >&2
        exit 1
    fi
elif [[ -e "$public_storage_link" ]]; then
    echo "The public storage path exists but is not the expected symbolic link." >&2
    exit 1
else
    ln -s "$public_storage_target" "$public_storage_link"
fi

front_controller_tmp="$(mktemp "$APP_DIR/storage/framework/public-index.XXXXXX")"
php -r '
    $template = file_get_contents($argv[1]);
    if ($template === false) {
        fwrite(STDERR, "Unable to read the shared front-controller template.\n");
        exit(1);
    }
    $contents = str_replace("__LARAVEL_BASE_PATH__", var_export($argv[2], true), $template);
    if (file_put_contents($argv[3], $contents, LOCK_EX) === false) {
        fwrite(STDERR, "Unable to write the shared front controller.\n");
        exit(1);
    }
' "$APP_DIR/deploy/shared-index.php.example" "$APP_DIR" "$front_controller_tmp"
chmod 0644 "$front_controller_tmp"
mv -f "$front_controller_tmp" "$PUBLIC_DIR/index.php"
front_controller_tmp=""

php artisan system:production-check --profile=shared --document-root="$PUBLIC_DIR"
php artisan queue:restart
php artisan up
trap - EXIT
trap - ERR

echo "Shared-hosting deployment completed."
echo "Shared preflight passed. Verify /up, /ready, both Cron jobs, email, login, and mobile sync on the actual domain."
