#!/bin/sh
set -e

# Render injects environment variables at runtime, so cache here, not at build time.
php artisan config:cache
php artisan view:cache

# Regenerate the OpenAPI JSON that L5-Swagger serves.
php artisan swagger:generate

# Ensure uploaded files saved on the public disk are reachable from /storage.
mkdir -p storage/app/public bootstrap/cache public
php artisan storage:link --force
chmod -R ug+rwX storage bootstrap/cache public || true

# Apply pending migrations without interactive confirmation.
php artisan migrate --force

# Render routes external traffic to $PORT; bind the server to it (fallback for local use).
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
