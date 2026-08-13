-- Adds expiration metadata for generated PDF download tokens.
-- Idempotent and compatible with MySQL 8 (ADD COLUMN IF NOT EXISTS is MariaDB-only).

SET @expires_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bitacora_pdfs'
      AND COLUMN_NAME = 'expires_at'
);

SET @expires_ddl := IF(
    @expires_exists = 0,
    'ALTER TABLE bitacora_pdfs ADD COLUMN expires_at DATETIME NULL AFTER creado_en',
    'SELECT 1'
);

PREPARE expires_stmt FROM @expires_ddl;
EXECUTE expires_stmt;
DEALLOCATE PREPARE expires_stmt;

UPDATE bitacora_pdfs
SET expires_at = DATE_ADD(creado_en, INTERVAL 90 DAY)
WHERE expires_at IS NULL;
