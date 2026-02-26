<?php
/**
 * MULTICAR — AJAX: Toggle vehicle featured status
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
$csrfToken = $input['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalido']);
    exit;
}

if ($vehicleId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalido']);
    exit;
}

// Toggle
$stmt = db()->prepare("UPDATE vehicles SET featured = NOT featured WHERE id = ?");
$stmt->execute([$vehicleId]);

// Get new value
$stmt = db()->prepare("SELECT featured FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$featured = (bool)$stmt->fetchColumn();

echo json_encode(['success' => true, 'featured' => $featured]);
