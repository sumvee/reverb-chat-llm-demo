#!/bin/sh
set -e
cd /var/www/html

# composer install and npm run build are done ON THE HOST, not here: installing
# into a macOS bind mount fails (php-code-coverage: "Failed to open directory").
if [ ! -f vendor/autoload.php ]; then
  echo "!! vendor/ missing. Run on the host:  composer install"
  exit 1
fi

if [ ! -f .env ]; then
  echo "==> writing .env"
  cp .env.example .env
  php artisan key:generate --ansi --no-interaction
fi

# Point .env at the compose services every boot: the scaffold defaults to
# sqlite, and these must track the container env rather than whatever was
# baked in the first time.
set_env() {
  if grep -qE "^#?${1}=" .env; then
    sed -i "s|^#\?${1}=.*|${1}=${2}|" .env
  else
    printf '%s=%s\n' "$1" "$2" >> .env
  fi
}
set_env APP_NAME "WebSockets Demo"
set_env APP_URL "${APP_URL}"
set_env DB_CONNECTION mysql
set_env DB_HOST "${DB_HOST}"
set_env DB_PORT 3306
set_env DB_DATABASE "${DB_DATABASE}"
set_env DB_USERNAME "${DB_USERNAME}"
set_env DB_PASSWORD "${DB_PASSWORD}"
set_env MAIL_MAILER log
set_env MAIL_FROM_ADDRESS "demo@websockets-demo.test"

# Optional overrides, applied only when the environment provides them. Local
# compose leaves these unset, so .env.example's defaults (local / debug) stand;
# the VPS override (deploy/compose.vps.yml) sets production + secure cookies.
[ -n "${APP_ENV}" ]              && set_env APP_ENV "${APP_ENV}"
[ -n "${APP_DEBUG}" ]            && set_env APP_DEBUG "${APP_DEBUG}"
[ -n "${SESSION_SECURE_COOKIE}" ] && set_env SESSION_SECURE_COOKIE "${SESSION_SECURE_COOKIE}"

# Broadcasting via Reverb. Credentials are PINNED to fixed demo values (not the
# random ones reverb:install generated) so the browser-side Echo config in
# resources/js/app.js can hardcode the same key without a Vite build-time
# dependency -- VITE_* vars bake at build, which runs on the host before this.
#
# Server-side, the app and the reverb server talk over the compose network at
# reverb:8080. The browser reaches reverb SAME-ORIGIN through nginx, which
# proxies the /app websocket path to reverb:8080 -- keeping it on the app's own
# origin (:8091) sidesteps mixed-port and CORS grief.
set_env BROADCAST_CONNECTION reverb
# database (not sync): the AI-assistant reply runs as a queued job on the worker
# container, so sends return immediately. MessageSent is ShouldBroadcastNow, so
# chat delivery stays instant regardless of the queue connection.
set_env QUEUE_CONNECTION database
set_env REVERB_APP_ID reverb-chat-demo
set_env REVERB_APP_KEY reverbchatkey
set_env REVERB_APP_SECRET reverbchatsecret
set_env REVERB_HOST reverb
set_env REVERB_PORT 8080
set_env REVERB_SCHEME http
set_env REVERB_SERVER_HOST 0.0.0.0
set_env REVERB_SERVER_PORT 8080

echo "==> waiting for mysql (via PDO: Alpine's mysql-client is MariaDB's and
    fails against MySQL 8 over TLS even when the server is healthy)"
until php -r '
    try { new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_DATABASE"),
                  getenv("DB_USERNAME"), getenv("DB_PASSWORD")); }
    catch (Throwable $e) { exit(1); }' 2>/dev/null; do
  sleep 1
done

echo "==> migrate"
php artisan migrate --force --no-interaction

# Seed only on a fresh database (no messages yet). ChatSeeder's Message::create
# is not idempotent, so re-seeding on every boot -- which happens on the VPS,
# where restart:always + a persistent volume mean the entrypoint reruns -- would
# pile up duplicate chat messages. On a fresh volume this runs exactly once.
MSGS=$(php -r '
    try { $p = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_DATABASE"),
                       getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
          echo $p->query("SELECT COUNT(*) FROM messages")->fetchColumn(); }
    catch (Throwable $e) { echo "fresh"; }' 2>/dev/null)
if [ "$MSGS" = "0" ] || [ "$MSGS" = "fresh" ]; then
  echo "==> seed (fresh db)"
  php artisan db:seed --force --no-interaction
else
  echo "==> seed skipped (messages=$MSGS)"
fi

chmod -R 777 storage bootstrap/cache 2>/dev/null || true

echo "==> ready: ${APP_URL}"
exec "$@"
