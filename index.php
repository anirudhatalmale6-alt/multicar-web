<?php
/**
 * MULTICAR — Homepage
 */
require_once __DIR__ . '/includes/init.php';

// ── Page config ──
$pageTitle       = SITE_NAME . ' — ' . SITE_TAGLINE;
$pageDescription = SITE_NAME . ' — Tu concesionario de confianza para vehículos usados. Compra, venta, alquiler y renting con garantía.';
$activePage      = 'inicio';
$headerSolid     = false;

// ── Featured vehicles (show featured first, then latest available) ──
$stmtFeatured = db()->query("
    SELECT v.*,
        (SELECT vi.filename FROM vehicle_images vi WHERE vi.vehicle_id = v.id ORDER BY vi.is_cover DESC, vi.sort_order ASC LIMIT 1) AS cover_image
    FROM vehicles v
    WHERE v.featured = 1 AND v.status = 'disponible'
    ORDER BY v.created_at DESC
    LIMIT 6
");
$featured = $stmtFeatured->fetchAll();

// If no featured, show latest available vehicles
if (empty($featured)) {
    $stmtLatest = db()->query("
        SELECT v.*,
            (SELECT vi.filename FROM vehicle_images vi WHERE vi.vehicle_id = v.id ORDER BY vi.is_cover DESC, vi.sort_order ASC LIMIT 1) AS cover_image
        FROM vehicles v
        WHERE v.status = 'disponible'
        ORDER BY v.created_at DESC
        LIMIT 6
    ");
    $featured = $stmtLatest->fetchAll();
}

// ── Stats ──
$totalVehicles    = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'disponible'")->fetchColumn();
$totalProximamente = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'proximamente'")->fetchColumn();
$totalSold        = db()->query("SELECT COUNT(*) FROM vehicles WHERE status = 'vendido'")->fetchColumn();

// ── Auto-calculate months of experience ──
$businessStart = getSetting('business_start_date', '2023-09-01');
$startDate = new DateTime($businessStart);
$now = new DateTime();
$monthsExperience = (int)$startDate->diff($now)->format('%m') + (int)$startDate->diff($now)->format('%y') * 12;

// ── Brands for search ──
$brands = getBrands();
$years  = getYears();

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ═══ HERO ═══ -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-grid"></div>

        <div class="hero-content">
            <div class="hero-badges">
                <div class="hero-badge">
                    <?php if ($totalVehicles > 0): ?>
                        <?= (int)$totalVehicles ?> vehículos disponibles
                    <?php else: ?>
                        Nuevo inventario disponible
                    <?php endif; ?>
                </div>
                <?php if ($totalProximamente > 0): ?>
                <div class="hero-badge hero-badge-prox">
                    <?= (int)$totalProximamente ?> próximamente
                </div>
                <?php endif; ?>
            </div>

            <h1>Encuentra tu próximo <span>vehículo</span></h1>

            <p class="hero-subtitle">
                Compra, venta, alquiler y renting de vehículos con garantía y la confianza de profesionales.
            </p>

            <!-- Search Bar -->
            <form class="search-bar" action="<?= SITE_URL ?>/inventario" method="GET">
                <div class="search-field">
                    <label>Marca</label>
                    <select name="marca">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($brands as $brand): ?>
                        <option value="<?= e($brand) ?>"><?= e($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <label>Modelo</label>
                    <input type="text" name="modelo" placeholder="Ej: A3, Serie 3...">
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <label>Año</label>
                    <select name="year_min">
                        <option value="">Cualquier año</option>
                        <?php foreach ($years as $year): ?>
                        <option value="<?= (int)$year ?>"><?= (int)$year ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <label>Precio máx.</label>
                    <select name="precio_max">
                        <option value="">Sin límite</option>
                        <option value="10000">10.000 &euro;</option>
                        <option value="15000">15.000 &euro;</option>
                        <option value="20000">20.000 &euro;</option>
                        <option value="30000">30.000 &euro;</option>
                        <option value="50000">50.000 &euro;</option>
                        <option value="75000">75.000 &euro;</option>
                        <option value="100000">100.000 &euro;</option>
                    </select>
                </div>
                <div class="search-divider"></div>
                <div class="search-field">
                    <label>Km máx.</label>
                    <select name="km_max">
                        <option value="">Sin límite</option>
                        <option value="10000">10.000 km</option>
                        <option value="25000">25.000 km</option>
                        <option value="50000">50.000 km</option>
                        <option value="75000">75.000 km</option>
                        <option value="100000">100.000 km</option>
                        <option value="150000">150.000 km</option>
                        <option value="200000">200.000 km</option>
                    </select>
                </div>
                <button type="submit" class="search-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Buscar
                </button>
            </form>

            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-number"><?= max((int)$totalSold, 12) ?></div>
                    <div class="hero-stat-label">Vehículos vendidos</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-number">+<?= max($monthsExperience, 36) ?></div>
                    <div class="hero-stat-label">Meses de experiencia</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-number">100%</div>
                    <div class="hero-stat-label">Clientes satisfechos</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ FEATURED VEHICLES ═══ -->
    <section class="section vehicles-section" id="inventario">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <div class="section-label">Inventario destacado</div>
                    <h2 class="section-title">Vehículos seleccionados</h2>
                    <p class="section-subtitle">Cada vehículo pasa por una inspección rigurosa antes de llegar a ti.</p>
                </div>
                <a href="<?= SITE_URL ?>/inventario" class="view-all-btn">
                    Ver todo el inventario
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>

            <div class="vehicles-grid">
                <?php if (empty($featured)): ?>
                    <!-- Placeholder cards when no vehicles in DB -->
                    <?php
                    $placeholders = [
                        ['Audi A3 Sportback', 2023, '45.000 km', 'Automático', '24.900', 'Destacado'],
                        ['BMW Serie 3 320d', 2022, '62.000 km', 'Automático', '29.500', ''],
                        ['Mercedes-Benz Clase A', 2024, '18.000 km', 'Automático', '32.900', 'Oferta'],
                        ['Volkswagen Golf GTI', 2021, '78.000 km', 'Manual', '22.400', ''],
                        ['Seat León FR', 2023, '35.000 km', 'Automático', '19.800', ''],
                        ['Toyota RAV4 Hybrid', 2024, '12.000 km', 'Automático', '36.500', 'Nuevo'],
                    ];
                    foreach ($placeholders as $ph):
                    ?>
                    <div class="vehicle-card reveal">
                        <div class="vehicle-card-img">
                            <div class="img-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <?php if ($ph[5]): ?>
                            <span class="vehicle-badge"><?= e($ph[5]) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="vehicle-card-body">
                            <h3 class="vehicle-card-title"><?= e($ph[0]) ?></h3>
                            <div class="vehicle-card-specs">
                                <span class="vehicle-spec">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <?= (int)$ph[1] ?>
                                </span>
                                <span class="vehicle-spec">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                    <?= e($ph[2]) ?>
                                </span>
                                <span class="vehicle-spec">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg>
                                    <?= e($ph[3]) ?>
                                </span>
                            </div>
                            <div class="vehicle-card-footer">
                                <div class="vehicle-price"><?= e($ph[4]) ?> &euro;</div>
                                <a href="<?= SITE_URL ?>/inventario" class="btn-details">
                                    Ver
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($featured as $v): ?>
                    <div class="vehicle-card reveal">
                        <a href="<?= SITE_URL ?>/vehiculo/<?= e($v['slug']) ?>">
                            <div class="vehicle-card-img">
                                <?php if ($v['cover_image']): ?>
                                <img src="<?= UPLOAD_URL . e($v['cover_image']) ?>" alt="<?= e($v['brand'] . ' ' . $v['model']) ?>">
                                <?php else: ?>
                                <div class="img-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                                <?php endif; ?>
                                <?php if ($v['featured']): ?>
                                <span class="vehicle-badge">Destacado</span>
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
                                    <!-- Fuel icon -->
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V5a2 2 0 012-2h8a2 2 0 012 2v17"/><path d="M15 10h2a2 2 0 012 2v3a2 2 0 002 2h0"/><path d="M21 13V8l-2-2"/><rect x="6" y="6" width="6" height="5" rx="1"/></svg>
                                    <?= fuelLabel($v['fuel']) ?>
                                </span>
                                <span class="vehicle-spec">
                                    <!-- Transmission icon -->
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
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ═══ WHY CHOOSE US ═══ -->
    <section class="section features-section" id="nosotros">
        <div class="container">
            <div class="section-header reveal">
                <div>
                    <div class="section-label">¿Por qué elegirnos?</div>
                    <h2 class="section-title">Confianza y profesionalidad</h2>
                    <p class="section-subtitle">Ayudamos a encontrar a cada cliente su coche perfecto.</p>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h3>Vehículos Verificados</h3>
                    <p>Cada vehículo pasa por una inspección técnica completa de más de 150 puntos antes de ser ofertado.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/><line x1="11" y1="8" x2="11" y2="14"/></svg>
                    </div>
                    <h3>Coche a tu medida</h3>
                    <p>¿No encuentras en nuestro inventario tu coche? Solicita nuestro servicio de compra por encargo.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h3>Garantía Incluida</h3>
                    <p>Todos nuestros vehículos incluyen garantía mecánica. Tu tranquilidad es nuestra prioridad.</p>
                </div>

                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>Asesoría Personalizada</h3>
                    <p>Nuestro equipo de expertos te guía en cada paso del proceso para que tomes la mejor decisión.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ STATS ═══ -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item reveal">
                    <div class="stat-number"><?= max((int)$totalSold, 12) ?></div>
                    <div class="stat-label">Vehículos vendidos</div>
                </div>
                <div class="stat-item reveal">
                    <div class="stat-number"><span class="accent">+</span><?= max($monthsExperience, 36) ?></div>
                    <div class="stat-label">Meses de experiencia</div>
                </div>
                <div class="stat-item reveal">
                    <div class="stat-number">100<span class="accent">%</span></div>
                    <div class="stat-label">Clientes satisfechos</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ CTA ═══ -->
    <section class="cta-section" id="contacto">
        <div class="container">
            <div class="cta-box reveal">
                <div class="cta-content">
                    <h2>¿Quieres vender tu vehículo?</h2>
                    <p>Te ofrecemos una tasación gratuita y sin compromiso. Obtén el mejor precio por tu coche en menos de 24 horas.</p>
                </div>
                <div class="cta-buttons">
                    <a href="<?= getWhatsAppLink() ?>" target="_blank" rel="noopener" class="btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 0 0 .917.918l4.458-1.495A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 0 1-5.39-1.582l-.386-.234-2.65.889.889-2.65-.234-.386A9.94 9.94 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        WhatsApp
                    </a>
                    <a href="<?= SITE_URL ?>/contacto" class="btn-outline">
                        Solicitar tasación
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
