-- Select the target database in phpMyAdmin before importing this migration.
-- This migration creates the location tables only. Import
-- ../hostinger_ph_locations_data.sql afterward to populate their records.

CREATE TABLE IF NOT EXISTS ph_regions (
    region_code VARCHAR(10) PRIMARY KEY,
    region_name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    region_code VARCHAR(10) NOT NULL,
    province_name VARCHAR(150) NOT NULL,
    UNIQUE KEY uq_ph_province_region_name (region_code, province_name),
    CONSTRAINT fk_ph_provinces_region FOREIGN KEY (region_code) REFERENCES ph_regions(region_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_localities (
    locality_id INT AUTO_INCREMENT PRIMARY KEY,
    province_id INT NOT NULL,
    locality_name VARCHAR(150) NOT NULL,
    UNIQUE KEY uq_ph_locality_province_name (province_id, locality_name),
    CONSTRAINT fk_ph_localities_province FOREIGN KEY (province_id) REFERENCES ph_provinces(province_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ph_barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    locality_id INT NOT NULL,
    barangay_name VARCHAR(180) NOT NULL,
    UNIQUE KEY uq_ph_barangay_locality_name (locality_id, barangay_name),
    CONSTRAINT fk_ph_barangays_locality FOREIGN KEY (locality_id) REFERENCES ph_localities(locality_id)
) ENGINE=InnoDB;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND COLUMN_NAME='region_code')=0,
    'ALTER TABLE user_addresses ADD COLUMN region_code VARCHAR(10) NULL AFTER street', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND COLUMN_NAME='province_id')=0,
    'ALTER TABLE user_addresses ADD COLUMN province_id INT NULL AFTER region_code', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND COLUMN_NAME='locality_id')=0,
    'ALTER TABLE user_addresses ADD COLUMN locality_id INT NULL AFTER province_id', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND COLUMN_NAME='barangay_id')=0,
    'ALTER TABLE user_addresses ADD COLUMN barangay_id INT NULL AFTER locality_id', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND CONSTRAINT_NAME='fk_user_addresses_region')=0,
    'ALTER TABLE user_addresses ADD CONSTRAINT fk_user_addresses_region FOREIGN KEY (region_code) REFERENCES ph_regions(region_code)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND CONSTRAINT_NAME='fk_user_addresses_province')=0,
    'ALTER TABLE user_addresses ADD CONSTRAINT fk_user_addresses_province FOREIGN KEY (province_id) REFERENCES ph_provinces(province_id)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND CONSTRAINT_NAME='fk_user_addresses_locality')=0,
    'ALTER TABLE user_addresses ADD CONSTRAINT fk_user_addresses_locality FOREIGN KEY (locality_id) REFERENCES ph_localities(locality_id)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND CONSTRAINT_NAME='fk_user_addresses_barangay')=0,
    'ALTER TABLE user_addresses ADD CONSTRAINT fk_user_addresses_barangay FOREIGN KEY (barangay_id) REFERENCES ph_barangays(barangay_id)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

-- Legacy text columns stay nullable only on upgraded databases so existing rows remain readable during migration.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_addresses' AND COLUMN_NAME='barangay')>0,
    'ALTER TABLE user_addresses MODIFY barangay VARCHAR(100) NULL, MODIFY city VARCHAR(100) NULL, MODIFY province VARCHAR(100) NULL', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS application_addresses (
    application_address_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    house_no VARCHAR(50) NULL,
    street VARCHAR(100) NOT NULL,
    region_code VARCHAR(10) NOT NULL,
    province_id INT NOT NULL,
    locality_id INT NOT NULL,
    barangay_id INT NOT NULL,
    postal_code VARCHAR(20) NULL,
    CONSTRAINT fk_application_addresses_application FOREIGN KEY (application_id) REFERENCES staff_applications(application_id) ON DELETE CASCADE,
    CONSTRAINT fk_application_addresses_region FOREIGN KEY (region_code) REFERENCES ph_regions(region_code),
    CONSTRAINT fk_application_addresses_province FOREIGN KEY (province_id) REFERENCES ph_provinces(province_id),
    CONSTRAINT fk_application_addresses_locality FOREIGN KEY (locality_id) REFERENCES ph_localities(locality_id),
    CONSTRAINT fk_application_addresses_barangay FOREIGN KEY (barangay_id) REFERENCES ph_barangays(barangay_id)
) ENGINE=InnoDB;
