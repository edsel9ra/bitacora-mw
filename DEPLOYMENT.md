# Despliegue En Servidor

Esta guía deja la aplicación lista para producción usando Docker Compose, sin PhpMyAdmin y sin exponer MySQL al host.

## Requisitos

- Servidor Linux con Docker Engine y Docker Compose Plugin.
- Proxy inverso/plataforma que enrute tráfico al servicio `nginx` en el puerto interno `80`.
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

Luego abre el dominio configurado en tu proxy/plataforma.

## Exposición HTTP

`docker-compose.prod.yml` no publica `ports` en el host. Expone el puerto interno `80` del servicio `nginx` para que Coolify u otro proxy inverso lo enrute sin chocar con el puerto `80` del servidor.

En Coolify, configura el dominio/proxy apuntando exactamente al servicio `nginx` y al puerto interno `80`. No apuntes al servicio `app` ni al puerto `9000`: `app` ejecuta PHP-FPM, no HTTP, y el proxy terminará en `504 Gateway Timeout`.

En la pantalla del recurso Compose, asigna el dominio al servicio `nginx`. Como `nginx` escucha en el puerto interno `80`, el dominio puede quedar como `https://tu-dominio.com` sin sufijo de puerto.

El compose de producción usa la red default del stack para que el proxy de Coolify pueda alcanzar el servicio `nginx`. No agregues una red custom para estos servicios salvo que también configures el proxy para esa red.

El endpoint de salud HTTP es:

```text
/healthz
```

Debe responder `ok` desde el servicio `nginx`.

El endpoint para validar que `nginx` también llega a PHP-FPM es:

```text
/php-healthz.php
```

También debe responder `ok`.

## Diagnóstico De 504 En Coolify

Si el despliegue termina pero el navegador muestra `504 Gateway Timeout`, revisa primero la configuración del dominio en Coolify:

- El dominio debe estar asignado al servicio `nginx`, no a `app`.
- El puerto interno debe ser `80`, no `9000`.
- El endpoint `/healthz` debe responder `ok`.
- El endpoint `/php-healthz.php` debe responder `ok`.

Comandos útiles desde el servidor o consola de Coolify:

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs --tail=120 nginx app
docker compose -f docker-compose.prod.yml exec nginx wget -q -O - http://127.0.0.1/healthz
docker compose -f docker-compose.prod.yml exec nginx wget -q -O - http://127.0.0.1/php-healthz.php
```

Si `/healthz` responde dentro del contenedor pero el dominio externo sigue en `504`, el problema está en el enrutamiento del proxy/Coolify hacia el servicio o puerto incorrecto.

Si `/healthz` responde pero `/php-healthz.php` falla, el problema está entre `nginx` y PHP-FPM (`app:9000`) o en el arranque del servicio `app`.

Si vas a ejecutar sin proxy inverso ni Coolify, agrega un override local no versionado, por ejemplo `docker-compose.expose.yml`:

```yaml
services:
  nginx:
    ports:
      - "80:80"
```

Y arranca con:

```bash
docker compose -f docker-compose.prod.yml -f docker-compose.expose.yml up -d --build
```

## Diagnóstico De MySQL Unhealthy

Si el panel de despliegue reporta `container db is unhealthy`, revisa los logs del servicio `db`:

```bash
docker compose -f docker-compose.prod.yml logs --tail=120 db
```

Casos frecuentes:

- Primera inicialización lenta en servidores pequeños: espera unos minutos y revisa si el contenedor termina quedando `healthy`.
- `MYSQL_USER=root`: no uses `root` como usuario de aplicación; usa un usuario como `bitacora_user` y deja `MYSQL_ROOT_PASSWORD` solo para administración.
- Variables vacías o mal escapadas: confirma que `.env` tenga `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` y `MYSQL_ROOT_PASSWORD` con valores reales.
- Contraseñas con caracteres especiales en `.env`: si usas `#`, espacios o comillas, envuélvelas entre comillas.

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
