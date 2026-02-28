<?php
/**
 * MULTICAR — Admin Vehicle List
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();
define('ADMIN_LOADED', true);
$adminTitle = 'Vehiculos';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCsrf()) {
        flash('error', 'Token de seguridad invalido.');
    } else {
        $deleteId = (int)($_POST['vehicle_id'] ?? 0);
        if ($deleteId > 0) {
            // Delete all images from disk
            $images = db()->prepare("SELECT filename FROM vehicle_images WHERE vehicle_id = ?");
            $images->execute([$deleteId]);
            foreach ($images->fetchAll() as $img) {
                $path = UPLOAD_DIR . $img['filename'];
                if (file_exists($path)) unlink($path);
            }
            // Delete vehicle (cascade deletes images in DB)
            $stmt = db()->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$deleteId]);
            flash('success', 'Vehiculo eliminado correctamente.');
        }
    }
    redirect(SITE_URL . '/admin/vehicles.php?' . http_build_query(array_filter([
        'q' => $_GET['q'] ?? '', 'status' => $_GET['status'] ?? '', 'page' => $_GET['page'] ?? ''
    ])));
}

// Filters
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(v.brand LIKE ? OR v.model LIKE ? OR v.version LIKE ?)";
    $searchLike = '%' . $search . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}
if ($statusFilter !== '' && in_array($statusFilter, ['disponible','reservado','vendido','proximamente'])) {
    $where[] = "v.status = ?";
    $params[] = $statusFilter;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = db()->prepare("SELECT COUNT(*) FROM vehicles v $whereSQL");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Sort
$sortOptions = [
    'price_asc'  => 'v.price ASC',
    'price_desc' => 'v.price DESC',
    'year_asc'   => 'v.year ASC',
    'year_desc'  => 'v.year DESC',
    'oldest'     => 'v.created_at ASC',
];
$orderSQL = $sortOptions[$sort] ?? 'v.created_at DESC';

// Fetch with views and leads count
$stmt = db()->prepare("
    SELECT v.*,
        (SELECT filename FROM vehicle_images WHERE vehicle_id = v.id ORDER BY is_cover DESC, sort_order ASC LIMIT 1) as cover_img,
        (SELECT COUNT(*) FROM leads WHERE vehicle_id = v.id) as leads_count
    FROM vehicles v
    $whereSQL
    ORDER BY $orderSQL
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="search-input">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Buscar por marca o modelo..."
               value="<?= e($search) ?>">
    </div>
    <select class="form-control" id="statusFilter" style="width:auto;min-width:160px">
        <option value="">Todos los estados</option>
        <option value="disponible" <?= $statusFilter === 'disponible' ? 'selected' : '' ?>>Disponible</option>
        <option value="reservado" <?= $statusFilter === 'reservado' ? 'selected' : '' ?>>Reservado</option>
        <option value="vendido" <?= $statusFilter === 'vendido' ? 'selected' : '' ?>>Vendido</option>
        <option value="proximamente" <?= $statusFilter === 'proximamente' ? 'selected' : '' ?>>Próximamente</option>
    </select>
    <select class="form-control" id="sortSelect" style="width:auto;min-width:160px">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mas recientes</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Mas antiguos</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
        <option value="year_desc" <?= $sort === 'year_desc' ? 'selected' : '' ?>>Ano: mas reciente</option>
        <option value="year_asc" <?= $sort === 'year_asc' ? 'selected' : '' ?>>Ano: mas antiguo</option>
    </select>
    <a href="<?= SITE_URL ?>/admin/vehicle_edit.php" class="btn btn-gold">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo
    </a>
</div>

<!-- Vehicle Table -->
<div class="card">
    <div class="table-wrapper">
        <?php if (empty($vehicles)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <p>No se encontraron vehiculos</p>
            <a href="<?= SITE_URL ?>/admin/vehicle_edit.php" class="btn btn-gold btn-sm">Anadir primer vehiculo</a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:80px"></th>
                    <th>Vehiculo</th>
                    <th>Ano</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th class="text-center" title="Visualizaciones">Visitas</th>
                    <th class="text-center" title="Consultas recibidas">Consultas</th>
                    <th style="width:50px" class="text-center">Dest.</th>
                    <th style="width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td>
                        <?php if ($v['cover_img']): ?>
                            <img src="<?= UPLOAD_URL . e($v['cover_img']) ?>" alt="" class="vehicle-thumb">
                        <?php else: ?>
                            <div class="vehicle-thumb" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:24px;height:24px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= SITE_URL ?>/admin/vehicle_edit.php?id=<?= $v['id'] ?>" style="text-decoration:none;color:inherit">
                            <div style="font-weight:600"><?= e($v['brand'] . ' ' . $v['model']) ?></div>
                            <?php if ($v['version']): ?>
                                <div class="text-sm text-muted"><?= e($v['version']) ?></div>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td><?= e($v['year']) ?></td>
                    <td style="font-weight:700;color:#1B3A5C"><?= formatPrice($v['price']) ?></td>
                    <td>
                        <?php
                            $badgeClass = ['disponible'=>'badge-green','reservado'=>'badge-yellow','vendido'=>'badge-red','proximamente'=>'badge-blue'][$v['status']] ?? 'badge-gray';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= statusLabel($v['status']) ?></span>
                    </td>
                    <td class="text-center text-sm text-muted"><?= number_format($v['views']) ?></td>
                    <td class="text-center text-sm"><?= (int)$v['leads_count'] > 0 ? '<span class="badge badge-blue">'.$v['leads_count'].'</span>' : '<span class="text-muted">0</span>' ?></td>
                    <td class="text-center">
                        <button class="star-toggle <?= $v['featured'] ? 'active' : '' ?>"
                                onclick="toggleFeatured(<?= $v['id'] ?>, this)"
                                data-tooltip="<?= $v['featured'] ? 'Quitar de destacados' : 'Marcar como destacado' ?>">
                            <?= $v['featured'] ? '&#9733;' : '&#9734;' ?>
                        </button>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= SITE_URL ?>/admin/vehicle_edit.php?id=<?= $v['id'] ?>" class="btn btn-xs btn-outline" data-tooltip="Editar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete('Eliminar este vehiculo y todas sus imagenes?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                                <button type="submit" class="btn btn-xs btn-danger" data-tooltip="Eliminar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-between items-center">
            <span class="text-sm text-muted">
                Mostrando <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?> de <?= $totalRows ?> vehiculos
            </span>
            <div class="pagination" style="margin-top:0">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
                <?php else: ?>
                    <span class="disabled">&laquo;</span>
                <?php endif; ?>

                <?php
                    $startP = max(1, $page - 2);
                    $endP = min($totalPages, $page + 2);
                    if ($startP > 1) echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => 1])) .'">1</a>';
                    if ($startP > 2) echo '<span class="disabled">&hellip;</span>';
                    for ($i = $startP; $i <= $endP; $i++):
                ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php
                    endfor;
                    if ($endP < $totalPages - 1) echo '<span class="disabled">&hellip;</span>';
                    if ($endP < $totalPages) echo '<a href="?'. http_build_query(array_merge($_GET, ['page' => $totalPages])) .'">'.$totalPages.'</a>';
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
                <?php else: ?>
                    <span class="disabled">&raquo;</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Search & Filter navigation
(function(){
    var timer;
    var searchInput = document.getElementById('searchInput');
    var statusFilter = document.getElementById('statusFilter');
    var sortSelect = document.getElementById('sortSelect');

    function applyFilters() {
        var params = new URLSearchParams(window.location.search);
        params.set('q', searchInput.value);
        params.set('status', statusFilter.value);
        params.set('sort', sortSelect.value);
        params.delete('page');
        window.location.href = '?' + params.toString();
    }

    searchInput.addEventListener('keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(applyFilters, 500);
    });
    statusFilter.addEventListener('change', applyFilters);
    sortSelect.addEventListener('change', applyFilters);
})();

// Toggle featured via AJAX
function toggleFeatured(vehicleId, btn) {
    adminFetch('<?= SITE_URL ?>/admin/api/toggle_featured.php', {
        vehicle_id: vehicleId,
        csrf_token: '<?= csrfToken() ?>'
    }).then(function(data) {
        if (data.success) {
            btn.classList.toggle('active');
            btn.innerHTML = data.featured ? '&#9733;' : '&#9734;';
            btn.setAttribute('data-tooltip', data.featured ? 'Quitar de destacados' : 'Marcar como destacado');
        }
    });
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
