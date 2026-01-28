
CREATE DATABASE IF NOT EXISTS kaimail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kaimail;

-- ============================================
-- Bảng domains - Quản lý tên miền
-- ============================================
CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Bảng emails - Email tạm thời
-- ============================================
CREATE TABLE IF NOT EXISTS emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    name_type ENUM('vn', 'en', 'custom') DEFAULT 'en',
    expiry_type ENUM('30days', '1year', '2years', 'forever') DEFAULT 'forever',
    expires_at DATETIME NULL,
    is_expired TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_domain_id (domain_id),
    INDEX idx_expires (expires_at),
    INDEX idx_expired (is_expired)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Bảng messages - Email nhận được
-- ============================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) DEFAULT '',
    subject VARCHAR(500) DEFAULT '(No subject)',
    body_text LONGTEXT,
    body_html LONGTEXT,
    message_id VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE,
    INDEX idx_email_id (email_id),
    INDEX idx_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Bảng settings - Cấu hình hệ thống 
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert dữ liệu mặc định
-- ============================================

-- Domain mặc định
INSERT IGNORE INTO domains (domain, is_active) VALUES 
('kaishop.id.vn', 1);

-- Cấu hình hệ thống
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('webhook_secret', '65a276de438f97d2b4496724e59d18d443168d3d2ed'),
('default_domain', 'kaishop.id.vn');