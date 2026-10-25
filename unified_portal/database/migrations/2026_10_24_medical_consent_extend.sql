-- Extends healthhub_consents for official Medical Consent Form (mirrors Laravel 2026_10_24_140000)

ALTER TABLE `healthhub_consents`
  ADD COLUMN IF NOT EXISTS `consent_status` VARCHAR(32) NULL,
  ADD COLUMN IF NOT EXISTS `patient_identifier` VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `patient_type_label` VARCHAR(128) NULL,
  ADD COLUMN IF NOT EXISTS `date_of_birth` DATE NULL,
  ADD COLUMN IF NOT EXISTS `named_patient` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `representative_relationship` VARCHAR(128) NULL,
  ADD COLUMN IF NOT EXISTS `interpreter_name` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `interpreter_signature_path` VARCHAR(512) NULL,
  ADD COLUMN IF NOT EXISTS `interpreter_signed_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `witness_name` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `witness_signature_path` VARCHAR(512) NULL,
  ADD COLUMN IF NOT EXISTS `witness_signed_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `signature_storage_path` VARCHAR(512) NULL,
  ADD COLUMN IF NOT EXISTS `physician_printed_name` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `physician_signature_path` VARCHAR(512) NULL,
  ADD COLUMN IF NOT EXISTS `physician_certified_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `physician_certified_by` VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `idempotency_key` VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `superseded_by_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `withdrawn_at` TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS `withdrawn_by` VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS `form_reference` VARCHAR(64) NULL;

CREATE TABLE IF NOT EXISTS `healthhub_consent_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consent_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `actor` VARCHAR(64) NULL,
  `details` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `healthhub_consent_audit_consent_idx` (`consent_id`, `created_at`)
);
