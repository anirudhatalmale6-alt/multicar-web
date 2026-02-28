-- Migration: Create page_views table for analytics tracking
CREATE TABLE IF NOT EXISTS page_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(255) NOT NULL,
    page_type ENUM('home','inventory','vehicle','contact','other') NOT NULL DEFAULT 'other',
    vehicle_id INT UNSIGNED NULL,
    visitor_hash CHAR(32) NOT NULL,
    referrer VARCHAR(500) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_visitor_date (visitor_hash, created_at),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_page_type (page_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
