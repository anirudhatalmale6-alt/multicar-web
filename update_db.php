<?php
/**
 * MULTICAR — Database Update Script
 * Adds new columns: warranty, sale_type
 * Adds new setting: business_start_date
 * Run this once, then DELETE this file.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';

try {
    $pdo = Database::connect();

    // Add warranty column
    try {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN `warranty` VARCHAR(150) DEFAULT NULL AFTER `video_url`");
        echo "<p style='color:green'>+ Columna 'warranty' añadida correctamente.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange'>= Columna 'warranty' ya existe.</p>";
        } else {
            echo "<p style='color:red'>Error warranty: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Add sale_type column
    try {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN `sale_type` ENUM('rebu','iva_incluido') NOT NULL DEFAULT 'rebu' AFTER `warranty`");
        echo "<p style='color:green'>+ Columna 'sale_type' añadida correctamente.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange'>= Columna 'sale_type' ya existe.</p>";
        } else {
            echo "<p style='color:red'>Error sale_type: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Add business_start_date setting
    $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`key`, `value`) VALUES (?, ?)");
    $stmt->execute(['business_start_date', '2023-09-01']);
    echo "<p style='color:green'>+ Configuración 'business_start_date' añadida.</p>";

    echo "<br><p style='font-weight:bold;color:#1B3A5C'>Base de datos actualizada correctamente. ELIMINA este archivo (update_db.php) por seguridad.</p>";
    echo "<p><a href='admin/' style='display:inline-block;padding:12px 24px;background:#1B3A5C;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold'>Ir al Panel →</a></p>";

} catch (PDOException $e) {
    echo "<h1>Error</h1>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
