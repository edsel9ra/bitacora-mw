-- Adds an index on bitacora_pdfs(expires_at) for the daily cleanup query.
-- Idempotent and compatible with MySQL 8.

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bitacora_pdfs'
      AND INDEX_NAME = 'idx_bitacora_pdfs_expires_at'
);

SET @idx_ddl := IF(
    @idx_exists = 0,
    'ALTER TABLE bitacora_pdfs ADD INDEX idx_bitacora_pdfs_expires_at (expires_at)',
    'SELECT 1'
);

PREPARE idx_stmt FROM @idx_ddl;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
