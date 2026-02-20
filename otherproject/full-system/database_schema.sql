-- User mappings with array support and duty roles
CREATE TABLE IF NOT EXISTS user_mappings_arr (
  id INT AUTO_INCREMENT PRIMARY KEY,
  line_user_id VARCHAR(255) NOT NULL,
  rise_user_id INT NOT NULL,
  nick_name VARCHAR(255),
  line_display_name VARCHAR(255),
  duty_role ENUM('boss', 'staff') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_line_user_id (line_user_id),
  INDEX idx_rise_user_id (rise_user_id),
  INDEX idx_duty_role (duty_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing data from user_mappings
INSERT INTO user_mappings_arr (line_user_id, rise_user_id, nick_name, line_display_name, duty_role, is_active)
SELECT 
  line_user_id, 
  rise_user_id, 
  nick_name, 
  line_display_name,
  'staff' as duty_role,
  1 as is_active
FROM user_mappings
WHERE NOT EXISTS (
  SELECT 1 FROM user_mappings_arr 
  WHERE user_mappings_arr.line_user_id = user_mappings.line_user_id
);
