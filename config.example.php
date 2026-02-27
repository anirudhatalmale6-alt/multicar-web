<?php
/**
 * MULTICAR — Configuration
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'multicar_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_URL', 'https://multicar.autos');
define('SITE_NAME', 'MULTICAR');
define('SITE_TAGLINE', 'Compra · Venta · Alquiler · Renting');

// WhatsApp
define('WHATSAPP_NUMBER', '34679967876');
define('WHATSAPP_MESSAGE', '¡Hola! Me interesa un vehículo de vuestra web.');

// Social Media
define('SOCIAL_FACEBOOK', '#');
define('SOCIAL_INSTAGRAM', '#');
define('SOCIAL_TIKTOK', '#');

// Uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/vehicles/');
define('UPLOAD_URL', SITE_URL . '/uploads/vehicles/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Pagination
define('VEHICLES_PER_PAGE', 12);

// Session
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// Timezone
date_default_timezone_set('Europe/Madrid');
