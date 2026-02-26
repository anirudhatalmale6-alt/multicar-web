<?php
/**
 * MULTICAR — Shared Header Template
 *
 * Variables expected:
 *   $pageTitle       — Page title for <title> tag
 *   $pageDescription — Page meta description
 *   $bodyClass       — Optional body class
 *   $headerSolid     — If true, header has solid background (for inner pages)
 *   $activePage      — Current page identifier for nav highlighting
 *   $ogImage         — Optional Open Graph image URL
 */

$pageTitle       = $pageTitle       ?? SITE_NAME . ' — ' . SITE_TAGLINE;
$pageDescription = $pageDescription ?? SITE_NAME . ' — Tu concesionario de confianza para vehículos usados. Compra, venta, alquiler y renting con garantía.';
$bodyClass       = $bodyClass       ?? '';
$headerSolid     = $headerSolid     ?? false;
$activePage      = $activePage      ?? '';
$ogImage         = $ogImage         ?? SITE_URL . '/assets/img/og-default.jpg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:url" content="<?= e(SITE_URL . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <!-- Canonical -->
    <link rel="canonical" href="<?= e(SITE_URL . strtok($_SERVER['REQUEST_URI'], '?')) ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/img/favicon.svg">
</head>
<body class="<?= e($bodyClass) ?>">

    <!-- ═══ HEADER ═══ -->
    <header class="header<?= $headerSolid ? ' header-solid' : '' ?>" id="header">
        <div class="header-inner">
            <a href="<?= SITE_URL ?>/" class="logo">
                <svg viewBox="0 0 48 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 38L16 6L24 22L32 6L44 38" stroke="white" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M12 38L16 28" stroke="white" stroke-width="5" stroke-linecap="round"/>
                </svg>
                <span class="logo-text">MULTICAR</span>
            </a>

            <nav class="nav" id="mainNav">
                <a href="<?= SITE_URL ?>/"<?= $activePage === 'inicio' ? ' class="active"' : '' ?>>Inicio</a>
                <a href="<?= SITE_URL ?>/inventario"<?= $activePage === 'inventario' ? ' class="active"' : '' ?>>Inventario</a>
                <a href="<?= SITE_URL ?>/contacto"<?= $activePage === 'contacto' ? ' class="active"' : '' ?>>Contacto</a>
            </nav>

            <a href="<?= getWhatsAppLink() ?>" target="_blank" rel="noopener" class="header-cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Contáctanos
            </a>

            <button class="menu-toggle" id="menuToggle" aria-label="Menú">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>
