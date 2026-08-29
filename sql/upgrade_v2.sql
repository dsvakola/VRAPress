ALTER TABLE users
    MODIFY password_hash VARCHAR(255) NOT NULL;

UPDATE users
SET password_hash = '$2y$12$pRO8bgD/eBI9uTDcDeEKUeyQVs37WTtJJ27dt/iV2vrnvYQniYccy'
WHERE username = 'admin';

CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_media_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
