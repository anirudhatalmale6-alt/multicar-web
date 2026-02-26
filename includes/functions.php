<?php
/**
 * MULTICAR — Helper Functions
 */

function db(): PDO {
    return Database::connect();
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function generateSlug(string $brand, string $model, int $year): string {
    $base = slugify("$brand $model $year");
    $slug = $base;
    $i = 1;
    $stmt = db()->prepare("SELECT COUNT(*) FROM vehicles WHERE slug = ?");
    while (true) {
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() == 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/admin/login.php');
    }
}

function formatPrice(float $price): string {
    return number_format($price, 0, ',', '.') . ' €';
}

function formatMileage(int $km): string {
    return number_format($km, 0, ',', '.') . ' km';
}

function getVehicleCover(int $vehicleId): ?string {
    $stmt = db()->prepare("SELECT filename FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_cover DESC, sort_order ASC LIMIT 1");
    $stmt->execute([$vehicleId]);
    $img = $stmt->fetchColumn();
    return $img ? UPLOAD_URL . $img : null;
}

function getVehicleImages(int $vehicleId): array {
    $stmt = db()->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_cover DESC, sort_order ASC");
    $stmt->execute([$vehicleId]);
    return $stmt->fetchAll();
}

function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $cache[$key] = $stmt->fetchColumn() ?: $default;
    }
    return $cache[$key];
}

function fuelLabel(string $fuel): string {
    $labels = [
        'gasolina' => 'Gasolina',
        'diesel' => 'Diésel',
        'hibrido' => 'Híbrido',
        'electrico' => 'Eléctrico',
        'glp' => 'GLP',
    ];
    return $labels[$fuel] ?? $fuel;
}

function transmissionLabel(string $t): string {
    return $t === 'automatico' ? 'Automático' : 'Manual';
}

function bodyTypeLabel(string $bt): string {
    $labels = [
        'sedan' => 'Sedán', 'suv' => 'SUV', 'hatchback' => 'Hatchback',
        'coupe' => 'Coupé', 'cabrio' => 'Cabrio', 'familiar' => 'Familiar',
        'monovolumen' => 'Monovolumen', 'furgoneta' => 'Furgoneta',
        'pick-up' => 'Pick-Up', 'otro' => 'Otro',
    ];
    return $labels[$bt] ?? $bt;
}

function statusLabel(string $s): string {
    $labels = ['disponible' => 'Disponible', 'reservado' => 'Reservado', 'vendido' => 'Vendido'];
    return $labels[$s] ?? $s;
}

function statusColor(string $s): string {
    $colors = ['disponible' => '#22c55e', 'reservado' => '#f59e0b', 'vendido' => '#ef4444'];
    return $colors[$s] ?? '#888';
}

function getWhatsAppLink(?string $vehicleName = null): string {
    $msg = $vehicleName
        ? "¡Hola! Me interesa el vehículo: $vehicleName. ¿Podrían darme más información?"
        : WHATSAPP_MESSAGE;
    return 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . urlencode($msg);
}

function getYouTubeEmbedUrl(string $url): ?string {
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
        '/youtu\.be\/([a-zA-Z0-9_-]+)/',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
    }
    return null;
}

function uploadVehicleImage(array $file, int $vehicleId, int $sortOrder = 0, bool $isCover = false): bool {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $vehicleId . '_' . uniqid() . '.' . strtolower($ext);
    $dest = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    $stmt = db()->prepare("INSERT INTO vehicle_images (vehicle_id, filename, sort_order, is_cover) VALUES (?, ?, ?, ?)");
    $stmt->execute([$vehicleId, $filename, $sortOrder, $isCover ? 1 : 0]);
    return true;
}

function deleteVehicleImage(int $imageId): bool {
    $stmt = db()->prepare("SELECT filename FROM vehicle_images WHERE id = ?");
    $stmt->execute([$imageId]);
    $filename = $stmt->fetchColumn();
    if (!$filename) return false;

    $path = UPLOAD_DIR . $filename;
    if (file_exists($path)) unlink($path);

    $stmt = db()->prepare("DELETE FROM vehicle_images WHERE id = ?");
    $stmt->execute([$imageId]);
    return true;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlash(): array {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function getBrands(): array {
    return db()->query("SELECT DISTINCT brand FROM vehicles WHERE status = 'disponible' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
}

function getYears(): array {
    return db()->query("SELECT DISTINCT year FROM vehicles WHERE status = 'disponible' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
}
