<?php
/**
 * MULTICAR — Vehicle Import API (from InverCar)
 *
 * Accepts JSON POST with API key authentication.
 * Creates vehicle in 'borrador' mode with photos.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/init.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// API key authentication
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$validKey = getSetting('invercar_api_key', '');

if ($apiKey === '' || $validKey === '' || !hash_equals($validKey, $apiKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'API key inválida.']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

// Required fields
$brand = trim($input['brand'] ?? '');
$model = trim($input['model'] ?? '');
$year  = (int)($input['year'] ?? 0);

if ($brand === '' || $model === '' || $year < 1900) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Marca, modelo y año son obligatorios.']);
    exit;
}

// Map fields
$version      = trim($input['version'] ?? '');
$price        = floatval($input['price'] ?? 0);
$mileage      = (int)($input['mileage'] ?? 0);
$fuel         = $input['fuel'] ?? 'gasolina';
$transmission = $input['transmission'] ?? 'manual';
$body_type    = $input['body_type'] ?? 'sedan';
$description  = trim($input['description'] ?? '');
$status       = 'disponible';
$slug         = generateSlug($brand, $model, $year);

// Validate enums
$validFuels = ['gasolina','diesel','hibrido','electrico','glp'];
$validTrans = ['manual','automatico'];
$validBodies = ['sedan','suv','hatchback','coupe','cabrio','familiar','monovolumen','furgoneta','pick-up','otro'];
if (!in_array($fuel, $validFuels)) $fuel = 'gasolina';
if (!in_array($transmission, $validTrans)) $transmission = 'manual';
if (!in_array($body_type, $validBodies)) $body_type = 'sedan';

try {
    // Check if published_status column exists
    $hasPublishedStatus = false;
    try {
        $colCheck = db()->query("SHOW COLUMNS FROM vehicles LIKE 'published_status'");
        $hasPublishedStatus = $colCheck->rowCount() > 0;
    } catch (Exception $e) {}

    // Insert vehicle as borrador
    if ($hasPublishedStatus) {
        $stmt = db()->prepare("INSERT INTO vehicles
            (slug, brand, model, version, year, price, mileage, fuel, transmission,
             body_type, description, status, published_status, featured, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $slug, $brand, $model, $version, $year, $price, $mileage,
            $fuel, $transmission, $body_type, $description,
            $status, 'borrador', 0, 0
        ]);
    } else {
        $stmt = db()->prepare("INSERT INTO vehicles
            (slug, brand, model, version, year, price, mileage, fuel, transmission,
             body_type, description, status, featured, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $slug, $brand, $model, $version, $year, $price, $mileage,
            $fuel, $transmission, $body_type, $description,
            $status, 0, 0
        ]);
    }
    $vehicleId = (int)db()->lastInsertId();

    // Handle photo URLs — download from InverCar
    $photosImported = 0;
    $photoUrls = $input['photo_urls'] ?? [];

    if (!empty($photoUrls) && is_array($photoUrls)) {
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0775, true);
        }

        foreach ($photoUrls as $i => $url) {
            $url = trim($url);
            if ($url === '') continue;

            // Download image
            $ctx = stream_context_create(['http' => ['timeout' => 15]]);
            $imageData = @file_get_contents($url, false, $ctx);
            if ($imageData === false) continue;

            // Detect extension from content type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($imageData);
            $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $ext = $extMap[$mime] ?? null;
            if (!$ext) continue;

            $filename = $vehicleId . '_' . uniqid() . '.' . $ext;
            $dest = UPLOAD_DIR . $filename;

            if (file_put_contents($dest, $imageData) !== false) {
                $isCover = ($i === 0) ? 1 : 0;
                $stmtImg = db()->prepare("INSERT INTO vehicle_images (vehicle_id, filename, sort_order, is_cover) VALUES (?,?,?,?)");
                $stmtImg->execute([$vehicleId, $filename, $i, $isCover]);
                $photosImported++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Vehículo importado como borrador.',
        'vehicle_id' => $vehicleId,
        'slug' => $slug,
        'photos_imported' => $photosImported
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear vehículo: ' . $e->getMessage()]);
}
