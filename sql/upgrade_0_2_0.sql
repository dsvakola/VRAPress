-- VRAPress upgrade 0.2.0: Menus + Comments

CREATE TABLE IF NOT EXISTS menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  location VARCHAR(60) NOT NULL DEFAULT 'primary',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id INT UNSIGNED NOT NULL,
  item_type ENUM('page','post','category','custom') NOT NULL DEFAULT 'custom',
  ref_id INT UNSIGNED NULL,
  label VARCHAR(180) NOT NULL,
  url VARCHAR(255) NOT NULL,
  target_blank TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 10,
  created_at DATETIME NOT NULL,
  INDEX idx_menu_items_menu (menu_id),
  INDEX idx_menu_items_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  is_admin_reply TINYINT(1) NOT NULL DEFAULT 0,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  comment_text TEXT NOT NULL,
  status ENUM('pending','approved','spam','deleted') NOT NULL DEFAULT 'pending',
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  approved_at DATETIME NULL,
  INDEX idx_comments_post (post_id),
  INDEX idx_comments_status (status),
  INDEX idx_comments_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default primary menu
INSERT INTO menus (name, location, created_at)
SELECT 'Primary Menu', 'primary', NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE location = 'primary');
