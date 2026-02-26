<?php
/**
 * MULTICAR — AJAX: Set vehicle cover image
 */
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageId   = (int)($input['image_id'] ?? 0);
$vehicleId = (int)($input['vehicle_id'] ?? 0);
$csrfToken = $input['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalido']);
    exit;
}

if ($imageId <= 0 || $vehicleId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalido']);
    exit;
}

// Verify image belongs to vehicle
$stmt = db()->prepare("SELECT COUNT(*) FROM vehicle_images WHERE id = ? AND vehicle_id = ?");
$stmt->execute([$imageId, $vehicleId]);
if ($stmt->fetchColumn() == 0) {
    echo json_encode(['success' => false, 'error' => 'Imagen no encontrada']);
    exit;
}

// Remove cover from all images of this vehicle
$stmt = db()->prepare("UPDATE vehicle_images SET is_cover = 0 WHERE vehicle_id = ?");
$stmt->execute([$vehicleId]);

// Set this image as cover
$stmt = db()->prepare("UPDATE vehicle_images SET is_cover = 1 WHERE id = ?");
$stmt->execute([$imageId]);

echo json_encode(['success' => true]);
