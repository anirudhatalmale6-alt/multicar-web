<?php
/**
 * MULTICAR — Inventory Listing
 */
require_once __DIR__ . '/includes/init.php';

// ── Page config ──
$pageTitle       = 'Inventario — ' . SITE_NAME;
$pageDescription = 'Explora nuestro inventario completo de vehículos usados. Coches de todas las marcas, modelos y precios con garantía.';
$activePage      = 'inventario';
$headerSolid     = true;

// ── Collect filter params (all sanitized) ──
$filterMarca       = trim($_GET['marca'] ?? '');
$filterModelo      = trim($_GET['modelo'] ?? '');
$filterYearMin     = (int)($_GET['year_min'] ?? 0);
$filterYearMax     = (int)($_GET['year_max'] ?? 0);
$filterPrecioMin   = (float)($_GET['precio_min'] ?? 0);
$filterPrecioMax   = (float)($_GET['precio_max'] ?? 0);
$filterKmMax       = (int)($_GET['km_max'] ?? 0);
$filterCombustible = trim($_GET['combustible'] ?? '');
$filterTransmision = trim($_GET['transmision'] ?? '');
$filterCarroceria  = trim($_GET['carroceria'] ?? '');
$filterEstado      = trim($_GET['estado'] ?? '');
$orden             = trim($_GET['orden'] ?? 'recientes');
$pagina            = max(1, (int)($_GET['pagina'] ?? 1));

// ── Build WHERE conditions ──
$where  = ['1=1'];
$params = [];

if ($filterMarca !== '') {
    $where[]  = 'v.brand = ?';
    $params[] = $filterMarca;
}

if ($filterModelo !== '') {
    $where[]  = 'v.model LIKE ?';
    $params[] = '%' . $filterModelo . '%';
}

if ($filterYearMin > 0) {
    $where[]  = 'v.year >= ?';
    $params[] = $filterYearMin;
}

if ($filterYearMax > 0) {
    $where[]  = 'v.year <= ?';
    $params[] = $filterYearMax;
}

if ($filterPrecioMin > 0) {
    $where[]  = 'v.price >= ?';
    $params[] = $filterPrecioMin;
}

if ($filterPrecioMax > 0) {
    $where[]  = 'v.price <= ?';
    $params[] = $filterPrecioMax;
}

if ($filterKmMax > 0) {
    $where[]  = 'v.mileage <= ?';
    $params[] = $filterKmMax;
}

if ($filterCombustible !== '') {
    $where[]  = 'v.fuel = ?';
    $params[] = $filterCombustible;
}

if ($filterTransmision !== '') {
    $where[]  = 'v.transmission = ?';
    $params[] = $filterTransmision;
}

if ($filterCarroceria !== '') {
    $where[]  = 'v.body_type = ?';
    $params[] = $filterCarroceria;
}

if ($filterEstado !== '') {
    $where[]  = 'v.status = ?';
    $params[] = $filterEstado;
} else {
    // By default show only available
    $where[] = "v.status IN ('disponible', 'reservado', 'proximamente')";
}

// Only show published vehicles on public site
$where[] = "v.published_status = 'activo'";

$whereSQL = implode(' AND ', $where);

// ── ORDER BY ──
$orderMap = [
    'recientes'   => 'v.created_at DESC',
    'precio_asc'  => 'v.price ASC',
    'precio_desc' => 'v.price DESC',
    'anio_desc'   => 'v.year DESC',
    'anio_asc'    => 'v.year ASC',
    'km_asc'      => 'v.mileage ASC',
];
$orderSQL = $orderMap[$orden] ?? 'v.created_at DESC';

// ── Count total ──
$stmtCount = db()->prepare("SELECT COUNT(*) FROM vehicles v WHERE $whereSQL");
$stmtCount->execute($params);
$totalResults = (int)$stmtCount->fetchColumn();
$totalPages   = max(1, ceil($totalResults / VEHICLES_PER_PAGE));
$pagina       = min($pagina, $totalPages);
$offset       = ($pagina - 1) * VEHICLES_PER_PAGE;

