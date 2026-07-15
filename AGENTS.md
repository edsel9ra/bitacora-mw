# AGENTS.md

## Project Shape
- Plain PHP app served by Docker: Nginx document root is `public/`, PHP-FPM runs as service `app`, MySQL is service `db`.
- Main entrypoints are `public/index.php`, `public/bd/login.php`, unified view `public/vistas/bitacora.php`, and unified POST handler `public/scripts/send_bitacora.php`.
- Legacy company views/scripts still exist as fallback/reference, but post-login routing goes through `public/vistas/pag_inicio.php` to `bitacora.php`.
- Nginx blocks direct access to `public/config/`, `public/phpmailer/`, `public/scripts/vendor/`, `storage/`, logs, `.env`, and Composer files; keep sensitive helpers there or outside `public`.

## Environment And Secrets
- Env vars are loaded from root `.env` by `public/config/env.php`; Docker also injects the same vars from `docker-compose.yml`.
- Never hardcode DB/SMTP credentials. Use `MYSQL_*`, `SMTP_*`, `SESSION_*`, and `BITACORA_STORAGE_PATH` from `.env.example`.
- SMTP TLS verification defaults to on via `SMTP_VERIFY_TLS=true`; do not disable it in PHP code.

## Auth, CSRF, And Company Routing
- Protected views must `require_once __DIR__ . '/../config/security.php';` then call `app_require_login()` or `app_require_login($empresaId)` and include `<?php echo app_csrf_input(); ?>` inside forms.
- Protected POST handlers must call `app_require_post_login($empresaId, true)` before reading `$_POST` when returning JSON.
- Unified company behavior lives in `public/config/bitacora.php`; keep sedes, extras, recipients, and report type there instead of branching in views.
- Add form fields dynamically through `bitacora_empresa_config.config_json` using `dynamic_fields`; the app falls back to the PHP schema in `public/config/bitacora.php` when JSON is empty.
- Legacy mapping for reference: `1 send_mesg/mes_group`, `2 send_msg/mes_soluciones_hcqc`, `3 send_lesg/les_group`, `4 send_inva/inversiones_valquin`, `5 send_lebor/lebor_sas`, `6 send_sup/supervisiones`, `7 send_trilogia/mes_trilogia`, `8 send_test/mes_test`.
- Login still accepts legacy MD5 hashes in `public/bd/login.php` for existing users, but new password work should use `password_hash`/`password_verify`.

## Mail And PDFs
- Configure PHPMailer only through `public/config/mailer.php` and `app_configure_mailer($mail)`; do not repeat SMTP setup in send scripts.
- PDFs must go through `bit_storage_base_dir()` in `public/scripts/bitacora_helpers.php`; do not write generated PDFs under `public/`.
- Download stored PDFs through `public/scripts/download_bitacora.php`, which validates session, company, and path traversal.

## Dependencies
- Composer is intentionally under `public/scripts`, not the repo root.
- Install PHP dependencies with `docker compose exec app sh -lc "cd public/scripts && composer install"`.
- If `public/scripts/composer.json` changes, keep `public/scripts/composer.lock` in sync and run Composer validation/audit.
- DB migrations are SQL files under `database/migrations/`; apply them manually to MySQL when schema changes are needed.

## Verification Commands
- PHP syntax, excluding Composer vendor: `docker compose run --rm --no-deps app sh -lc "find public -path 'public/scripts/vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l"`
- Composer validation and audit: `docker compose run --rm --no-deps app sh -lc "cd public/scripts && composer validate --strict && composer audit"`
- Nginx config: `docker compose run --rm --no-deps nginx nginx -t`
- Compose config: `docker compose config --quiet`

## Local Runtime Notes
- Start locally with `docker compose up --build`; app is `http://localhost:8080`, PhpMyAdmin is bound to `127.0.0.1:8081`.
- Use Docker for PHP/Composer commands; PHP may not be installed on the host.
- Runtime files belong in `storage/`, `public/uploads/`, or Docker volumes and are ignored by `.gitignore`.
