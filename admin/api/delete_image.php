<?php
/**
 * MULTICAR — AJAX: Delete vehicle image
 */
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageId = (int)($input['image_id'] ?? 0);
$csrfToken = $input['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalido']);
    exit;
}

if ($imageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalido']);
    exit;
}

$result = deleteVehicleImage($imageId);

echo json_encode(['success' => $result]);
