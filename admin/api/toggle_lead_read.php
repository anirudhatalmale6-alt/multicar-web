<?php
/**
 * MULTICAR — AJAX: Toggle lead read status
 */
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$leadId = (int)($input['lead_id'] ?? 0);
$csrfToken = $input['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalido']);
    exit;
}

if ($leadId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalido']);
    exit;
}

// Toggle
$stmt = db()->prepare("UPDATE leads SET read_status = NOT read_status WHERE id = ?");
$stmt->execute([$leadId]);

// Get new value
$stmt = db()->prepare("SELECT read_status FROM leads WHERE id = ?");
$stmt->execute([$leadId]);
$readStatus = (bool)$stmt->fetchColumn();

echo json_encode(['success' => true, 'read_status' => $readStatus]);
