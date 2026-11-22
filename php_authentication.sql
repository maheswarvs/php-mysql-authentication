-- Database for app 4 (Secure User Authentication). MySQL 5.7+ / MariaDB 10.3+.
-- Local XAMPP: import this whole file in phpMyAdmin (Import tab) or run: mysql -u root < database/php_authentication.sql
-- Hosted MySQL: if your provider already created a database for you and won't allow CREATE DATABASE,
-- delete the two lines below that create and select php_authentication, select your own database, then run the rest.

CREATE DATABASE IF NOT EXISTS php_authentication CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE php_authentication;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username   VARCHAR(32)  NOT NULL,
    password   VARCHAR(255) NOT NULL,                       -- password_hash() output, never plaintext
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample user:  username demo_user   password Demo@2026!   (bcrypt hash below)
INSERT INTO users (username, password) VALUES
    ('demo_user', '$2y$12$NQhy3hgWlQ.RWQIwDUf1reBlRnSicZugq1dkdq7TRrhsbskoUcgga')
ON DUPLICATE KEY UPDATE username = username;
