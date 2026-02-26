<?php
/**
 * MULTICAR — Contact Page
 */
require_once __DIR__ . '/includes/init.php';

// ── Page config ──
$pageTitle       = 'Contacto — ' . SITE_NAME;
$pageDescription = 'Contacta con ' . SITE_NAME . '. Estamos aquí para ayudarte con la compra, venta, alquiler o renting de tu vehículo.';
$activePage      = 'contacto';
$headerSolid     = true;

// ── Optional vehicle reference from URL ──
$vehicleRef = trim($_GET['vehiculo'] ?? '');

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ═══ PAGE BANNER ═══ -->
    <section class="page-banner">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?= SITE_URL ?>/">Inicio</a>
                <span class="sep">/</span>
                <span>Contacto</span>
            </div>
            <h1>Contáctanos</h1>
            <p>Estamos aquí para ayudarte. Escríbenos y te responderemos lo antes posible.</p>
        </div>
    </section>

    <!-- ═══ CONTACT CONTENT ═══ -->
    <section style="background: var(--off-white);">
        <div class="container">
            <div class="contact-layout">
                <!-- CONTACT FORM -->
                <div class="contact-form-wrapper">
                    <h2>Envíanos un mensaje</h2>
                    <p class="subtitle">Rellena el formulario y nos pondremos en contacto contigo a la mayor brevedad.</p>

                    <form data-ajax-form action="<?= SITE_URL ?>/api/lead.php" method="POST">
                        <?= csrfField() ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact-name">Nombre *</label>
                                <input type="text" id="contact-name" name="name" required placeholder="Tu nombre completo" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="contact-phone">Teléfono *</label>
                                <input type="tel" id="contact-phone" name="phone" required placeholder="+34 600 000 000" maxlength="20">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contact-email">Email</label>
                            <input type="email" id="contact-email" name="email" placeholder="tu@email.com" maxlength="150">
                        </div>

                        <?php if ($vehicleRef !== ''): ?>
                        <div class="form-group">
                            <label for="contact-vehicle">Vehículo de referencia</label>
                            <input type="text" id="contact-vehicle" name="vehicle_name" value="<?= e($vehicleRef) ?>" readonly style="background:var(--off-white);">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="contact-subject">Asunto</label>
                            <select id="contact-subject" name="subject">
                                <option value="informacion">Solicitar información</option>
                                <option value="cita">Agendar una cita</option>
                                <option value="tasacion">Tasación de mi vehículo</option>
                                <option value="financiamiento">Financiamiento</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="contact-message">Mensaje *</label>
                            <textarea id="contact-message" name="message" required placeholder="Cuéntanos en qué podemos ayudarte..." rows="5" maxlength="2000"></textarea>
                        </div>

                        <button type="submit" class="form-submit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            Enviar mensaje
                        </button>
                    </form>
                </div>

                <!-- CONTACT INFO -->
                <div class="contact-info-wrapper">
                    <div class="contact-info-card">
                        <h3>Información de contacto</h3>

                        <div class="contact-info-item">
                            <div class="icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <h4>Dirección</h4>
                                <p><?= e(getSetting('address', 'Tu dirección aquí, Ciudad, España')) ?></p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <h4>Teléfono</h4>
                                <p><a href="tel:+<?= e(WHATSAPP_NUMBER) ?>"><?= e(getSetting('phone', '+34 ' . substr(WHATSAPP_NUMBER, 2, 3) . ' ' . substr(WHATSAPP_NUMBER, 5, 3) . ' ' . substr(WHATSAPP_NUMBER, 8))) ?></a></p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <h4>Email</h4>
                                <p><a href="mailto:<?= e(getSetting('email', 'info@multicar.autos')) ?>"><?= e(getSetting('email', 'info@multicar.autos')) ?></a></p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <h4>Horario</h4>
                                <p><?= nl2br(e(getSetting('hours', "Lun - Vie: 9:00 - 19:00\nSáb: 10:00 - 14:00"))) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="contact-map">
                        <?php
                        $mapEmbed = getSetting('map_embed', '');
                        if ($mapEmbed):
                        ?>
                        <iframe src="<?= e($mapEmbed) ?>" loading="lazy" title="Ubicación de <?= e(SITE_NAME) ?>"></iframe>
                        <?php else: ?>
                        <div class="map-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Ubicación del concesionario</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- WhatsApp CTA -->
                    <div class="contact-whatsapp-cta">
                        <div class="text">
                            <h3>¿Prefieres WhatsApp?</h3>
                            <p>Escríbenos directamente y responderemos al instante.</p>
                        </div>
                        <a href="<?= getWhatsAppLink() ?>" target="_blank" rel="noopener" class="btn-whatsapp-lg">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 0 0 .917.918l4.458-1.495A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 0 1-5.39-1.582l-.386-.234-2.65.889.889-2.65-.234-.386A9.94 9.94 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                            WhatsApp directo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
