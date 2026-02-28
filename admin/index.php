<?php
/**
 * MULTICAR — Admin Dashboard
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();
define('ADMIN_LOADED', true);
$adminTitle = 'Dashboard';

// Stats
$totalVehicles   = db()->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$disponibles     = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'disponible'")->fetchColumn();
$reservados      = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'reservado'")->fetchColumn();
$vendidos        = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'vendido'")->fetchColumn();
$proximamente    = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'proximamente'")->fetchColumn();
$totalLeads      = db()->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$unreadLeadsCount = db()->query("SELECT COUNT(*) FROM leads WHERE read_status = 0")->fetchColumn();
$totalViews      = db()->query("SELECT COALESCE(SUM(views), 0) FROM vehicles")->fetchColumn();

// Recent leads (last 10)
$recentLeads = db()->query("
    SELECT l.*, v.brand, v.model, v.year
    FROM leads l
    LEFT JOIN vehicles v ON l.vehicle_id = v.id
    ORDER BY l.created_at DESC
    LIMIT 10
")->fetchAll();

// Recent vehicles (last 5)
$recentVehicles = db()->query("
    SELECT v.*,
        (SELECT filename FROM vehicle_images WHERE vehicle_id = v.id ORDER BY is_cover DESC, sort_order ASC LIMIT 1) as cover_img
    FROM vehicles v
    ORDER BY v.created_at DESC
    LIMIT 5
")->fetchAll();

// Analytics data (last 30 days)
$hasAnalytics = false;
$dailyData = [];
$sourceData = [];
$vehicleVisits = [];
try {
    $check = db()->query("SHOW TABLES LIKE 'page_views'");
    if ($check->rowCount() > 0) {
        $hasAnalytics = true;

        // Daily unique visitors & page views (last 30 days)
        $dailyData = db()->query("
            SELECT DATE(created_at) as dia,
                   COUNT(*) as paginas,
                   COUNT(DISTINCT visitor_hash) as visitantes
            FROM page_views
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY dia ASC
        ")->fetchAll();

        // Traffic sources (top referrers)
        $sourceData = db()->query("
            SELECT
                CASE
                    WHEN referrer = '' OR referrer IS NULL THEN 'Directo'
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%bing%' THEN 'Bing'
                    WHEN referrer LIKE '%facebook%' OR referrer LIKE '%fb.%' THEN 'Facebook'
                    WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                    WHEN referrer LIKE '%tiktok%' THEN 'TikTok'
                    WHEN referrer LIKE '%whatsapp%' OR referrer LIKE '%wa.me%' THEN 'WhatsApp'
                    WHEN referrer LIKE '%" . str_replace("'", "\\'", SITE_URL) . "%' THEN 'Interno'
                    ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1)
                END as origen,
                COUNT(*) as total
            FROM page_views
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY origen
            ORDER BY total DESC
            LIMIT 8
        ")->fetchAll();

        // Vehicle visits (active listings with views)
        $vehicleVisits = db()->query("
            SELECT v.brand, v.model, v.year, v.views,
                COUNT(pv.id) as visitas_30d
            FROM vehicles v
            LEFT JOIN page_views pv ON pv.vehicle_id = v.id AND pv.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE v.status = 'disponible'
            GROUP BY v.id
            HAVING v.views > 0 OR visitas_30d > 0
            ORDER BY visitas_30d DESC
            LIMIT 10
        ")->fetchAll();
    }
} catch (Exception $e) {}

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon navy">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalVehicles ?></h3>
            <p>Total vehiculos</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $disponibles ?></h3>
            <p>Disponibles</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $reservados ?></h3>
            <p>Reservados</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $vendidos ?></h3>
            <p>Vendidos</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $totalLeads ?></h3>
            <p>Consultas totales</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $proximamente ?></h3>
            <p>Proximamente</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(200,150,62,0.12); color: #C8963E;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $unreadLeadsCount ?></h3>
            <p>Consultas sin leer</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= number_format($totalViews) ?></h3>
            <p>Visitas totales</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= SITE_URL ?>/admin/vehicle_edit.php" class="btn btn-gold">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Nuevo vehiculo
    </a>
    <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Ver sitio web
    </a>
</div>

<?php if ($hasAnalytics): ?>
<!-- Analytics Charts -->
<div class="grid-2 mb-3">
    <!-- Visitors & Page Views -->
    <div class="card">
        <div class="card-header">
            <h2>Visitantes y paginas vistas</h2>
            <span class="text-sm text-muted">Ultimos 30 dias</span>
        </div>
        <div class="card-body" style="padding:16px">
            <canvas id="chartVisitors" height="220"></canvas>
        </div>
    </div>
    <!-- Traffic Sources -->
    <div class="card">
        <div class="card-header">
            <h2>Origen del trafico</h2>
            <span class="text-sm text-muted">Ultimos 30 dias</span>
        </div>
        <div class="card-body" style="padding:16px">
            <canvas id="chartSources" height="220"></canvas>
        </div>
    </div>
</div>

<?php if (!empty($vehicleVisits)): ?>
<!-- Vehicle Visits Chart -->
<div class="card mb-3">
    <div class="card-header">
        <h2>Visitas por anuncio activo</h2>
        <span class="text-sm text-muted">Ultimos 30 dias</span>
    </div>
    <div class="card-body" style="padding:16px">
        <canvas id="chartVehicleVisits" height="180"></canvas>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="grid-2">
    <!-- Recent Leads -->
    <div class="card">
        <div class="card-header">
            <h2>Ultimas consultas</h2>
            <a href="<?= SITE_URL ?>/admin/leads.php" class="btn btn-sm btn-outline">Ver todas</a>
        </div>
        <div class="table-wrapper">
            <?php if (empty($recentLeads)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <p>No hay consultas todavia</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Contacto</th>
                        <th>Vehiculo</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLeads as $lead): ?>
                    <tr>
                        <td>
                            <div style="font-weight:<?= $lead['read_status'] ? '400' : '700' ?>">
                                <?= e($lead['name']) ?>
                            </div>
                            <div class="text-sm text-muted"><?= e($lead['email'] ?? $lead['phone'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if ($lead['brand']): ?>
                                <span class="text-sm"><?= e($lead['brand'] . ' ' . $lead['model']) ?></span>
                            <?php else: ?>
                                <span class="text-sm text-muted">General</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted">
                            <?= date('d/m/y H:i', strtotime($lead['created_at'])) ?>
                        </td>
                        <td>
                            <?php if (!$lead['read_status']): ?>
                                <span class="badge badge-blue">Nuevo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Vehicles -->
    <div class="card">
        <div class="card-header">
            <h2>Vehiculos recientes</h2>
            <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-sm btn-outline">Ver todos</a>
        </div>
        <div class="table-wrapper">
            <?php if (empty($recentVehicles)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <p>No hay vehiculos todavia</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Vehiculo</th>
                        <th>Precio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVehicles as $v): ?>
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
                                <div class="text-sm text-muted"><?= e($v['year']) ?> &middot; <?= formatMileage($v['mileage']) ?></div>
                            </a>
                        </td>
                        <td style="font-weight:700;color:#1B3A5C"><?= formatPrice($v['price']) ?></td>
                        <td>
                            <?php
                                $badgeClass = ['disponible'=>'badge-green','reservado'=>'badge-yellow','vendido'=>'badge-red','proximamente'=>'badge-blue'][$v['status']] ?? 'badge-gray';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= statusLabel($v['status']) ?></span>
                            <?php if (($v['published_status'] ?? 'activo') !== 'activo'): ?>
                                <span class="badge badge-gray" style="margin-left:4px"><?= ($v['published_status'] ?? '') === 'borrador' ? 'Borrador' : 'No Activo' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php if ($hasAnalytics): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function(){
    var navy = '#1B3A5C';
    var gold = '#C8963E';
    var blue = '#3b82f6';
    var goldLight = 'rgba(200,150,62,0.15)';
    var blueLight = 'rgba(59,130,246,0.15)';

    // Chart defaults
    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;

    // -- Visitors & Page Views --
    var dailyLabels = <?= json_encode(array_map(function($d) { return date('d/m', strtotime($d['dia'])); }, $dailyData)) ?>;
    var dailyVisitors = <?= json_encode(array_map(function($d) { return (int)$d['visitantes']; }, $dailyData)) ?>;
    var dailyPages = <?= json_encode(array_map(function($d) { return (int)$d['paginas']; }, $dailyData)) ?>;

    new Chart(document.getElementById('chartVisitors'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Visitantes unicos',
                data: dailyVisitors,
                borderColor: navy,
                backgroundColor: goldLight,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: navy,
                borderWidth: 2
            }, {
                label: 'Paginas vistas',
                data: dailyPages,
                borderColor: blue,
                backgroundColor: blueLight,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: blue,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });

    // -- Traffic Sources (doughnut) --
    var sourceLabels = <?= json_encode(array_column($sourceData, 'origen')) ?>;
    var sourceValues = <?= json_encode(array_map('intval', array_column($sourceData, 'total'))) ?>;
    var sourceColors = ['#1B3A5C', '#C8963E', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

    new Chart(document.getElementById('chartSources'), {
        type: 'doughnut',
        data: {
            labels: sourceLabels,
            datasets: [{ data: sourceValues, backgroundColor: sourceColors, borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'right', labels: { padding: 12, font: { size: 11 } } }
            }
        }
    });

    <?php if (!empty($vehicleVisits)): ?>
    // -- Vehicle Visits (horizontal bar) --
    var vLabels = <?= json_encode(array_map(function($v) { return $v['brand'] . ' ' . $v['model'] . ' ' . $v['year']; }, $vehicleVisits)) ?>;
    var vVisits = <?= json_encode(array_map(function($v) { return (int)$v['visitas_30d']; }, $vehicleVisits)) ?>;
    var vTotal = <?= json_encode(array_map(function($v) { return (int)$v['views']; }, $vehicleVisits)) ?>;

    new Chart(document.getElementById('chartVehicleVisits'), {
        type: 'bar',
        data: {
            labels: vLabels,
            datasets: [{
                label: 'Visitas (30 dias)',
                data: vVisits,
                backgroundColor: gold,
                borderRadius: 4,
                barThickness: 20
            }, {
                label: 'Visitas totales',
                data: vTotal,
                backgroundColor: blueLight,
                borderColor: blue,
                borderWidth: 1,
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.04)' } },
                y: { grid: { display: false } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });
    <?php endif; ?>
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
