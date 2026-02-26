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
$totalLeads      = db()->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$unreadLeadsCount = db()->query("SELECT COUNT(*) FROM leads WHERE read_status = 0")->fetchColumn();

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
        <div class="stat-icon" style="background: rgba(200,150,62,0.12); color: #C8963E;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </div>
        <div class="stat-info">
            <h3><?= $unreadLeadsCount ?></h3>
            <p>Consultas sin leer</p>
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
                            <div style="font-weight:600"><?= e($v['brand'] . ' ' . $v['model']) ?></div>
                            <div class="text-sm text-muted"><?= e($v['year']) ?> &middot; <?= formatMileage($v['mileage']) ?></div>
                        </td>
                        <td style="font-weight:700;color:#1B3A5C"><?= formatPrice($v['price']) ?></td>
                        <td>
                            <?php
                                $badgeClass = ['disponible'=>'badge-green','reservado'=>'badge-yellow','vendido'=>'badge-red'][$v['status']] ?? 'badge-gray';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= statusLabel($v['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
