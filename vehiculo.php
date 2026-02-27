<?php
/**
 * MULTICAR — Single Vehicle Detail
 */
require_once __DIR__ . '/includes/init.php';

// ── Get vehicle by slug ──
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    redirect(SITE_URL . '/inventario');
}

$stmt = db()->prepare("SELECT * FROM vehicles WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    http_response_code(404);
    $pageTitle       = 'Vehículo no encontrado — ' . SITE_NAME;
    $pageDescription = 'El vehículo solicitado no existe o ha sido eliminado.';
    $activePage      = 'inventario';
    $headerSolid     = true;
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= SITE_URL ?>/">Inicio</a>
                <span class="sep">/</span>
                <a href="<?= SITE_URL ?>/inventario">Inventario</a>
                <span class="sep">/</span>
                <span>No encontrado</span>
            </div>
            <h1>Vehículo no encontrado</h1>
            <p>El vehículo que buscas no existe o ha sido eliminado.</p>
        </div>
    </section>
    <section class="section" style="text-align:center">
        <div class="container">
            <a href="<?= SITE_URL ?>/inventario" class="btn-primary">Volver al inventario</a>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ── Increment views ──
$stmtViews = db()->prepare("UPDATE vehicles SET views = views + 1 WHERE id = ?");
$stmtViews->execute([$vehicle['id']]);

// ── Get images ──
$images = getVehicleImages($vehicle['id']);

// ── Related vehicles (same brand, exclude current) ──
$stmtRelated = db()->prepare("
    SELECT v.*,
        (SELECT vi.filename FROM vehicle_images vi WHERE vi.vehicle_id = v.id ORDER BY vi.is_cover DESC, vi.sort_order ASC LIMIT 1) AS cover_image
    FROM vehicles v
    WHERE v.brand = ? AND v.id != ? AND v.status = 'disponible'
    ORDER BY v.created_at DESC
    LIMIT 3
");
$stmtRelated->execute([$vehicle['brand'], $vehicle['id']]);
$related = $stmtRelated->fetchAll();

// ── Vehicle name ──
$vehicleName = $vehicle['brand'] . ' ' . $vehicle['model'];
if (!empty($vehicle['version'])) {
    $vehicleFullName = $vehicleName . ' ' . $vehicle['version'];
} else {
    $vehicleFullName = $vehicleName;
}

// ── SEO ──
$pageTitle       = $vehicleFullName . ' (' . $vehicle['year'] . ') — ' . SITE_NAME;
$pageDescription = $vehicleFullName . ' ' . $vehicle['year'] . ' — ' . formatPrice((float)$vehicle['price']) . '. ' . formatMileage((int)$vehicle['mileage']) . '. ' . fuelLabel($vehicle['fuel']) . ', ' . transmissionLabel($vehicle['transmission']) . '. Disponible en ' . SITE_NAME . '.';
$activePage      = 'inventario';
$headerSolid     = true;

// OG image
$coverImage = null;
if (!empty($images)) {
    $coverImage = UPLOAD_URL . $images[0]['filename'];
}
$ogImage = $coverImage ?? SITE_URL . '/assets/img/og-default.jpg';

// WhatsApp link for this vehicle
$whatsappVehicle = getWhatsAppLink($vehicleFullName . ' ' . $vehicle['year']);

// YouTube embed
$youtubeEmbed = null;
if (!empty($vehicle['video_url'])) {
    $youtubeEmbed = getYouTubeEmbedUrl($vehicle['video_url']);
}

// Share URLs
$shareUrl   = SITE_URL . '/vehiculo/' . $vehicle['slug'];
$shareTitle = $vehicleFullName . ' — ' . SITE_NAME;

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ═══ PAGE BANNER ═══ -->
    <section class="page-banner" style="padding-bottom:40px;">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= SITE_URL ?>/">Inicio</a>
                <span class="sep">/</span>
                <a href="<?= SITE_URL ?>/inventario">Inventario</a>
                <span class="sep">/</span>
                <span><?= e($vehicleName) ?></span>
            </div>
        </div>
    </section>

    <!-- ═══ VEHICLE DETAIL ═══ -->
    <section class="vehicle-detail">
        <div class="container">
            <div class="vehicle-detail-grid">
                <!-- GALLERY -->
                <div class="gallery">
                    <div class="gallery-main">
                        <?php if (!empty($images)): ?>
                        <img src="<?= UPLOAD_URL . e($images[0]['filename']) ?>" alt="<?= e($vehicleFullName) ?>" id="galleryMainImg">
                        <?php else: ?>
                        <div class="img-placeholder" style="width:100%;height:100%;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#E2E8F0,#CBD5E1);">
                            <svg viewBox="0 0 24 24" width="80" height="80" fill="none" stroke="currentColor" stroke-width="1" opacity="0.3"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($images as $idx => $img): ?>
                        <div class="gallery-thumb<?= $idx === 0 ? ' active' : '' ?>">
                            <img src="<?= UPLOAD_URL . e($img['filename']) ?>" alt="<?= e($vehicleFullName) ?> - Foto <?= $idx + 1 ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- VEHICLE INFO -->
                <div class="vehicle-info">
                    <div class="vehicle-info-header">
                        <?php if ($vehicle['status'] !== 'disponible'): ?>
                        <span class="vehicle-badge badge-<?= e($vehicle['status']) ?>" style="background:<?= statusColor($vehicle['status']) ?>"><?= statusLabel($vehicle['status']) ?></span>
                        <?php endif; ?>

                        <h1><?= e($vehicleName) ?></h1>
                        <?php if (!empty($vehicle['version'])): ?>
                        <p class="version"><?= e($vehicle['version']) ?> &middot; <?= (int)$vehicle['year'] ?></p>
                        <?php else: ?>
                        <p class="version"><?= (int)$vehicle['year'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="vehicle-info-price">
                        <div class="price"><?= formatPrice((float)$vehicle['price']) ?><?php if (isset($vehicle['sale_type']) && $vehicle['sale_type'] === 'iva_incluido'): ?> <small>IVA incl.</small><?php endif; ?></div>
                    </div>

                    <?php if (!empty($vehicle['warranty'])): ?>
                    <div class="vehicle-warranty-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <strong><?= e($vehicle['warranty']) ?></strong>
                    </div>
                    <?php endif; ?>

                    <!-- Specs Grid -->
                    <div class="specs-grid">
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Año</div>
                                <div class="spec-item-value"><?= (int)$vehicle['year'] ?></div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Kilómetros</div>
                                <div class="spec-item-value"><?= formatMileage((int)$vehicle['mileage']) ?></div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V5a2 2 0 012-2h8a2 2 0 012 2v17"/><path d="M15 10h2a2 2 0 012 2v3a2 2 0 002 2h0"/><path d="M21 13V8l-2-2"/><rect x="6" y="6" width="6" height="5" rx="1"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Combustible</div>
                                <div class="spec-item-value"><?= fuelLabel($vehicle['fuel']) ?></div>
                            </div>
                        </div>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><line x1="6" y1="8" x2="6" y2="16"/><path d="M18 8v4a4 4 0 01-4 4H6"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Transmisión</div>
                                <div class="spec-item-value"><?= transmissionLabel($vehicle['transmission']) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($vehicle['power'])): ?>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Potencia</div>
                                <div class="spec-item-value"><?= (int)$vehicle['power'] ?> CV</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($vehicle['doors'])): ?>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Puertas</div>
                                <div class="spec-item-value"><?= (int)$vehicle['doors'] ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($vehicle['color'])): ?>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><path d="M17.08 9.42a8 8 0 1 1-10.16 0"/><line x1="12" y1="2" x2="12" y2="6"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Color</div>
                                <div class="spec-item-value"><?= e($vehicle['color']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($vehicle['body_type'])): ?>
                        <div class="spec-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2" ry="2"/><path d="M16 8h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2"/><polyline points="1 13 16 13"/></svg>
                            <div class="spec-item-content">
                                <div class="spec-item-label">Carrocería</div>
                                <div class="spec-item-value"><?= bodyTypeLabel($vehicle['body_type']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="vehicle-cta">
                        <a href="<?= $whatsappVehicle ?>" target="_blank" rel="noopener" class="btn-whatsapp">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 0 0 .917.918l4.458-1.495A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 0 1-5.39-1.582l-.386-.234-2.65.889.889-2.65-.234-.386A9.94 9.94 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                            Consultar por este vehículo
                        </a>
                        <a href="tel:+<?= e(WHATSAPP_NUMBER) ?>" class="btn-call">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            Llamar
                        </a>
                    </div>

                    <!-- Share Buttons -->
                    <div class="share-buttons">
                        <span>Compartir:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener" class="share-btn facebook" aria-label="Compartir en Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($shareTitle . ' ' . $shareUrl) ?>" target="_blank" rel="noopener" class="share-btn whatsapp-share" aria-label="Compartir en WhatsApp">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 0 0 .917.918l4.458-1.495A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 0 1-5.39-1.582l-.386-.234-2.65.889.889-2.65-.234-.386A9.94 9.94 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($shareTitle) ?>" target="_blank" rel="noopener" class="share-btn twitter" aria-label="Compartir en Twitter">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- DESCRIPTION -->
            <?php if (!empty($vehicle['description'])): ?>
            <div class="vehicle-description">
                <h2>Descripción</h2>
                <div class="rich-content">
                    <?= $vehicle['description'] ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- FEATURES -->
            <?php if (!empty($vehicle['features'])): ?>
            <div class="vehicle-features">
                <h2>Equipamiento</h2>
                <div class="rich-content">
                    <?= $vehicle['features'] ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- VIDEO -->
            <?php if ($youtubeEmbed): ?>
            <div class="vehicle-video">
                <h2>Vídeo</h2>
                <div class="video-container">
                    <iframe src="<?= e($youtubeEmbed) ?>" title="<?= e($vehicleFullName) ?>" allowfullscreen loading="lazy"></iframe>
                </div>
            </div>
            <?php endif; ?>

            <!-- CONTACT FORM -->
            <div class="vehicle-contact-form">
                <h2>¿Interesado en este vehículo?</h2>
                <form data-ajax-form action="<?= SITE_URL ?>/api/lead.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle['id'] ?>">
                    <input type="hidden" name="vehicle_name" value="<?= e($vehicleFullName . ' ' . $vehicle['year']) ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="lead-name">Nombre *</label>
                            <input type="text" id="lead-name" name="name" required placeholder="Tu nombre completo" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="lead-phone">Teléfono *</label>
                            <input type="tel" id="lead-phone" name="phone" required placeholder="+34 600 000 000" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lead-email">Email</label>
                        <input type="email" id="lead-email" name="email" placeholder="tu@email.com" maxlength="150">
                    </div>
                    <div class="form-group">
                        <label for="lead-message">Mensaje</label>
                        <textarea id="lead-message" name="message" placeholder="Escríbenos tu consulta sobre este vehículo..." rows="4" maxlength="2000"></textarea>
                    </div>
                    <button type="submit" class="form-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Enviar consulta
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- LIGHTBOX -->
    <?php if (!empty($images)): ?>
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" aria-label="Cerrar">&times;</button>
        <button class="lightbox-nav lightbox-prev" aria-label="Anterior">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <img src="" alt="<?= e($vehicleFullName) ?>">
        <button class="lightbox-nav lightbox-next" aria-label="Siguiente">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
    <?php endif; ?>

    <!-- RELATED VEHICLES -->
    <?php if (!empty($related)): ?>
    <section class="related-section">
        <div class="container">
            <h2 class="section-title">Vehículos relacionados</h2>
            <div class="vehicles-grid">
                <?php foreach ($related as $r): ?>
                <div class="vehicle-card reveal">
                    <a href="<?= SITE_URL ?>/vehiculo/<?= e($r['slug']) ?>">
                        <div class="vehicle-card-img">
                            <?php if ($r['cover_image']): ?>
                            <img src="<?= UPLOAD_URL . e($r['cover_image']) ?>" alt="<?= e($r['brand'] . ' ' . $r['model']) ?>" loading="lazy">
                            <?php else: ?>
                            <div class="img-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="vehicle-card-body">
                        <h3 class="vehicle-card-title"><?= e($r['brand'] . ' ' . $r['model']) ?></h3>
                        <div class="vehicle-card-specs">
                            <span class="vehicle-spec">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <?= (int)$r['year'] ?>
                            </span>
                            <span class="vehicle-spec">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                <?= formatMileage((int)$r['mileage']) ?>
                            </span>
                            <span class="vehicle-spec">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg>
                                <?= transmissionLabel($r['transmission']) ?>
                            </span>
                        </div>
                        <div class="vehicle-card-footer">
                            <div class="vehicle-price"><?= formatPrice((float)$r['price']) ?></div>
                            <a href="<?= SITE_URL ?>/vehiculo/<?= e($r['slug']) ?>" class="btn-details">
                                Ver
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
