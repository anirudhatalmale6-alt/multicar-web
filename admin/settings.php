<?php
/**
 * MULTICAR — Admin Site Settings
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();

// Admin only
if (!isAdmin()) {
    flash('error', 'No tienes permisos para acceder a esta seccion.');
    redirect(SITE_URL . '/admin/');
}

define('ADMIN_LOADED', true);
$adminTitle = 'Configuracion';

// Settings keys
$settingsKeys = [
    'site_phone', 'site_email', 'site_address', 'site_schedule',
    'hero_title', 'hero_subtitle',
    'social_facebook', 'social_instagram', 'social_tiktok',
];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Token de seguridad invalido.');
    } else {
        $stmt = db()->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($settingsKeys as $key) {
            $val = trim($_POST[$key] ?? '');
            $stmt->execute([$key, $val]);
        }
        // Clear cached settings
        flash('success', 'Configuracion guardada correctamente.');
    }
    redirect(SITE_URL . '/admin/settings.php');
}

// Load current values
$settings = [];
$rows = db()->query("SELECT `key`, `value` FROM settings")->fetchAll();
foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}

include __DIR__ . '/includes/admin_header.php';
?>

<form method="POST">
    <?= csrfField() ?>

    <!-- Contact Info -->
    <div class="card mb-3">
        <div class="card-header">
            <h2>Informacion de contacto</h2>
        </div>
        <div class="card-body">
            <div class="form-row mb-2">
                <div class="form-group">
                    <label for="site_phone">Telefono</label>
                    <input type="text" id="site_phone" name="site_phone" class="form-control"
                           value="<?= e($settings['site_phone'] ?? '') ?>"
                           placeholder="+34 679 96 78 76">
                </div>
                <div class="form-group">
                    <label for="site_email">Email</label>
                    <input type="email" id="site_email" name="site_email" class="form-control"
                           value="<?= e($settings['site_email'] ?? '') ?>"
                           placeholder="info@multicar.autos">
                </div>
            </div>
            <div class="form-group">
                <label for="site_address">Direccion</label>
                <input type="text" id="site_address" name="site_address" class="form-control"
                       value="<?= e($settings['site_address'] ?? '') ?>"
                       placeholder="Calle Ejemplo, 123, 28001 Madrid">
            </div>
            <div class="form-group mb-0">
                <label for="site_schedule">Horario</label>
                <input type="text" id="site_schedule" name="site_schedule" class="form-control"
                       value="<?= e($settings['site_schedule'] ?? '') ?>"
                       placeholder="Lun - Vie: 9:00 - 19:00 | Sab: 10:00 - 14:00">
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="card mb-3">
        <div class="card-header">
            <h2>Pagina principal — Hero</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="hero_title">Titulo principal</label>
                <input type="text" id="hero_title" name="hero_title" class="form-control"
                       value="<?= e($settings['hero_title'] ?? '') ?>"
                       placeholder="Encuentra tu proximo vehiculo">
                <div class="hint">Se muestra como titulo grande en la cabecera de la pagina principal</div>
            </div>
            <div class="form-group mb-0">
                <label for="hero_subtitle">Subtitulo</label>
                <textarea id="hero_subtitle" name="hero_subtitle" class="form-control" rows="3"
                          placeholder="Compra, venta, alquiler y renting..."><?= e($settings['hero_subtitle'] ?? '') ?></textarea>
                <div class="hint">Texto que aparece debajo del titulo principal</div>
            </div>
        </div>
    </div>

    <!-- Social Media -->
    <div class="card mb-3">
        <div class="card-header">
            <h2>Redes sociales</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="social_facebook">Facebook</label>
                <input type="url" id="social_facebook" name="social_facebook" class="form-control"
                       value="<?= e($settings['social_facebook'] ?? '') ?>"
                       placeholder="https://facebook.com/multicar">
            </div>
            <div class="form-group">
                <label for="social_instagram">Instagram</label>
                <input type="url" id="social_instagram" name="social_instagram" class="form-control"
                       value="<?= e($settings['social_instagram'] ?? '') ?>"
                       placeholder="https://instagram.com/multicar">
            </div>
            <div class="form-group mb-0">
                <label for="social_tiktok">TikTok</label>
                <input type="url" id="social_tiktok" name="social_tiktok" class="form-control"
                       value="<?= e($settings['social_tiktok'] ?? '') ?>"
                       placeholder="https://tiktok.com/@multicar">
            </div>
        </div>
    </div>

    <div class="d-flex justify-between items-center">
        <span></span>
        <button type="submit" class="btn btn-gold" style="min-width:200px;justify-content:center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Guardar configuracion
        </button>
    </div>
</form>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
