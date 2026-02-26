<?php
/**
 * MULTICAR — Lead / Contact Form API
 *
 * Accepts POST with: name, phone, email, message, vehicle_id (optional), vehicle_name (optional), subject (optional)
 * Returns JSON: { success: bool, message: string }
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/init.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// CSRF check
if (!verifyCsrf()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.']);
    exit;
}

// Rate limiting (simple session-based)
$lastSubmit = $_SESSION['last_lead_submit'] ?? 0;
if (time() - $lastSubmit < 30) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Por favor, espera 30 segundos antes de enviar otro mensaje.']);
    exit;
}

// Validate required fields
$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$subject = trim($_POST['subject'] ?? 'informacion');
$vehicleId   = (int)($_POST['vehicle_id'] ?? 0);
$vehicleName = trim($_POST['vehicle_name'] ?? '');

$errors = [];

if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'El nombre es obligatorio (máx. 100 caracteres).';
}

if ($phone === '' || mb_strlen($phone) > 20) {
    $errors[] = 'El teléfono es obligatorio (máx. 20 caracteres).';
}

// Basic phone validation: at least 6 digits
if ($phone !== '' && !preg_match('/\d{6,}/', preg_replace('/\D/', '', $phone))) {
    $errors[] = 'El teléfono no parece válido.';
}

if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)) {
    $errors[] = 'El email no es válido.';
}

if (mb_strlen($message) > 2000) {
    $errors[] = 'El mensaje es demasiado largo (máx. 2000 caracteres).';
}

// Honeypot check (if field present in form)
if (!empty($_POST['website'])) {
    // Bot detected — silently accept but don't save
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente.']);
    exit;
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Save to database ──
try {
    $stmt = db()->prepare("
        INSERT INTO leads (vehicle_id, vehicle_name, name, phone, email, subject, message, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $vehicleId > 0 ? $vehicleId : null,
        $vehicleName !== '' ? $vehicleName : null,
        $name,
        $phone,
        $email !== '' ? $email : null,
        $subject,
        $message !== '' ? $message : null,
        $_SERVER['REMOTE_ADDR'] ?? '',
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);

    $_SESSION['last_lead_submit'] = time();

    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado correctamente! Nos pondremos en contacto contigo lo antes posible.']);

} catch (PDOException $e) {
    // Log error (in production, log to file)
    error_log('MULTICAR lead insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar el mensaje. Inténtalo de nuevo más tarde.']);
}
