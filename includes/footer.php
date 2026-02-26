    <!-- ═══ FOOTER ═══ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="<?= SITE_URL ?>/" class="logo">
                        <img src="<?= SITE_URL ?>/assets/img/logo-white.png" alt="MULTICAR" class="logo-img" style="height:40px;width:auto">
                    </a>
                    <p>Tu concesionario de confianza. Compra, venta, alquiler y renting de vehículos con garantía y profesionalismo.</p>
                    <div class="footer-social">
                        <?php if (SOCIAL_FACEBOOK && SOCIAL_FACEBOOK !== '#'): ?>
                        <a href="<?= e(SOCIAL_FACEBOOK) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <?php else: ?>
                        <a href="#" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php if (SOCIAL_INSTAGRAM && SOCIAL_INSTAGRAM !== '#'): ?>
                        <a href="<?= e(SOCIAL_INSTAGRAM) ?>" target="_blank" rel="noopener" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        </a>
                        <?php else: ?>
                        <a href="#" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php if (SOCIAL_TIKTOK && SOCIAL_TIKTOK !== '#'): ?>
                        <a href="<?= e(SOCIAL_TIKTOK) ?>" target="_blank" rel="noopener" aria-label="TikTok">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1 0-5.78 2.92 2.92 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 3 15.57 6.33 6.33 0 0 0 9.37 22a6.33 6.33 0 0 0 6.37-6.22V9.34a8.16 8.16 0 0 0 3.85.97V6.69z"/></svg>
                        </a>
                        <?php else: ?>
                        <a href="#" aria-label="TikTok">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1 0-5.78 2.92 2.92 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 3 15.57 6.33 6.33 0 0 0 9.37 22a6.33 6.33 0 0 0 6.37-6.22V9.34a8.16 8.16 0 0 0 3.85.97V6.69z"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Navegación</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/">Inicio</a></li>
                        <li><a href="<?= SITE_URL ?>/inventario">Inventario</a></li>
                        <li><a href="<?= SITE_URL ?>/contacto">Contacto</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Servicios</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/inventario">Compra de vehículos</a></li>
                        <li><a href="<?= SITE_URL ?>/contacto">Venta de vehículos</a></li>
                        <li><a href="<?= SITE_URL ?>/contacto">Alquiler</a></li>
                        <li><a href="<?= SITE_URL ?>/contacto">Renting</a></li>
                        <li><a href="<?= SITE_URL ?>/contacto">Financiamiento</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contacto</h4>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span><?= e(getSetting('address', 'Tu dirección aquí, Ciudad, España')) ?></span>
                    </div>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span><?= e(getSetting('phone', '+34 ' . substr(WHATSAPP_NUMBER, 2, 3) . ' ' . substr(WHATSAPP_NUMBER, 5, 3) . ' ' . substr(WHATSAPP_NUMBER, 8))) ?></span>
                    </div>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span><?= e(getSetting('hours', 'Lun – Vie: 9:00 – 19:00 / Sáb: 10:00 – 14:00')) ?></span>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Todos los derechos reservados.</span>
                <span>
                    <a href="<?= SITE_URL ?>/privacidad" style="margin-right:20px">Política de Privacidad</a>
                    <a href="<?= SITE_URL ?>/aviso-legal">Aviso Legal</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- ═══ WHATSAPP FLOAT ═══ -->
    <a href="<?= getWhatsAppLink() ?>" target="_blank" rel="noopener" class="whatsapp-float" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 0 0 .917.918l4.458-1.495A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 0 1-5.39-1.582l-.386-.234-2.65.889.889-2.65-.234-.386A9.94 9.94 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        <span class="tooltip">¿Necesitas ayuda? Escríbenos</span>
    </a>

    <!-- ═══ BACK TO TOP ═══ -->
    <button class="back-to-top" id="backToTop" aria-label="Volver arriba">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
    </button>

    <!-- ═══ FLASH MESSAGES ═══ -->
    <?php $flashes = getFlash(); if (!empty($flashes)): ?>
    <div class="flash-messages">
        <?php foreach ($flashes as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ SCRIPTS ═══ -->
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
