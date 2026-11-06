-- iCare Service — MySQL / MariaDB tables. Import this once (phpMyAdmin → Import), or open /setup.php.

CREATE TABLE IF NOT EXISTS site_content (
  `key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `data` LONGTEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  updated_by INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
