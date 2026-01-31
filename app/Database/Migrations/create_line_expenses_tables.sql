-- ============================================
-- LINE Bot Expenses - Database Migration
-- ============================================

-- Table: Title/Vendor Keywords (replaces data.js titile_expenses)
CREATE TABLE IF NOT EXISTS `rise_line_expenses_title_keywords` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `keyword` VARCHAR(100) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `sort` INT DEFAULT 0,
  `deleted` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_keyword` (`keyword`),
  INDEX `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Project Keywords (replaces data.js client_and_project)
CREATE TABLE IF NOT EXISTS `rise_line_expenses_project_keywords` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `keyword` VARCHAR(100) NOT NULL,
  `client_name` VARCHAR(500) NOT NULL,
  `project_name` VARCHAR(500) DEFAULT '',
  `project_id` INT UNSIGNED DEFAULT 0,
  `is_monthly_project` TINYINT(1) DEFAULT 0,
  `sort` INT DEFAULT 0,
  `deleted` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_keyword` (`keyword`),
  INDEX `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: User Mappings (LINE user ID to Rise user ID)
CREATE TABLE IF NOT EXISTS `rise_user_mappings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `line_user_id` VARCHAR(100) NOT NULL,
  `line_display_name` VARCHAR(255) DEFAULT '',
  `rise_user_id` INT UNSIGNED DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_line_user` (`line_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Seed Data: Title Keywords
-- ============================================
INSERT INTO `rise_line_expenses_title_keywords` (`keyword`, `title`, `sort`) VALUES
('โฮม', 'บริษัท โฮม โปรดักส์ เซ็นเตอร์ จำกัด (มหาชน)', 1),
('นิ่ม', 'บริษัท นิ่มเอ็กซ์เพรส จำกัด', 2),
('แฟลช', 'บริษัท แฟลช โฮม โอเปอร์เรชั่น จำกัด', 3),
('ลีส', 'ลีสซิ่งกสิกรไทย', 4),
('ไท', 'บริษัท ซีอาร์ซี ไทวัสดุ จำกัด', 5),
('โฮมโปร', 'บริษัท โฮม โปรดักส์ เซ็นเตอร์ จำกัด', 6),
('j', 'บริษัท เอ็น พี เจ โลจิสติกส์ จำกัด ( J&T Express )', 7),
('เอ็มพี', 'เอ็มพี วัสดุ', 8),
('การทาง', 'การทางพิเศษแห่งประเทศไทย', 9),
('โอลิมปัส', 'บริษัท โอลิมมปัส ออยส์ จำกัด', 10),
('บางจาก', 'บริษัท บางจากกรีนเนท จำกัด', 11),
('ปิโตรเลียม', 'บริษัท ปิโตรเลียม (ช่างอากาศอุทิศ) จำกัด', 12),
('home', 'HOME HARDWARE', 13),
('ไปร', 'QUICK SERVICE (ไปรษณีย์ไทย)', 14),
('โยฟ้า', 'ร้านโยธินการไฟฟ้า', 15),
('โยก่อ', 'ร้านโยธินวัสดุก่อสร้าง', 16),
('ล่า', 'Lalamove', 17),
('นิ่มซี่', 'บริษัท นิ่มซี่เส็งขนส่ง 1988 จำกัด', 18),
('วัน', 'วันสต๊อกโฮม', 19),
('ที', 'บริษัท ทีทีพี (ประเทศไทย)', 20);

-- ============================================
-- Seed Data: Project Keywords
-- ============================================
INSERT INTO `rise_line_expenses_project_keywords` (`keyword`, `client_name`, `project_name`, `is_monthly_project`, `sort`) VALUES
('ณ', 'บริษัท ณัฏฐกรณ์ เอ็นจิเนียริ่ง แอนด์ เทรดดิ้ง จำกัด', 'บจ. ณัฏฐกรณ์ เอ็นจิเนียริ่ง แอนด์ เทรดดิ้ง จำกัด (บ้านกำแพงแสน)', 0, 1),
('9', 'RUBYSHOP บ้านเลขที่ 9', 'งานสร้าง รื้อถอน โรงงานใหม่ บ้านเลขที่ 9', 0, 2),
('ruby', 'RUBYSHOP PART.,LTD.', '', 1, 3);

-- Table: Category Keywords (replaces data.js catagory_expenses)
CREATE TABLE IF NOT EXISTS `rise_line_expenses_category_keywords` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `keyword` VARCHAR(100) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `sort` INT DEFAULT 0,
  `deleted` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_keyword` (`keyword`),
  INDEX `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Seed Data: Category Keywords
-- ============================================
INSERT INTO `rise_line_expenses_category_keywords` (`keyword`, `category_id`, `sort`) VALUES
('42', 42, 1),
('06', 41, 2),
('40', 40, 3),
('39', 39, 4),
('38', 38, 5),
('37', 37, 6),
('36', 36, 7),
('35', 35, 8),
('34', 34, 9),
('33', 33, 10),
('32', 32, 11),
('31', 31, 12),
('30', 30, 13),
('29', 29, 14),
('28', 28, 15),
('27', 27, 16),
('26', 26, 17),
('25', 25, 18),
('24', 24, 19),
('23', 23, 20),
('22', 22, 21),
('21', 21, 22),
('20', 20, 23),
('19', 19, 24),
('18', 18, 25),
('17', 17, 26),
('16', 16, 27),
('15', 15, 28),
('14', 14, 29),
('13', 13, 30),
('12', 12, 31),
('11', 11, 32),
('10', 10, 33),
('09', 9, 34),
('08', 8, 35),
('07', 7, 36),
('05', 5, 37),
('04', 4, 38),
('03', 3, 39),
('01', 1, 40),
('43', 43, 41),
('44', 44, 42);

-- ============================================
-- Seed Data: Default Settings
-- ============================================
INSERT INTO `rise_settings` (`setting_name`, `setting_value`, `type`, `deleted`) VALUES
('line_expenses_enabled', '0', 'app', 0),
('line_expenses_channel_access_token', '', 'app', 0),
('line_expenses_channel_secret', '', 'app', 0),
('line_expenses_report_target_id', '', 'app', 0),
('line_expenses_report_target_type', 'user', 'app', 0),
('line_expenses_daily_report_enabled', '1', 'app', 0),
('line_expenses_daily_report_time', '20:00', 'app', 0),
('line_expenses_monthly_report_enabled', '1', 'app', 0),
('line_expenses_monthly_report_days', '1,monday,saturday', 'app', 0),
('line_expenses_monthly_report_time', '20:01', 'app', 0),
('line_expenses_rooms', '', 'app', 0),
('line_expenses_default_category_id', '24', 'app', 0)
ON DUPLICATE KEY UPDATE `setting_name` = VALUES(`setting_name`);
