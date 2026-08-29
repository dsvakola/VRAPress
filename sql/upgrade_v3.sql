ALTER TABLE users
    MODIFY password_hash VARCHAR(255) NOT NULL;

UPDATE users
SET password_hash = '$2y$12$6rm2OJFPmirjapp1kdhuselmzuyiywZ0tAyylQhD5PC33Zx9TAz5.'
WHERE username = 'admin';

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value, updated_at)
VALUES ('site_title', 'Vidyasagar Academy', NOW())
ON DUPLICATE KEY UPDATE updated_at = updated_at;

INSERT INTO settings (setting_key, setting_value, updated_at)
VALUES ('site_tagline', 'Lightweight custom PHP website', NOW())
ON DUPLICATE KEY UPDATE updated_at = updated_at;
