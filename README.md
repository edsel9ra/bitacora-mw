# Bitácora Mister Wings

Aplicación PHP para registro, envío y generación de bitácoras por empresa/sede. El flujo principal usa la vista unificada `public/vistas/bitacora.php` y el handler `public/scripts/send_bitacora.php`.

## Arranque Local

1. Copia `.env.example` a `.env` y ajusta las variables reales.
2. Ejecuta `docker compose up --build`.
3. Abre `http://localhost:8080`.
4. PhpMyAdmin queda disponible en `http://localhost:8081` solo desde localhost.

## Despliegue En Servidor

Para producción usa `docker-compose.prod.yml`, que no incluye PhpMyAdmin, no expone MySQL y construye imágenes con el código y dependencias dentro.

Guía completa: [`DEPLOYMENT.md`](DEPLOYMENT.md).

## Variables Requeridas

- `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` para conexión a BD.
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASSWORD`, `SMTP_FROM` para envío de correos.
- `BITACORA_STORAGE_PATH` para almacenar PDFs fuera de `public`.
- `SESSION_SECURE=true` en producción con HTTPS.

## Seguridad

- No versionar `.env`, logs, uploads, storage ni `vendor/`.
- Rotar credenciales si alguna vez estuvieron en el repositorio o en respaldos.
- Los endpoints de envío requieren sesión activa, empresa autorizada y token CSRF.
- Los PDFs se guardan fuera de `public` y se descargan mediante `scripts/download_bitacora.php` con sesión válida.

## Dependencias PHP

Actualmente Composer está en `public/scripts`. Para instalar dependencias:

```bash
docker compose exec app sh -lc "cd public/scripts && composer install"
```

Próximo refactor recomendado: mover `composer.json` a la raíz y dejar `vendor/` fuera del webroot.

## Base De Datos

Las migraciones manuales viven en `database/migrations/`. La migración `001_unified_bitacora.sql` amplía `usuarios_login.password` a `VARCHAR(255)` y crea tablas para parametrizar sedes, destinatarios y configuración por empresa.

`bitacora_empresa_config.config_json` permite agregar campos al formulario unificado sin editar PHP. Ejemplo:

```json
{
  "schema": "operational_current",
  "dynamic_fields": [
    {
      "name": "temperatura_nevera",
      "label": "Temperatura de nevera",
      "type": "number",
      "section": "Operaciones",
      "required": true,
      "suffix": "°C",
      "sedes": ["PANCE"]
    }
  ]
}
```

Tipos iniciales soportados: `text`, `textarea`, `number`, `date`, `time`, `select`, `yes_no`.
