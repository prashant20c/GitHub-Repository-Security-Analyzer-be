#!/bin/sh

set -eu

cd /var/www/backend

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -z "${APP_KEY:-}" ]; then
  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --ansi
  fi
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php -r '
  $host = getenv("DB_HOST") ?: "mysql";
  $port = (int) (getenv("DB_PORT") ?: 3306);
  $db = getenv("DB_DATABASE") ?: "security_analyzer";
  $user = getenv("DB_USERNAME") ?: "security_analyzer";
  $pass = getenv("DB_PASSWORD") ?: "security_analyzer";
  for ($i = 0; $i < 60; $i++) {
      try {
          new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass);
          exit(0);
      } catch (Throwable $e) {
          sleep(2);
      }
  }
  fwrite(STDERR, "Database is unavailable.\n");
  exit(1);
  '
  php artisan migrate --force --ansi
fi

exec "$@"
