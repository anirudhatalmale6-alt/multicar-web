<?php
/**
 * MULTICAR — Admin Create / Edit Vehicle
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();
define('ADMIN_LOADED', true);

$vehicleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $vehicleId > 0;
$vehicle = null;
$images = [];

if ($isEdit) {
    $stmt = db()->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) {
        flash('error', 'Vehiculo no encontrado.');
        redirect(SITE_URL . '/admin/vehicles.php');
    }
    $images = getVehicleImages($vehicleId);
}

$adminTitle = $isEdit ? 'Editar vehiculo' : 'Nuevo vehiculo';

// Sanitize HTML for Quill output (allow safe tags only)
function sanitizeQuillHtml($html) {
    $allowed = '<p><br><strong><em><u><s><h1><h2><h3><ol><ul><li><a><span><sub><sup><blockquote><pre>';
    return strip_tags($html, $allowed);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Token de seguridad invalido.');
        redirect($_SERVER['REQUEST_URI']);
    }

    // Collect fields
    $brand        = trim($_POST['brand'] ?? '');
    $model        = trim($_POST['model'] ?? '');
    $version      = trim($_POST['version'] ?? '');
    $year         = (int)($_POST['year'] ?? date('Y'));
    $price        = floatval(str_replace(['.', ','], ['', '.'], $_POST['price'] ?? '0'));
    $mileage      = (int)str_replace(['.', ','], '', $_POST['mileage'] ?? '0');
    $fuel         = $_POST['fuel'] ?? 'gasolina';
    $transmission = $_POST['transmission'] ?? 'manual';
    $power_hp     = (int)($_POST['power_hp'] ?? 0) ?: null;
    $doors        = (int)($_POST['doors'] ?? 5);
    $color        = trim($_POST['color'] ?? '');
    $body_type    = $_POST['body_type'] ?? 'sedan';
    $description  = sanitizeQuillHtml(trim($_POST['description'] ?? ''));
    $features     = sanitizeQuillHtml(trim($_POST['features'] ?? ''));
    $video_url    = trim($_POST['video_url'] ?? '');
    $warranty     = trim($_POST['warranty'] ?? '');
    $sale_type    = $_POST['sale_type'] ?? 'rebu';
    $badge        = trim($_POST['badge'] ?? '');
    $status       = $_POST['status'] ?? 'disponible';
    $featured     = isset($_POST['featured']) ? 1 : 0;
    $meta_title   = trim($_POST['meta_title'] ?? '');
    $meta_desc    = trim($_POST['meta_description'] ?? '');

    // Validate
    $errors = [];
    if ($brand === '') $errors[] = 'La marca es obligatoria.';
    if ($model === '') $errors[] = 'El modelo es obligatorio.';
    if ($year < 1900 || $year > date('Y') + 2) $errors[] = 'Ano no valido.';
    if ($price <= 0) $errors[] = 'El precio debe ser mayor que 0.';
    if (!in_array($fuel, ['gasolina','diesel','hibrido','electrico','glp'])) $errors[] = 'Combustible no valido.';
    if (!in_array($transmission, ['manual','automatico'])) $errors[] = 'Transmision no valida.';
    if (!in_array($body_type, ['sedan','suv','hatchback','coupe','cabrio','familiar','monovolumen','furgoneta','pick-up','otro'])) $errors[] = 'Tipo de carroceria no valido.';
    if (!in_array($status, ['disponible','reservado','vendido'])) $errors[] = 'Estado no valido.';
    if (!in_array($sale_type, ['rebu','iva_incluido'])) $errors[] = 'Tipo de venta no valido.';

    if (!empty($errors)) {
        foreach ($errors as $err) flash('error', $err);
        // Keep POST data to repopulate
    } else {
        if ($isEdit) {
            // Check if brand/model/year changed for slug
            if ($brand !== $vehicle['brand'] || $model !== $vehicle['model'] || $year !== (int)$vehicle['year']) {
                $slug = generateSlug($brand, $model, $year);
            } else {
                $slug = $vehicle['slug'];
            }

            $stmt = db()->prepare("UPDATE vehicles SET
                slug=?, brand=?, model=?, version=?, year=?, price=?, mileage=?,
                fuel=?, transmission=?, power_hp=?, doors=?, color=?, body_type=?,
                description=?, features=?, video_url=?, warranty=?, sale_type=?,
                badge=?, status=?, featured=?,
                meta_title=?, meta_description=?
                WHERE id = ?");
            $stmt->execute([
                $slug, $brand, $model, $version, $year, $price, $mileage,
                $fuel, $transmission, $power_hp, $doors, $color, $body_type,
                $description, $features, $video_url, $warranty, $sale_type,
                $badge, $status, $featured,
                $meta_title, $meta_desc, $vehicleId
            ]);
            $savedId = $vehicleId;
            flash('success', 'Vehiculo actualizado correctamente.');
        } else {
            $slug = generateSlug($brand, $model, $year);
            $stmt = db()->prepare("INSERT INTO vehicles
                (slug, brand, model, version, year, price, mileage, fuel, transmission,
                 power_hp, doors, color, body_type, description, features, video_url,
                 warranty, sale_type, badge, status, featured, meta_title, meta_description, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $slug, $brand, $model, $version, $year, $price, $mileage,
                $fuel, $transmission, $power_hp, $doors, $color, $body_type,
                $description, $features, $video_url, $warranty, $sale_type,
                $badge, $status, $featured,
                $meta_title, $meta_desc, $_SESSION['user_id']
            ]);
            $savedId = db()->lastInsertId();
            flash('success', 'Vehiculo creado correctamente.');
        }

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            // Ensure upload directory exists
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0775, true);
            }

            $existingCount = db()->prepare("SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = ?");
            $existingCount->execute([$savedId]);
            $sortStart = (int)$existingCount->fetchColumn();

            $hasAnyCover = db()->prepare("SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = ? AND is_cover = 1");
            $hasAnyCover->execute([$savedId]);
            $noCover = $hasAnyCover->fetchColumn() == 0;

            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                ];
                $makeCover = ($noCover && $i === 0);
                uploadVehicleImage($file, $savedId, $sortStart + $i, $makeCover);
            }
        }

        redirect(SITE_URL . '/admin/vehicle_edit.php?id=' . $savedId);
    }
}

// For repopulating form after validation error
$f = $_POST ?: ($vehicle ?: []);

include __DIR__ . '/includes/admin_header.php';
?>

<!-- Quill.js Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<style>
    .quill-editor {
        background: var(--white);
        border-radius: 0 0 var(--radius) var(--radius);
        min-height: 200px;
    }
    .quill-editor .ql-editor {
        min-height: 200px;
        font-size: 14px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: var(--gray-800);
        line-height: 1.6;
    }
    .quill-editor .ql-editor.ql-blank::before {
        color: var(--gray-400);
        font-style: normal;
    }
    /* Toolbar styling to match admin navy/gold theme */
    .ql-toolbar.ql-snow {
        border: 1px solid var(--gray-300);
        border-radius: var(--radius) var(--radius) 0 0;
        background: var(--gray-50);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
    .ql-container.ql-snow {
        border: 1px solid var(--gray-300);
        border-top: none;
        border-radius: 0 0 var(--radius) var(--radius);
    }
    .ql-toolbar.ql-snow .ql-picker-label:hover,
    .ql-toolbar.ql-snow button:hover,
    .ql-toolbar.ql-snow button:focus,
    .ql-toolbar.ql-snow .ql-active {
        color: var(--navy) !important;
    }
    .ql-toolbar.ql-snow button:hover .ql-stroke,
    .ql-toolbar.ql-snow button:focus .ql-stroke,
    .ql-toolbar.ql-snow .ql-active .ql-stroke,
    .ql-toolbar.ql-snow .ql-picker-label:hover .ql-stroke {
        stroke: var(--navy) !important;
    }
    .ql-toolbar.ql-snow button:hover .ql-fill,
    .ql-toolbar.ql-snow button:focus .ql-fill,
    .ql-toolbar.ql-snow .ql-active .ql-fill,
    .ql-toolbar.ql-snow .ql-picker-label:hover .ql-fill {
        fill: var(--navy) !important;
    }
    .ql-snow .ql-editor a {
        color: var(--gold);
    }
    /* Focus state */
    .ql-container.ql-snow:focus-within {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(27,58,92,0.1);
    }
    .ql-container.ql-snow:focus-within + .ql-toolbar.ql-snow,
    .ql-toolbar.ql-snow:has(+ .ql-container.ql-snow:focus-within) {
        border-color: var(--navy);
    }
</style>

<div class="d-flex justify-between items-center mb-3 flex-wrap gap-2">
    <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-outline btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a vehiculos
    </a>
    <?php if ($isEdit): ?>
        <a href="<?= SITE_URL ?>/vehiculo/<?= e($vehicle['slug']) ?>" target="_blank" class="btn btn-outline btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Ver en la web
        </a>
    <?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data" id="vehicleForm">
    <?= csrfField() ?>

    <div class="grid-2">
        <!-- Left Column: Main Data -->
        <div>
            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Informacion basica</h2>
                </div>
                <div class="card-body">
                    <div class="form-row mb-2">
                        <div class="form-group">
                            <label for="brand">Marca *</label>
                            <input type="text" id="brand" name="brand" class="form-control" required
                                   value="<?= e($f['brand'] ?? '') ?>"
                                   placeholder="Ej: Volkswagen, BMW, Audi...">
                        </div>
                        <div class="form-group">
                            <label for="model">Modelo *</label>
                            <input type="text" id="model" name="model" class="form-control" required
                                   value="<?= e($f['model'] ?? '') ?>"
                                   placeholder="Ej: Golf, Serie 3, A4...">
                        </div>
                    </div>
                    <div class="form-row mb-2">
                        <div class="form-group">
                            <label for="version">Version / Acabado</label>
                            <input type="text" id="version" name="version" class="form-control"
                                   value="<?= e($f['version'] ?? '') ?>"
                                   placeholder="Ej: 1.6 TDI Advance, 320d M Sport...">
                            <div class="hint">Opcional. Version o linea de acabado del vehiculo</div>
                        </div>
                        <div class="form-group">
                            <label for="year">Ano *</label>
                            <input type="number" id="year" name="year" class="form-control" required
                                   min="1900" max="<?= date('Y') + 2 ?>"
                                   value="<?= e($f['year'] ?? date('Y')) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Precio (EUR) *</label>
                            <input type="text" id="price" name="price" class="form-control" required
                                   value="<?= e($f['price'] ?? '') ?>"
                                   placeholder="Ej: 18500">
                            <div class="hint">Precio sin puntos ni comas (se formatea automaticamente)</div>
                        </div>
                        <div class="form-group">
                            <label for="mileage">Kilometraje</label>
                            <input type="text" id="mileage" name="mileage" class="form-control"
                                   value="<?= e($f['mileage'] ?? '0') ?>"
                                   placeholder="Ej: 85000">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Details -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Detalles tecnicos</h2>
                </div>
                <div class="card-body">
                    <div class="form-row mb-2">
                        <div class="form-group">
                            <label for="fuel">Combustible</label>
                            <select id="fuel" name="fuel" class="form-control">
                                <?php foreach (['gasolina','diesel','hibrido','electrico','glp'] as $fuelOpt): ?>
                                    <option value="<?= $fuelOpt ?>" <?= ($f['fuel'] ?? 'gasolina') === $fuelOpt ? 'selected' : '' ?>>
                                        <?= fuelLabel($fuelOpt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transmission">Transmision</label>
                            <select id="transmission" name="transmission" class="form-control">
                                <option value="manual" <?= ($f['transmission'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>Manual</option>
                                <option value="automatico" <?= ($f['transmission'] ?? '') === 'automatico' ? 'selected' : '' ?>>Automatico</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row mb-2">
                        <div class="form-group">
                            <label for="power_hp">Potencia (CV)</label>
                            <input type="number" id="power_hp" name="power_hp" class="form-control"
                                   value="<?= e($f['power_hp'] ?? '') ?>" min="0"
                                   placeholder="Ej: 150">
                        </div>
                        <div class="form-group">
                            <label for="doors">Puertas</label>
                            <select id="doors" name="doors" class="form-control">
                                <?php foreach ([2,3,4,5] as $d): ?>
                                    <option value="<?= $d ?>" <?= (int)($f['doors'] ?? 5) === $d ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color" class="form-control"
                                   value="<?= e($f['color'] ?? '') ?>"
                                   placeholder="Ej: Blanco, Negro metalizado...">
                        </div>
                        <div class="form-group">
                            <label for="body_type">Carroceria</label>
                            <select id="body_type" name="body_type" class="form-control">
                                <?php foreach (['sedan','suv','hatchback','coupe','cabrio','familiar','monovolumen','furgoneta','pick-up','otro'] as $bt): ?>
                                    <option value="<?= $bt ?>" <?= ($f['body_type'] ?? 'sedan') === $bt ? 'selected' : '' ?>>
                                        <?= bodyTypeLabel($bt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description & Features (Quill Rich Text) -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Descripcion y equipamiento</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Descripcion</label>
                        <div id="quill-description" class="quill-editor"></div>
                        <textarea id="description" name="description" style="display:none"><?= e($f['description'] ?? '') ?></textarea>
                        <div class="hint">Usa el editor para dar formato: negritas, listas, encabezados, etc.</div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Equipamiento / Extras</label>
                        <div id="quill-features" class="quill-editor"></div>
                        <textarea id="features" name="features" style="display:none"><?= e($f['features'] ?? '') ?></textarea>
                        <div class="hint">Usa listas para organizar el equipamiento del vehiculo</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Status, Images, SEO -->
        <div>
            <!-- Status & Visibility -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Estado y visibilidad</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="disponible" <?= ($f['status'] ?? 'disponible') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="reservado" <?= ($f['status'] ?? '') === 'reservado' ? 'selected' : '' ?>>Reservado</option>
                            <option value="vendido" <?= ($f['status'] ?? '') === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="warranty">Garantía</label>
                        <input type="text" id="warranty" name="warranty" class="form-control"
                               value="<?= e($f['warranty'] ?? '') ?>"
                               placeholder="Ej: 12 meses de garantía, 24 meses, Sin garantía...">
                        <div class="hint">Se muestra en negrita en la tarjeta del vehículo</div>
                    </div>
                    <div class="form-group">
                        <label for="sale_type">Tipo de venta</label>
                        <select id="sale_type" name="sale_type" class="form-control">
                            <option value="rebu" <?= ($f['sale_type'] ?? 'rebu') === 'rebu' ? 'selected' : '' ?>>REBU (Régimen especial)</option>
                            <option value="iva_incluido" <?= ($f['sale_type'] ?? '') === 'iva_incluido' ? 'selected' : '' ?>>IVA Incluido</option>
                        </select>
                        <div class="hint">Solo se muestra "IVA Incl." en el precio si seleccionas IVA Incluido</div>
                    </div>
                    <div class="form-group">
                        <label for="badge">Etiqueta especial</label>
                        <input type="text" id="badge" name="badge" class="form-control"
                               value="<?= e($f['badge'] ?? '') ?>"
                               placeholder="Ej: Oferta, Nuevo, KM 0, Garantia...">
                        <div class="hint">Aparece como etiqueta destacada en la ficha del vehiculo</div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="featured" name="featured" value="1"
                               <?= !empty($f['featured']) ? 'checked' : '' ?>>
                        <label for="featured">Vehiculo destacado (aparece en la portada)</label>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Imagenes</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($images)): ?>
                    <div class="image-grid" id="existingImages">
                        <?php foreach ($images as $img): ?>
                        <div class="image-item <?= $img['is_cover'] ? 'is-cover' : '' ?>" id="img-<?= $img['id'] ?>">
                            <?php if ($img['is_cover']): ?>
                                <span class="cover-badge">Portada</span>
                            <?php endif; ?>
                            <img src="<?= UPLOAD_URL . e($img['filename']) ?>" alt="" loading="lazy">
                            <div class="image-actions">
                                <button type="button" class="image-btn btn-cover-img"
                                        onclick="setCover(<?= $img['id'] ?>, <?= $vehicleId ?>)"
                                        data-tooltip="Usar como portada">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </button>
                                <button type="button" class="image-btn btn-delete-img"
                                        onclick="deleteImage(<?= $img['id'] ?>)"
                                        data-tooltip="Eliminar imagen">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:16px;margin-bottom:8px;height:1px;background:var(--gray-200)"></div>
                    <?php endif; ?>

                    <label style="font-size:13px;font-weight:600;color:var(--gray-700);display:block;margin-bottom:8px">
                        Subir nuevas imagenes
                    </label>
                    <div class="upload-zone" id="uploadZone">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <p>Arrastra imagenes aqui o haz clic para seleccionar</p>
                        <div class="upload-hint">JPG, PNG o WebP. Maximo 10 MB por imagen.</div>
                        <input type="file" name="images[]" id="imageInput" multiple
                               accept=".jpg,.jpeg,.png,.webp" style="display:none">
                    </div>
                    <div class="upload-previews" id="uploadPreviews"></div>
                    <?php if ($isEdit): ?>
                        <div class="hint mt-2">La primera imagen subida sera la portada si no hay ninguna seleccionada.</div>
                    <?php else: ?>
                        <div class="hint mt-2">La primera imagen sera la portada automaticamente. Podras cambiarla despues.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Video -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>Video</h2>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="video_url">URL del video (YouTube)</label>
                        <input type="url" id="video_url" name="video_url" class="form-control"
                               value="<?= e($f['video_url'] ?? '') ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <div class="hint">Pega el enlace del video de YouTube del vehiculo</div>
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2>SEO (Avanzado)</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="meta_title">Titulo SEO</label>
                        <input type="text" id="meta_title" name="meta_title" class="form-control"
                               value="<?= e($f['meta_title'] ?? '') ?>"
                               placeholder="Se genera automaticamente si se deja vacio" maxlength="255">
                        <div class="hint">Titulo personalizado para buscadores (max. 70 caracteres recomendados)</div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="meta_description">Descripcion SEO</label>
                        <textarea id="meta_description" name="meta_description" class="form-control" rows="3"
                                  placeholder="Se genera automaticamente si se deja vacio" maxlength="500"><?= e($f['meta_description'] ?? '') ?></textarea>
                        <div class="hint">Descripcion para resultados de busqueda (max. 160 caracteres recomendados)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="d-flex justify-between items-center mt-3" style="padding-bottom:40px">
        <a href="<?= SITE_URL ?>/admin/vehicles.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-gold" style="min-width:200px;justify-content:center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <?= $isEdit ? 'Guardar cambios' : 'Crear vehiculo' ?>
        </button>
    </div>
</form>

<script>
// ===== Quill Rich Text Editors =====
(function(){
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link'],
        ['clean']
    ];

    // Initialize Description editor
    var quillDesc = new Quill('#quill-description', {
        theme: 'snow',
        modules: { toolbar: toolbarOptions },
        placeholder: 'Describe el vehiculo: estado general, equipamiento destacado, historial de mantenimiento...'
    });

    // Initialize Features editor
    var quillFeat = new Quill('#quill-features', {
        theme: 'snow',
        modules: { toolbar: toolbarOptions },
        placeholder: 'Equipamiento y extras del vehiculo. Usa listas para organizar mejor...'
    });

    // Pre-populate with existing content (HTML from database)
    var descField = document.getElementById('description');
    var featField = document.getElementById('features');

    if (descField.value.trim()) {
        quillDesc.root.innerHTML = descField.value;
    }
    if (featField.value.trim()) {
        quillFeat.root.innerHTML = featField.value;
    }

    // On form submit, copy Quill HTML to hidden textareas
    var form = document.getElementById('vehicleForm');
    form.addEventListener('submit', function() {
        // Get the HTML content; if editor is empty, store empty string
        var descHtml = quillDesc.root.innerHTML;
        var featHtml = quillFeat.root.innerHTML;

        // Quill uses <p><br></p> for empty content
        if (descHtml === '<p><br></p>') descHtml = '';
        if (featHtml === '<p><br></p>') featHtml = '';

        descField.value = descHtml;
        featField.value = featHtml;
    });
})();

// ===== Drag & Drop Upload =====
(function(){
    var zone = document.getElementById('uploadZone');
    var input = document.getElementById('imageInput');
    var previews = document.getElementById('uploadPreviews');
    var dataTransfer = new DataTransfer();

    zone.addEventListener('click', function() { input.click(); });

    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', function() {
        zone.classList.remove('dragover');
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', function() {
        addFiles(this.files);
    });

    function addFiles(files) {
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (!file.type.match(/^image\/(jpeg|png|webp)$/)) continue;
            if (file.size > 10 * 1024 * 1024) continue;
            dataTransfer.items.add(file);
            showPreview(file, dataTransfer.items.length - 1);
        }
        input.files = dataTransfer.files;
    }

    function showPreview(file, idx) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'upload-preview';
            div.setAttribute('data-idx', idx);
            div.innerHTML = '<img src="' + e.target.result + '" alt="">' +
                '<button type="button" class="remove-preview" onclick="removePreview(' + idx + ')">&times;</button>';
            previews.appendChild(div);
        };
        reader.readAsDataURL(file);
    }

    window.removePreview = function(idx) {
        // Rebuild DataTransfer without this file
        var newDT = new DataTransfer();
        var items = dataTransfer.files;
        var mapping = [];
        for (var i = 0; i < items.length; i++) {
            if (i !== idx) {
                newDT.items.add(items[i]);
                mapping.push(i);
            }
        }
        dataTransfer = newDT;
        input.files = dataTransfer.files;
        // Re-render previews
        previews.innerHTML = '';
        for (var j = 0; j < dataTransfer.files.length; j++) {
            showPreview(dataTransfer.files[j], j);
        }
    };
})();

