-- Migration: Add published_status field to vehicles table
-- Allows controlling visibility: borrador (draft), activo (published), no_activo (hidden)
-- Only vehicles with published_status='activo' are shown on public site

ALTER TABLE vehicles
ADD COLUMN published_status ENUM('borrador','activo','no_activo') NOT NULL DEFAULT 'activo'
AFTER status;

-- Existing vehicles keep 'activo' (already visible)
-- Vehicles imported from InverCar will be created as 'borrador'

-- API key for InverCar integration
INSERT INTO settings (`key`, `value`) VALUES ('invercar_api_key', '3fe89860d7ea9fc224aed84cdbb78504d707116cdb5227058a8157d772a934d6')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
