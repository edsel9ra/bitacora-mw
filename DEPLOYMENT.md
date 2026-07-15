# Despliegue En Servidor

Esta guía deja la aplicación lista para producción usando Docker Compose, sin PhpMyAdmin y sin exponer MySQL al host.

## Requisitos

- Servidor Linux con Docker Engine y Docker Compose Plugin.
- Puerto HTTP disponible, por defecto `80`.
- Acceso SMTP válido.
- Acceso SSH al servidor.

## Subida Inicial

1. Copia el proyecto al servidor, por ejemplo en `/opt/bitacora-mw`.
2. No subas un `.env` real al repositorio ni lo compartas por chat/correo.
3. En el servidor crea el archivo de variables:

```bash
cd /opt/bitacora-mw
cp .env.production.example .env
nano .env
```

4. Cambia todas las credenciales `change_this_*`.
5. Deja `APP_ENV_FILE=.env` salvo que uses otro nombre para el archivo de variables.
6. Si el sitio estará detrás de HTTPS, deja `SESSION_SECURE=true`. Si estás probando temporalmente solo por HTTP, usa `SESSION_SECURE=false` hasta activar HTTPS.

## Construcción Y Arranque

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

La imagen de producción instala dependencias Composer durante el build desde `public/scripts/composer.lock`. El servidor necesita salida a internet para descargar dependencias la primera vez. El primer build puede tardar varios minutos porque compila extensiones PHP como `gd`.

## Migraciones De Base De Datos

Ejecuta las migraciones manuales después del primer arranque:

```bash
docker compose -f docker-compose.prod.yml exec -T db sh -lc 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/001_unified_bitacora.sql
docker compose -f docker-compose.prod.yml exec -T db sh -lc 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/002_seed_bitacora_config_json.sql
```

Si ya tienes una base de datos existente, realiza backup antes de aplicar migraciones.

## Verificación

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml exec nginx nginx -t
docker compose -f docker-compose.prod.yml exec app sh -lc "find public -path 'public/scripts/vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l"
docker compose -f docker-compose.prod.yml exec app sh -lc "cd public/scripts && composer validate --strict && composer audit"
```

Luego abre `http://IP_DEL_SERVIDOR` o el dominio configurado.

## HTTPS

Recomendado: usar un proxy inverso con certificados TLS, por ejemplo Nginx Proxy Manager, Traefik, Caddy o Nginx + Certbot.

Cuando HTTPS esté activo:

```env
SESSION_SECURE=true
```

Después reinicia:

```bash
docker compose -f docker-compose.prod.yml up -d
```

## Archivos Persistentes

Producción usa estos volúmenes Docker:

- `bitacora_db_data`: datos MySQL.
- `bitacora_storage`: PDFs, logs PHP y temporales de PDF.
- `bitacora_uploads`: archivos subidos bajo `public/uploads`.

No borres estos volúmenes salvo que tengas backup.

## Backup

Base de datos:

```bash
docker compose -f docker-compose.prod.yml exec -T db sh -lc 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup_bitacora.sql
```

Volúmenes de archivos:

```bash
docker run --rm -v bitacora-mw_bitacora_storage:/data -v "$PWD":/backup alpine tar czf /backup/backup_storage.tar.gz -C /data .
docker run --rm -v bitacora-mw_bitacora_uploads:/data -v "$PWD":/backup alpine tar czf /backup/backup_uploads.tar.gz -C /data .
```

El prefijo real del volumen puede variar según el nombre de la carpeta. Confírmalo con:

```bash
docker volume ls | grep bitacora
```

## Actualización De Código

1. Sube los cambios al servidor.
2. Reconstruye imágenes:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

3. Aplica nuevas migraciones si existen.
4. Revisa logs:

```bash
docker compose -f docker-compose.prod.yml logs -f app nginx
```

## Seguridad Operativa

- No publiques `db` ni `phpmyadmin` en producción.
- No subas `.env`, backups SQL, logs, `storage/`, `public/uploads/` ni `public/scripts/vendor/` al repositorio.
- Mantén `SMTP_VERIFY_TLS=true`.
- Cambia credenciales por valores únicos y largos.
- Haz backup antes de actualizar.
