<?php
/**
 * MULTICAR — AJAX: Reorder vehicle images
 */
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$vehicleId = (int)($input['vehicle_id'] ?? 0);
$order     = $input['order'] ?? [];
$csrfToken = $input['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalido']);
    exit;
}

if ($vehicleId <= 0 || !is_array($order) || empty($order)) {
    echo json_encode(['success' => false, 'error' => 'Datos invalidos']);
    exit;
}

// Update sort_order for each image
$stmt = db()->prepare("UPDATE vehicle_images SET sort_order = ? WHERE id = ? AND vehicle_id = ?");
foreach ($order as $index => $imageId) {
    $stmt->execute([(int)$index, (int)$imageId, $vehicleId]);
}

echo json_encode(['success' => true]);
