#!/bin/sh
set -eu

compose_file="${BITACORA_COMPOSE_FILE:-docker-compose.yml}"
compose_env_file="${BITACORA_COMPOSE_ENV_FILE:-}"

compose() {
    if [ -n "$compose_env_file" ]; then
        docker compose --env-file "$compose_env_file" -f "$compose_file" "$@"
    else
        docker compose -f "$compose_file" "$@"
    fi
}

compose config --quiet

if [ "$compose_file" = "docker-compose.prod.yml" ]; then
    compose build app nginx
    compose run --rm --no-deps app sh -lc "find public database scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l"
    compose run --rm --no-deps app php tests/run.php
    compose run --rm --no-deps app php tests/section_mail_privacy_test.php
    compose run --rm app sh -lc "php database/migrate.php && php tests/integration.php"
else
    compose run --rm --no-deps app composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts
    compose run --rm --no-deps app sh -lc "find public database scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l"
    compose run --rm --no-deps app php tests/run.php
    compose run --rm --no-deps app php tests/section_mail_privacy_test.php
    compose run --rm app sh -lc "php database/migrate.php && php tests/integration.php"
    compose run --rm --no-deps app composer validate --strict
    compose run --rm --no-deps app composer audit
fi

compose run --rm --no-deps nginx nginx -t
