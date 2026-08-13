-- Normalizes legacy base tables that may have been created with utf8mb3.

ALTER TABLE razones_sociales
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE sedes
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE usuarios_login
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
