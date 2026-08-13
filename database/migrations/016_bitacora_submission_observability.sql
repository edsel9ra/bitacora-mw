-- Distinguishes outbox jobs and exposes ambiguous synchronous deliveries.

SET @delivery_started_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_envios' AND COLUMN_NAME = 'delivery_started_at') = 0,
  'ALTER TABLE bitacora_envios ADD COLUMN delivery_started_at DATETIME NULL AFTER response_completed_at',
  'SELECT 1'
);
PREPARE delivery_started_stmt FROM @delivery_started_ddl;
EXECUTE delivery_started_stmt;
DEALLOCATE PREPARE delivery_started_stmt;

SET @queue_job_type_ddl := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_email_queue' AND COLUMN_NAME = 'job_type') = 0,
  'ALTER TABLE bitacora_email_queue ADD COLUMN job_type ENUM(''main'',''section'') NOT NULL DEFAULT ''main'' AFTER attachments_json',
  'SELECT 1'
);
PREPARE queue_job_type_stmt FROM @queue_job_type_ddl;
EXECUTE queue_job_type_stmt;
DEALLOCATE PREPARE queue_job_type_stmt;

SET @queue_type_idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bitacora_email_queue'
    AND INDEX_NAME = 'bitacora_email_queue_envio_type_idx'
);
SET @queue_type_idx_ddl := IF(
  @queue_type_idx_exists = 0,
  'ALTER TABLE bitacora_email_queue ADD KEY bitacora_email_queue_envio_type_idx (idEnvio, job_type, estado)',
  'SELECT 1'
);
PREPARE queue_type_idx_stmt FROM @queue_type_idx_ddl;
EXECUTE queue_type_idx_stmt;
DEALLOCATE PREPARE queue_type_idx_stmt;
