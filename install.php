<?php
/**
 * MULTICAR — Database Installation Script
 * Run this once to create all tables and default admin user.
 * DELETE THIS FILE after installation.
 */

require_once __DIR__ . '/config.php';

$dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) DEFAULT NULL,
        `role` ENUM('admin','editor') NOT NULL DEFAULT 'editor',
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Vehicles table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `vehicles` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `brand` VARCHAR(100) NOT NULL,
        `model` VARCHAR(100) NOT NULL,
        `version` VARCHAR(150) DEFAULT NULL,
        `year` SMALLINT UNSIGNED NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `mileage` INT UNSIGNED DEFAULT 0,
        `fuel` ENUM('gasolina','diesel','hibrido','electrico','glp') NOT NULL DEFAULT 'gasolina',
        `transmission` ENUM('manual','automatico') NOT NULL DEFAULT 'manual',
        `power_hp` SMALLINT UNSIGNED DEFAULT NULL,
        `doors` TINYINT UNSIGNED DEFAULT 5,
        `color` VARCHAR(50) DEFAULT NULL,
        `body_type` ENUM('sedan','suv','hatchback','coupe','cabrio','familiar','monovolumen','furgoneta','pick-up','otro') DEFAULT 'sedan',
        `description` TEXT DEFAULT NULL,
        `features` TEXT DEFAULT NULL,
        `video_url` VARCHAR(500) DEFAULT NULL,
        `warranty` VARCHAR(150) DEFAULT NULL,
        `sale_type` ENUM('rebu','iva_incluido') NOT NULL DEFAULT 'rebu',
        `badge` VARCHAR(50) DEFAULT NULL,
        `status` ENUM('disponible','reservado','vendido','proximamente') NOT NULL DEFAULT 'disponible',
        `featured` TINYINT(1) NOT NULL DEFAULT 0,
        `meta_title` VARCHAR(255) DEFAULT NULL,
        `meta_description` VARCHAR(500) DEFAULT NULL,
        `views` INT UNSIGNED NOT NULL DEFAULT 0,
        `created_by` INT UNSIGNED DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_brand` (`brand`),
        INDEX `idx_status` (`status`),
        INDEX `idx_featured` (`featured`),
        INDEX `idx_price` (`price`),
        INDEX `idx_year` (`year`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Vehicle images table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `vehicle_images` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `vehicle_id` INT UNSIGNED NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_vehicle` (`vehicle_id`),
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Contact leads table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `leads` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `vehicle_id` INT UNSIGNED DEFAULT NULL,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(30) DEFAULT NULL,
        `email` VARCHAR(150) DEFAULT NULL,
        `message` TEXT DEFAULT NULL,
        `source` VARCHAR(50) DEFAULT 'web',
        `read_status` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Site settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT DEFAULT NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Default settings
    $settings = [
        ['site_phone', '+34 679 96 78 76'],
        ['site_email', 'info@multicar.autos'],
        ['site_address', ''],
        ['site_schedule', 'Lun – Vie: 9:00 – 19:00 | Sáb: 10:00 – 14:00'],
        ['hero_title', 'Encuentra tu próximo vehículo'],
        ['hero_subtitle', 'Compra, venta, alquiler y renting de vehículos con garantía y la confianza de profesionales.'],
        ['business_start_date', '2023-09-01'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`key`, `value`) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }

    // Default admin user (password: Multicar2026!)
    $adminPass = password_hash('Multicar2026!', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (`username`, `password`, `name`, `role`) VALUES (?, ?, ?, 'admin')");
    $stmt->execute(['admin', $adminPass, 'Administrador']);

    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Instalación MULTICAR</title>
    <style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px;background:#f4f6f8}
    .ok{background:#d4edda;border:1px solid #c3e6cb;padding:20px;border-radius:8px;margin:10px 0}
    .warn{background:#fff3cd;border:1px solid #ffeaa7;padding:20px;border-radius:8px;margin:10px 0}
    h1{color:#1B3A5C}</style></head><body>";
    echo "<h1>MULTICAR — Instalación</h1>";
    echo "<div class='ok'><strong>Base de datos creada correctamente.</strong><br>Todas las tablas han sido generadas.</div>";
    echo "<div class='ok'><strong>Usuario administrador creado:</strong><br>Usuario: <code>admin</code><br>Contraseña: <code>Multicar2026!</code></div>";
    echo "<div class='warn'><strong>IMPORTANTE:</strong> Elimina este archivo (<code>install.php</code>) después de la instalación por seguridad.</div>";
    echo "<p><a href='admin/' style='display:inline-block;padding:12px 24px;background:#1B3A5C;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold'>Ir al Panel de Administración →</a></p>";
    echo "</body></html>";

} catch (PDOException $e) {
    echo "<h1>Error de instalación</h1>";
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica las credenciales de la base de datos en <code>config.php</code></p>";
}
