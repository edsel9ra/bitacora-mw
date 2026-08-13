-- Preserves the original response JSON byte order for faithful idempotent replay.

ALTER TABLE bitacora_envios
  MODIFY response_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
