CREATE DATABASE IF NOT EXISTS mi_storyblast;
USE mi_storyblast;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin', 'operator') NOT NULL DEFAULT 'operator',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS platforms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform ENUM('facebook', 'instagram', 'whatsapp') NOT NULL,
    account_name VARCHAR(100),
    access_token_encrypted TEXT,
    page_id VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    token_expires_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS post_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    platform VARCHAR(50),
    caption TEXT,
    media_url VARCHAR(255),
    status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    meta_post_id VARCHAR(100),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
