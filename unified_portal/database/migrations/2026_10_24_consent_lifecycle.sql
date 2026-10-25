-- Consent withdraw/supersede version history (mirrors Laravel 2026_10_24_150000)

ALTER TABLE `healthhub_consents`
  ADD COLUMN IF NOT EXISTS `superseded_from_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `withdrawal_reason` TEXT NULL;

CREATE TABLE IF NOT EXISTS `healthhub_consent_versions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `consent_id` BIGINT UNSIGNED NOT NULL,
  `version_kind` VARCHAR(32) NOT NULL,
  `snapshot_json` LONGTEXT NOT NULL,
  `reason` TEXT NULL,
  `replaced_by_consent_id` BIGINT UNSIGNED NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `healthhub_consent_versions_consent_idx` (`consent_id`, `created_at`)
);
