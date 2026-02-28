<?php
/**
 * MULTICAR — AI Vehicle Description Generator
 * Generates professional, attractive HTML descriptions for vehicles
 */
require_once __DIR__ . '/../../includes/init.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST; // fallback

$brand       = trim($input['brand'] ?? '');
$model       = trim($input['model'] ?? '');
$version     = trim($input['version'] ?? '');
$year        = (int)($input['year'] ?? 0);
$fuel        = trim($input['fuel'] ?? '');
$transmission = trim($input['transmission'] ?? '');
$powerHp     = (int)($input['power_hp'] ?? 0);
$color       = trim($input['color'] ?? '');
$mileage     = (int)str_replace(['.', ','], '', $input['mileage'] ?? '0');
$bodyType    = trim($input['body_type'] ?? '');

if ($brand === '' || $model === '') {
    echo json_encode(['error' => 'Marca y modelo son obligatorios']);
    exit;
}

$vehicleName = $brand . ' ' . $model;
if ($version) $vehicleName .= ' ' . $version;

$fuelLabels = [
    'gasolina' => 'gasolina', 'diesel' => 'diésel', 'hibrido' => 'híbrido',
    'electrico' => 'eléctrico', 'glp' => 'GLP'
];
$fuelText = $fuelLabels[$fuel] ?? $fuel;

$transText = $transmission === 'automatico' ? 'automática' : 'manual';

$bodyLabels = [
    'sedan' => 'berlina', 'suv' => 'SUV', 'hatchback' => 'compacto', 'coupe' => 'coupé',
    'cabrio' => 'descapotable', 'familiar' => 'familiar', 'monovolumen' => 'monovolumen',
    'furgoneta' => 'furgoneta', 'pick-up' => 'pick-up', 'otro' => 'vehículo'
];
$bodyText = $bodyLabels[$bodyType] ?? 'vehículo';

// Build professional description
$intro = [];
$intro[] = "Te presentamos este magnífico <strong>$vehicleName</strong>";
if ($year) $intro[] = "del año <strong>$year</strong>";
$introText = implode(' ', $intro) . '.';

$details = [];
if ($mileage > 0) {
    $kmFormatted = number_format($mileage, 0, ',', '.');
    if ($mileage < 50000) {
        $details[] = "Con tan solo <strong>$kmFormatted km</strong>, este $bodyText se encuentra en un estado excepcional.";
    } elseif ($mileage < 100000) {
        $details[] = "Con <strong>$kmFormatted km</strong> recorridos, este $bodyText mantiene un excelente estado general.";
    } else {
        $details[] = "Con <strong>$kmFormatted km</strong>, un vehículo bien mantenido y listo para muchos más kilómetros.";
    }
}

if ($fuel) {
    if ($fuel === 'hibrido') {
        $details[] = "Motor <strong>$fuelText</strong> que combina eficiencia y prestaciones, reduciendo el consumo y las emisiones.";
    } elseif ($fuel === 'electrico') {
        $details[] = "Motorización <strong>100% eléctrica</strong> con cero emisiones, ideal para la movilidad sostenible.";
    } elseif ($fuel === 'diesel') {
        $details[] = "Motorización <strong>$fuelText</strong> que ofrece un consumo reducido y una gran autonomía.";
    } else {
        $details[] = "Motor <strong>$fuelText</strong> con un rendimiento excelente en cualquier situación.";
    }
}

if ($powerHp > 0) {
    $details[] = "Sus <strong>$powerHp CV</strong> de potencia garantizan una conducción dinámica y segura.";
}

$details[] = "Transmisión <strong>$transText</strong> para una experiencia de conducción cómoda y fluida.";

if ($color) {
    $details[] = "En un elegante color <strong>$color</strong> que realza sus líneas.";
}

$closing = "No pierdas esta oportunidad. <strong>Contacta con nosotros</strong> para concertar una visita y probarlo sin compromiso.";

$description = "<p>$introText</p>";
$description .= "<p>" . implode(' ', $details) . "</p>";
$description .= "<p>$closing</p>";

// Build features list based on body type and specs
$features = '<p><strong>Equipamiento destacado:</strong></p><ul>';

$commonFeatures = [
    'sedan' => ['Climatización automática', 'Sistema de navegación', 'Asientos calefactados', 'Sensor de aparcamiento trasero', 'Cámara de visión trasera', 'Control de crucero', 'Volante multifunción', 'Faros LED', 'Bluetooth y conectividad', 'Sistema Start/Stop'],
    'suv' => ['Tracción integral', 'Climatización bizona', 'Sistema de navegación', 'Cámara de visión 360°', 'Sensor de aparcamiento', 'Barras de techo', 'Control de crucero adaptativo', 'Faros LED', 'Asistente de arranque en pendiente', 'Bluetooth y Apple CarPlay/Android Auto'],
    'hatchback' => ['Climatización automática', 'Pantalla táctil multimedia', 'Sensor de aparcamiento', 'Control de crucero', 'Volante multifunción', 'Faros LED', 'Bluetooth y conectividad', 'Llantas de aleación', 'Sistema Start/Stop', 'Cierre centralizado'],
    'coupe' => ['Climatización automática', 'Asientos deportivos', 'Sistema de navegación', 'Faros LED adaptativos', 'Llantas de aleación deportivas', 'Volante multifunción en cuero', 'Control de tracción', 'Bluetooth y conectividad', 'Modo de conducción Sport', 'Escape deportivo'],
];

$featureList = $commonFeatures[$bodyType] ?? $commonFeatures['sedan'];

if ($transmission === 'automatico') {
    $featureList[] = 'Cambio automático';
}
if ($fuel === 'hibrido' || $fuel === 'electrico') {
    $featureList[] = 'Recuperación de energía en frenada';
    $featureList[] = 'Modo ECO';
}

// Take up to 10 features
$featureList = array_slice(array_unique($featureList), 0, 10);

foreach ($featureList as $feat) {
    $features .= "<li>$feat</li>";
}
$features .= '</ul>';
$features .= '<p><em>Equipamiento sujeto a verificación. Consulta con nuestro equipo para más detalles.</em></p>';

echo json_encode([
    'success' => true,
    'description' => $description,
    'features' => $features
]);
