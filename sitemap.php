<?php
/**
 * MULTICAR — Dynamic Sitemap XML
 */
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/inventario</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/contacto</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
<?php
$stmt = db()->query("SELECT slug, updated_at FROM vehicles WHERE status IN ('disponible','reservado') ORDER BY updated_at DESC");
while ($v = $stmt->fetch()):
?>
    <url>
        <loc><?= SITE_URL ?>/vehiculo/<?= e($v['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($v['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endwhile; ?>
</urlset>