// ── Fetch vehicles ──
$stmtVehicles = db()->prepare("
    SELECT v.*,
        (SELECT vi.filename FROM vehicle_images vi WHERE vi.vehicle_id = v.id ORDER BY vi.is_cover DESC, vi.sort_order ASC LIMIT 1) AS cover_image
    FROM vehicles v
    WHERE $whereSQL
    ORDER BY $orderSQL
    LIMIT " . (int)VEHICLES_PER_PAGE . " OFFSET " . (int)$offset
);
$stmtVehicles->execute($params);
$vehicles = $stmtVehicles->fetchAll();

// ── Get filter options (for sidebar) ──
$brands   = getBrands();
$years    = getYears();
$fuels    = ['gasolina', 'diesel', 'hibrido', 'electrico', 'glp'];
$transmissions = ['manual', 'automatico'];
$bodyTypes = ['sedan', 'suv', 'hatchback', 'coupe', 'cabrio', 'familiar', 'monovolumen', 'furgoneta', 'pick-up', 'otro'];

// ── Helper to keep current filters in URL ──
function buildFilterUrl(array $overrides = []): string {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    unset($params['pagina']);
    $qs = http_build_query($params);
    return SITE_URL . '/inventario' . ($qs ? '?' . $qs : '');
}

trackPageView('inventory');
require_once __DIR__ . '/includes/header.php';
?>

    <!-- ═══ PAGE BANNER ═══ -->
    <section class="page-banner">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= SITE_URL ?>/">Inicio</a>
                <span class="sep">/</span>
                <span>Inventario</span>
            </div>
            <h1>Nuestro Inventario</h1>
            <p>Encuentra el vehículo perfecto entre nuestra selección.</p>
        </div>
    </section>

    <!-- ═══ INVENTORY CONTENT ═══ -->
    <section style="background: var(--off-white);">
        <div class="container">
            <!-- Mobile filter toggle -->
            <button class="filter-toggle-btn" id="filterToggle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                Mostrar filtros
            </button>

            <div class="inventory-layout">
                <!-- SIDEBAR FILTERS -->
                <aside class="filter-sidebar" id="filterSidebar">
                    <h3>Filtrar vehículos</h3>
                    <form method="GET" action="<?= SITE_URL ?>/inventario">
                        <!-- Brand -->
                        <div class="filter-group">
                            <label>Marca</label>
                            <select name="marca">
                                <option value="">Todas</option>
                                <?php foreach ($brands as $b): ?>
                                <option value="<?= e($b) ?>"<?= $filterMarca === $b ? ' selected' : '' ?>><?= e($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Model -->
                        <div class="filter-group">
                            <label>Modelo</label>
                            <input type="text" name="modelo" placeholder="Ej: A3, Serie 3..." value="<?= e($filterModelo) ?>">
                        </div>

                        <!-- Year range -->
                        <div class="filter-group">
                            <label>Año</label>
                            <div class="filter-row">
                                <select name="year_min">
                                    <option value="">Desde</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?= (int)$y ?>"<?= $filterYearMin === (int)$y ? ' selected' : '' ?>><?= (int)$y ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="year_max">
                                    <option value="">Hasta</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?= (int)$y ?>"<?= $filterYearMax === (int)$y ? ' selected' : '' ?>><?= (int)$y ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Price range -->
                        <div class="filter-group">
                            <label>Precio</label>
                            <div class="filter-row">
                                <input type="number" name="precio_min" placeholder="Mínimo" value="<?= $filterPrecioMin > 0 ? (int)$filterPrecioMin : '' ?>" min="0" step="500">
                                <input type="number" name="precio_max" placeholder="Máximo" value="<?= $filterPrecioMax > 0 ? (int)$filterPrecioMax : '' ?>" min="0" step="500">
                            </div>
                        </div>

                        <!-- Fuel -->
                        <div class="filter-group">
                            <label>Combustible</label>
                            <select name="combustible">
                                <option value="">Todos</option>
                                <?php foreach ($fuels as $f): ?>
                                <option value="<?= e($f) ?>"<?= $filterCombustible === $f ? ' selected' : '' ?>><?= fuelLabel($f) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Transmission -->
                        <div class="filter-group">
                            <label>Transmisión</label>
                            <select name="transmision">
                                <option value="">Todas</option>
                                <?php foreach ($transmissions as $t): ?>
                                <option value="<?= e($t) ?>"<?= $filterTransmision === $t ? ' selected' : '' ?>><?= transmissionLabel($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Body type -->
                        <div class="filter-group">
                            <label>Carrocería</label>
                            <select name="carroceria">
                                <option value="">Todas</option>
                                <?php foreach ($bodyTypes as $bt): ?>
                                <option value="<?= e($bt) ?>"<?= $filterCarroceria === $bt ? ' selected' : '' ?>><?= bodyTypeLabel($bt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="filter-group">
                            <label>Estado</label>
                            <select name="estado">
                                <option value="">Disponibles</option>
                                <option value="disponible"<?= $filterEstado === 'disponible' ? ' selected' : '' ?>>Disponible</option>
                                <option value="reservado"<?= $filterEstado === 'reservado' ? ' selected' : '' ?>>Reservado</option>
                                <option value="vendido"<?= $filterEstado === 'vendido' ? ' selected' : '' ?>>Vendido</option>
                            </select>
                        </div>

                        <!-- Keep current sort order -->
                        <?php if ($orden !== 'recientes'): ?>
                        <input type="hidden" name="orden" value="<?= e($orden) ?>">
                        <?php endif; ?>

                        <button type="submit" class="filter-btn">Aplicar filtros</button>
                        <a href="<?= SITE_URL ?>/inventario" class="filter-reset">Limpiar filtros</a>
                    </form>
                </aside>

                <!-- RESULTS -->
                <div class="inventory-results">
                    <div class="results-toolbar">
                        <p class="results-count">
                            Mostrando <strong><?= $totalResults ?></strong> vehículo<?= $totalResults !== 1 ? 's' : '' ?>
                        </p>
                        <div class="results-sort">
                            <label>Ordenar por:</label>
                            <select id="sortSelect">
                                <option value="recientes"<?= $orden === 'recientes' ? ' selected' : '' ?>>Más recientes</option>
                                <option value="precio_asc"<?= $orden === 'precio_asc' ? ' selected' : '' ?>>Precio: menor a mayor</option>
                                <option value="precio_desc"<?= $orden === 'precio_desc' ? ' selected' : '' ?>>Precio: mayor a menor</option>
                                <option value="anio_desc"<?= $orden === 'anio_desc' ? ' selected' : '' ?>>Año: más nuevo</option>
                                <option value="anio_asc"<?= $orden === 'anio_asc' ? ' selected' : '' ?>>Año: más antiguo</option>
                                <option value="km_asc"<?= $orden === 'km_asc' ? ' selected' : '' ?>>Kilómetros: menor a mayor</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($vehicles)): ?>
                    <!-- No results -->
                    <div class="no-results">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            <line x1="8" y1="8" x2="14" y2="14"/><line x1="14" y1="8" x2="8" y2="14"/>
                        </svg>
                        <h3>No encontramos vehículos</h3>
                        <p>Prueba a modificar los filtros de búsqueda o contacta con nosotros para que busquemos tu coche ideal.</p>
                        <a href="<?= SITE_URL ?>/inventario" class="btn-secondary" style="margin-top:20px">Ver todo el inventario</a>
                    </div>
                    <?php else: ?>

                    <div class="vehicles-grid">
                        <?php foreach ($vehicles as $v): ?>
                        <div class="vehicle-card reveal">
                            <a href="<?= SITE_URL ?>/vehiculo/<?= e($v['slug']) ?>">
                                <div class="vehicle-card-img">
                                    <?php if ($v['cover_image']): ?>
                                    <img src="<?= UPLOAD_URL . e($v['cover_image']) ?>" alt="<?= e($v['brand'] . ' ' . $v['model']) ?>" loading="lazy">
                                    <?php else: ?>
                                    <div class="img-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($v['status'] === 'proximamente'): ?>
                                    <span class="vehicle-badge badge-proximamente">Próximamente</span>
                                    <?php elseif ($v['featured']): ?>
                                    <span class="vehicle-badge">Destacado</span>
                                    <?php elseif ($v['status'] === 'reservado'): ?>
                                    <span class="vehicle-badge badge-reservado">Reservado</span>
                                    <?php elseif ($v['status'] === 'vendido'): ?>
                                    <span class="vehicle-badge badge-vendido">Vendido</span>
                                    <?php endif; ?>

                                    <?php if ($v['status'] === 'vendido'): ?>
                                    <div class="vehicle-status-overlay"><span>Vendido</span></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <div class="vehicle-card-body">
                                <h3 class="vehicle-card-title"><?= e($v['brand'] . ' ' . $v['model']) ?></h3>
                                <div class="vehicle-card-specs">
                                    <span class="vehicle-spec">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <?= (int)$v['year'] ?>
                                    </span>
                                    <span class="vehicle-spec">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="12" x2="16" y2="12"/></svg>
                                        <?= formatMileage((int)$v['mileage']) ?>
                                    </span>
                                    <span class="vehicle-spec">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V5a2 2 0 012-2h8a2 2 0 012 2v17"/><path d="M15 10h2a2 2 0 012 2v3a2 2 0 002 2h0"/><path d="M21 13V8l-2-2"/><rect x="6" y="6" width="6" height="5" rx="1"/></svg>
                                        <?= fuelLabel($v['fuel']) ?>
                                    </span>
                                    <span class="vehicle-spec">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><line x1="6" y1="8" x2="6" y2="16"/><path d="M18 8v4a4 4 0 01-4 4H6"/></svg>
                                        <?= transmissionLabel($v['transmission']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($v['warranty'])): ?>
                                <div class="vehicle-card-warranty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    <?= e($v['warranty']) ?>
                                </div>
                                <?php endif; ?>
                                <div class="vehicle-card-footer">
                                    <div class="vehicle-price"><?= formatPrice((float)$v['price']) ?><?php if (isset($v['sale_type']) && $v['sale_type'] === 'iva_incluido'): ?> <small>IVA incl.</small><?php endif; ?></div>
                                    <a href="<?= SITE_URL ?>/vehiculo/<?= e($v['slug']) ?>" class="btn-details">
                                        Ver
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Build base URL preserving filters
                        $baseParams = $_GET;
                        unset($baseParams['pagina']);
                        $baseUrl = SITE_URL . '/inventario?' . http_build_query($baseParams) . '&pagina=';
                        ?>

                        <?php if ($pagina > 1): ?>
                        <a href="<?= $baseUrl . ($pagina - 1) ?>" class="prev">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                            Ant.
                        </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $pagina - 2);
                        $end   = min($totalPages, $pagina + 2);
                        if ($start > 1):
                        ?>
                        <a href="<?= $baseUrl ?>1">1</a>
                        <?php if ($start > 2): ?><span class="dots">...</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i === $pagina): ?>
                            <span class="active"><?= $i ?></span>
                            <?php else: ?>
                            <a href="<?= $baseUrl . $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?><span class="dots">...</span><?php endif; ?>
                        <a href="<?= $baseUrl . $totalPages ?>"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($pagina < $totalPages): ?>
                        <a href="<?= $baseUrl . ($pagina + 1) ?>" class="next">
                            Sig.
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
