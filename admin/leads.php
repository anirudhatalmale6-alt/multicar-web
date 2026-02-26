<?php
/**
 * MULTICAR — Admin Leads / Consultas
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();
define('ADMIN_LOADED', true);
$adminTitle = 'Leads / Consultas';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verifyCsrf()) {
        flash('error', 'Token de seguridad invalido.');
    } else {
        $deleteId = (int)($_POST['lead_id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = db()->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$deleteId]);
            flash('success', 'Consulta eliminada.');
        }
    }
    redirect(SITE_URL . '/admin/leads.php?' . http_build_query(array_filter([
        'filter' => $_GET['filter'] ?? '', 'vehicle' => $_GET['vehicle'] ?? '', 'page' => $_GET['page'] ?? ''
    ])));
}

// Filters
$filter = $_GET['filter'] ?? '';
$vehicleFilter = (int)($_GET['vehicle'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];

if ($filter === 'unread') {
    $where[] = "l.read_status = 0";
}
if ($vehicleFilter > 0) {
    $where[] = "l.vehicle_id = ?";
    $params[] = $vehicleFilter;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = db()->prepare("SELECT COUNT(*) FROM leads l $whereSQL");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch
$stmt = db()->prepare("
    SELECT l.*, v.brand, v.model, v.year, v.slug as vehicle_slug
    FROM leads l
    LEFT JOIN vehicles v ON l.vehicle_id = v.id
    $whereSQL
    ORDER BY l.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Vehicle list for filter dropdown
$vehicleList = db()->query("SELECT id, brand, model, year FROM vehicles ORDER BY brand, model")->fetchAll();

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="d-flex gap-1">
        <a href="<?= SITE_URL ?>/admin/leads.php" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-outline' ?>">
            Todas (<?= $totalRows ?>)
        </a>
        <?php
            $unreadCount = db()->query("SELECT COUNT(*) FROM leads WHERE read_status = 0")->fetchColumn();
        ?>
        <a href="<?= SITE_URL ?>/admin/leads.php?filter=unread" class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline' ?>">
            Sin leer (<?= $unreadCount ?>)
        </a>
    </div>
    <select class="form-control" id="vehicleFilterSelect" style="width:auto;min-width:200px">
        <option value="">Todos los vehiculos</option>
        <?php foreach ($vehicleList as $vf): ?>
            <option value="<?= $vf['id'] ?>" <?= $vehicleFilter === (int)$vf['id'] ? 'selected' : '' ?>>
                <?= e($vf['brand'] . ' ' . $vf['model'] . ' ' . $vf['year']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Leads Table -->
<div class="card">
    <div class="table-wrapper">
        <?php if (empty($leads)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <p>No hay consultas todavia</p>
        </div>
        <?php else: ?>
        <table id="leadsTable">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Contacto</th>
                    <th>Vehiculo</th>
                    <th>Mensaje</th>
                    <th style="width:50px"></th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead): ?>
                <tr class="lead-row <?= !$lead['read_status'] ? 'unread' : '' ?>"
                    onclick="toggleMessage(<?= $lead['id'] ?>)" id="lead-row-<?= $lead['id'] ?>">
                    <td class="text-center">
                        <button type="button" class="star-toggle <?= !$lead['read_status'] ? 'active' : '' ?>"
                                onclick="event.stopPropagation(); toggleRead(<?= $lead['id'] ?>, this)"
                                data-tooltip="<?= $lead['read_status'] ? 'Marcar como no leido' : 'Marcar como leido' ?>"
                                style="font-size:14px;color:<?= $lead['read_status'] ? 'var(--gray-300)' : 'var(--blue)' ?>">
                            <?= $lead['read_status'] ? '&#9711;' : '&#9679;' ?>
                        </button>
                    </td>
                    <td class="text-sm">
                        <?= date('d/m/Y', strtotime($lead['created_at'])) ?><br>
                        <span class="text-muted"><?= date('H:i', strtotime($lead['created_at'])) ?></span>
                    </td>
                    <td><?= e($lead['name']) ?></td>
                    <td class="text-sm">
                        <?php if ($lead['phone']): ?>
                            <div><?= e($lead['phone']) ?></div>
                        <?php endif; ?>
                        <?php if ($lead['email']): ?>
                            <div class="text-muted"><?= e($lead['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($lead['brand']): ?>
                            <a href="<?= SITE_URL ?>/admin/vehicle_edit.php?id=<?= $lead['vehicle_id'] ?>"
                               onclick="event.stopPropagation()" class="text-sm">
                                <?= e($lead['brand'] . ' ' . $lead['model'] . ' ' . $lead['year']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-sm text-muted">Consulta general</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="truncate text-sm"><?= e(mb_substr($lead['message'] ?? '', 0, 60)) ?><?= mb_strlen($lead['message'] ?? '') > 60 ? '...' : '' ?></div>
                    </td>
                    <td>
                        <?php if (!$lead['read_status']): ?>
                            <span class="badge badge-blue">Nuevo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline" onclick="event.stopPropagation()"
                              onsubmit="return confirmDelete('Eliminar esta consulta?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger" data-tooltip="Eliminar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <!-- Expandable message row -->
                <tr class="lead-message" id="lead-msg-<?= $lead['id'] ?>">
                    <td colspan="8" style="padding:16px 24px;background:var(--gray-50)">
                        <div style="max-width:600px">
                            <strong>Mensaje completo:</strong>
                            <p style="margin-top:8px;white-space:pre-wrap;line-height:1.7"><?= e($lead['message'] ?? 'Sin mensaje') ?></p>
                            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--gray-200)">
                                <?php if ($lead['phone']): ?>
                                    <a href="tel:<?= e($lead['phone']) ?>" class="btn btn-sm btn-outline" style="margin-right:8px">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                        Llamar
                                    </a>
                                <?php endif; ?>
                                <?php if ($lead['email']): ?>
                                    <a href="mailto:<?= e($lead['email']) ?>" class="btn btn-sm btn-outline">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        Email
                                    </a>
                                <?php endif; ?>
                                <?php if ($lead['phone']): ?>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['phone']) ?>" target="_blank" class="btn btn-sm btn-success" style="margin-left:8px">
                                        WhatsApp
                                    </a>
                                <?php endif; ?>
                            </div>
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
                Mostrando <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?> de <?= $totalRows ?> consultas
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
// Vehicle filter
document.getElementById('vehicleFilterSelect').addEventListener('change', function() {
    var params = new URLSearchParams(window.location.search);
    if (this.value) {
        params.set('vehicle', this.value);
    } else {
        params.delete('vehicle');
    }
    params.delete('page');
    window.location.href = '?' + params.toString();
});

// Toggle message expand
function toggleMessage(leadId) {
    var msgRow = document.getElementById('lead-msg-' + leadId);
    if (!msgRow) return;
    var isShown = msgRow.classList.contains('show');
    // Close all
    document.querySelectorAll('.lead-message.show').forEach(function(el) {
        el.classList.remove('show');
    });
    if (!isShown) {
        msgRow.classList.add('show');
        // Auto-mark as read
        var row = document.getElementById('lead-row-' + leadId);
        if (row && row.classList.contains('unread')) {
            toggleRead(leadId, row.querySelector('.star-toggle'));
        }
    }
}

// Toggle read status via AJAX
function toggleRead(leadId, btn) {
    adminFetch('<?= SITE_URL ?>/admin/api/toggle_lead_read.php', {
        lead_id: leadId,
        csrf_token: '<?= csrfToken() ?>'
    }).then(function(data) {
        if (data.success) {
            var row = document.getElementById('lead-row-' + leadId);
            if (data.read_status) {
                row.classList.remove('unread');
                btn.style.color = 'var(--gray-300)';
                btn.innerHTML = '&#9711;';
                btn.setAttribute('data-tooltip', 'Marcar como no leido');
                // Remove badge
                var badge = row.querySelector('.badge');
                if (badge) badge.remove();
            } else {
                row.classList.add('unread');
                btn.style.color = 'var(--blue)';
                btn.innerHTML = '&#9679;';
                btn.setAttribute('data-tooltip', 'Marcar como leido');
            }
        }
    });
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
