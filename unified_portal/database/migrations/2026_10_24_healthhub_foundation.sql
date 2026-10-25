-- WPU HealthHub foundation (run once on unified DB; mirrors Laravel migration 2026_10_24_000001)

ALTER TABLE `admins`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(191) NULL AFTER `username`,
  ADD INDEX IF NOT EXISTS `admins_email_index` (`email`);

ALTER TABLE `patient_records`
  ADD COLUMN IF NOT EXISTS `transaction_type` VARCHAR(64) NULL AFTER `visit_date`,
  ADD COLUMN IF NOT EXISTS `case_category` VARCHAR(64) NULL AFTER `case_type_id`,
  ADD COLUMN IF NOT EXISTS `case_subcategory` VARCHAR(128) NULL AFTER `case_category`,
  ADD COLUMN IF NOT EXISTS `case_other_specify` VARCHAR(255) NULL AFTER `case_subcategory`,
  ADD COLUMN IF NOT EXISTS `organizational_unit_id` BIGINT UNSIGNED NULL AFTER `department_id`,
  ADD COLUMN IF NOT EXISTS `dependent_of_type` VARCHAR(32) NULL AFTER `organizational_unit_id`,
  ADD COLUMN IF NOT EXISTS `dependent_of_patient_id` BIGINT UNSIGNED NULL AFTER `dependent_of_type`,
  ADD COLUMN IF NOT EXISTS `agency_affiliation` VARCHAR(255) NULL AFTER `dependent_of_patient_id`,
  ADD COLUMN IF NOT EXISTS `chief_complaint` TEXT NULL AFTER `doctor`,
  ADD COLUMN IF NOT EXISTS `remarks` TEXT NULL AFTER `chief_complaint`,
  ADD COLUMN IF NOT EXISTS `examination_data` JSON NULL AFTER `remarks`,
  ADD COLUMN IF NOT EXISTS `consent_record_id` BIGINT UNSIGNED NULL AFTER `examination_data`;

UPDATE `patient_types` SET `type_name` = 'Private Individual' WHERE `type_name` = 'Outsider';

INSERT INTO `patient_types` (`type_name`, `color_code`, `created_at`)
SELECT 'Dependent', '#8E24AA', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `patient_types` WHERE `type_name` = 'Dependent');

INSERT INTO `departments` (`name`, `created_at`)
SELECT 'ASILS', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `departments` WHERE `name` = 'ASILS');

INSERT INTO `departments` (`name`, `created_at`)
SELECT 'BELS', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `departments` WHERE `name` = 'BELS');
