-- Makes draft-backed submissions replayable and prevents duplicate side effects.

SET @submission_key_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_envios' AND COLUMN_NAME = 'submission_key') = 0,
  'ALTER TABLE bitacora_envios ADD COLUMN submission_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER tipo_formulario',
  'SELECT 1'
);
PREPARE submission_key_stmt FROM @submission_key_ddl;
EXECUTE submission_key_stmt;
DEALLOCATE PREPARE submission_key_stmt;

SET @request_hash_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_envios' AND COLUMN_NAME = 'request_hash') = 0,
  'ALTER TABLE bitacora_envios ADD COLUMN request_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER submission_key',
  'SELECT 1'
);
PREPARE request_hash_stmt FROM @request_hash_ddl;
EXECUTE request_hash_stmt;
DEALLOCATE PREPARE request_hash_stmt;

SET @response_status_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_envios' AND COLUMN_NAME = 'response_http_status') = 0,
  'ALTER TABLE bitacora_envios ADD COLUMN response_http_status SMALLINT UNSIGNED NULL AFTER request_hash',
  'SELECT 1'
);
PREPARE response_status_stmt FROM @response_status_ddl;
EXECUTE response_status_stmt;
DEALLOCATE PREPARE response_status_stmt;

SET @response_json_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_envios' AND COLUMN_NAME = 'response_json') = 0,
  'ALTER TABLE bitacora_envios ADD COLUMN response_json JSON NULL AFTER response_http_status',
  'SELECT 1'
);
PREPARE response_json_stmt FROM @response_json_ddl;
EXECUTE response_json_stmt;
DEALLOCATE PREPARE response_json_stmt;

SET @response_completed_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_envios' AND COLUMN_NAME = 'response_completed_at') = 0,
  'ALTER TABLE bitacora_envios ADD COLUMN response_completed_at DATETIME NULL AFTER response_json',
  'SELECT 1'
);
PREPARE response_completed_stmt FROM @response_completed_ddl;
EXECUTE response_completed_stmt;
DEALLOCATE PREPARE response_completed_stmt;

SET @submission_idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bitacora_envios'
    AND INDEX_NAME = 'bitacora_envios_submission_key_unique'
);
SET @submission_idx_ddl := IF(
  @submission_idx_exists = 0,
  'ALTER TABLE bitacora_envios ADD UNIQUE KEY bitacora_envios_submission_key_unique (submission_key)',
  'SELECT 1'
);
PREPARE submission_idx_stmt FROM @submission_idx_ddl;
EXECUTE submission_idx_stmt;
DEALLOCATE PREPARE submission_idx_stmt;