// ===== Delete Existing Image (AJAX) =====
function deleteImage(imageId) {
    if (!confirm('Eliminar esta imagen?')) return;
    adminFetch('<?= SITE_URL ?>/admin/api/delete_image.php', {
        image_id: imageId,
        csrf_token: '<?= csrfToken() ?>'
    }).then(function(data) {
        if (data.success) {
            var el = document.getElementById('img-' + imageId);
            if (el) el.remove();
        } else {
            alert(data.error || 'Error al eliminar la imagen.');
        }
    });
}

// ===== Set Cover Image (AJAX) =====
function setCover(imageId, vehicleId) {
    adminFetch('<?= SITE_URL ?>/admin/api/set_cover.php', {
        image_id: imageId,
        vehicle_id: vehicleId,
        csrf_token: '<?= csrfToken() ?>'
    }).then(function(data) {
        if (data.success) {
            // Update UI
            document.querySelectorAll('.image-item').forEach(function(el) {
                el.classList.remove('is-cover');
                var badge = el.querySelector('.cover-badge');
                if (badge) badge.remove();
            });
            var target = document.getElementById('img-' + imageId);
            if (target) {
                target.classList.add('is-cover');
                var badge = document.createElement('span');
                badge.className = 'cover-badge';
                badge.textContent = 'Portada';
                target.insertBefore(badge, target.firstChild);
            }
        }
    });
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
