-- Adds application roles for form administration.
-- Idempotent and compatible with MySQL 8 (ADD COLUMN IF NOT EXISTS is MariaDB-only).

SET @roles_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios_login'
      AND COLUMN_NAME = 'rol'
);

SET @roles_ddl := IF(
    @roles_exists = 0,
    "ALTER TABLE usuarios_login ADD COLUMN rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario' AFTER idEmpresa",
    'SELECT 1'
);

PREPARE roles_stmt FROM @roles_ddl;
EXECUTE roles_stmt;
DEALLOCATE PREPARE roles_stmt;

-- Example after applying the migration:
-- UPDATE usuarios_login SET rol = 'admin' WHERE usuario = 'usuario_admin';
