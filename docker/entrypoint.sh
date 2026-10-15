#!/bin/sh
ENV_FILE=/var/www/html/api/.env
EXAMPLE=/var/www/html/api/.env.example
if [ ! -f "$ENV_FILE" ] && [ -f "$EXAMPLE" ]; then
    cp "$EXAMPLE" "$ENV_FILE" || true
fi

mkdir -p /tmp/fitpower-sessions
chmod 777 /tmp/fitpower-sessions || true

if [ -x /usr/local/bin/docker-php-entrypoint ]; then
    exec /usr/local/bin/docker-php-entrypoint apache2-foreground
fi
exec apache2-foreground
