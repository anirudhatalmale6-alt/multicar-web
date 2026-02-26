<?php
/**
 * MULTICAR — Admin User Management
 */
require_once __DIR__ . '/../includes/init.php';
requireLogin();

// Admin only
if (!isAdmin()) {
    flash('error', 'No tienes permisos para acceder a esta seccion.');
    redirect(SITE_URL . '/admin/');
}

define('ADMIN_LOADED', true);
$adminTitle = 'Usuarios';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Token de seguridad invalido.');
        redirect(SITE_URL . '/admin/users.php');
    }

    $action = $_POST['action'] ?? '';

    // CREATE user
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'editor';

        $errors = [];
        if ($username === '') $errors[] = 'El nombre de usuario es obligatorio.';
        if (strlen($password) < 6) $errors[] = 'La contrasena debe tener al menos 6 caracteres.';
        if ($name === '') $errors[] = 'El nombre es obligatorio.';
        if (!in_array($role, ['admin','editor'])) $errors[] = 'Rol no valido.';

        // Check duplicate username
        if ($username !== '') {
            $check = db()->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                $errors[] = 'Ese nombre de usuario ya existe.';
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $err) flash('error', $err);
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare("INSERT INTO users (username, password, name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $name, $email ?: null, $role]);
            flash('success', 'Usuario creado correctamente.');
        }
        redirect(SITE_URL . '/admin/users.php');
    }

    // UPDATE user
    if ($action === 'update') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $role   = $_POST['role'] ?? 'editor';
        $active = isset($_POST['active']) ? 1 : 0;
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0 || $name === '') {
            flash('error', 'Datos invalidos.');
        } else {
            if (!in_array($role, ['admin','editor'])) $role = 'editor';

            // Prevent deactivating yourself
            if ($userId === (int)$_SESSION['user_id'] && !$active) {
                flash('error', 'No puedes desactivar tu propia cuenta.');
                redirect(SITE_URL . '/admin/users.php');
            }

            $stmt = db()->prepare("UPDATE users SET name = ?, email = ?, role = ?, active = ? WHERE id = ?");
            $stmt->execute([$name, $email ?: null, $role, $active, $userId]);

            // Update password if provided
            if ($newPassword !== '' && strlen($newPassword) >= 6) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $userId]);
            } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
                flash('warning', 'La contrasena debe tener al menos 6 caracteres. No se ha cambiado.');
            }

            flash('success', 'Usuario actualizado correctamente.');
        }
        redirect(SITE_URL . '/admin/users.php');
    }

    // DELETE user
    if ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$_SESSION['user_id']) {
            flash('error', 'No puedes eliminar tu propia cuenta.');
        } elseif ($userId > 0) {
            $stmt = db()->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            flash('success', 'Usuario eliminado.');
        }
        redirect(SITE_URL . '/admin/users.php');
    }
}

// Load users
$users = db()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Editing user?
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="grid-2">
    <!-- User List -->
    <div class="card">
        <div class="card-header">
            <h2>Lista de usuarios</h2>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th style="width:100px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600"><?= e($u['name']) ?></div>
                            <?php if ($u['email']): ?>
                                <div class="text-sm text-muted"><?= e($u['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm"><?= e($u['username']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'badge-blue' : 'badge-gray' ?>">
                                <?= $u['role'] === 'admin' ? 'Admin' : 'Editor' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $u['active'] ? 'badge-green' : 'badge-red' ?>">
                                <?= $u['active'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="text-sm text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= SITE_URL ?>/admin/users.php?edit=<?= $u['id'] ?>" class="btn btn-xs btn-outline" data-tooltip="Editar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirmDelete('Eliminar al usuario <?= e($u['name']) ?>?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-xs btn-danger" data-tooltip="Eliminar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit User Form -->
    <div>
        <?php if ($editUser): ?>
        <!-- Edit User -->
        <div class="card">
            <div class="card-header">
                <h2>Editar usuario</h2>
                <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-xs btn-outline">Cancelar</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">

                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" class="form-control" value="<?= e($editUser['username']) ?>" disabled>
                        <div class="hint">El nombre de usuario no se puede cambiar</div>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Nombre completo *</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required
                               value="<?= e($editUser['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control"
                               value="<?= e($editUser['email'] ?? '') ?>">
                    </div>
                    <div class="form-row mb-2">
                        <div class="form-group">
                            <label for="edit_role">Rol</label>
                            <select id="edit_role" name="role" class="form-control">
                                <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="editor" <?= $editUser['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="form-check" style="margin-top:6px">
                                <input type="checkbox" id="edit_active" name="active" value="1"
                                       <?= $editUser['active'] ? 'checked' : '' ?>>
                                <label for="edit_active">Cuenta activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">Nueva contrasena</label>
                        <input type="password" id="edit_password" name="new_password" class="form-control"
                               placeholder="Dejar vacio para no cambiar" autocomplete="new-password">
                        <div class="hint">Minimo 6 caracteres. Dejar vacio si no quieres cambiarla.</div>
                    </div>
                    <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center">
                        Guardar cambios
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Add New User -->
        <div class="card">
            <div class="card-header">
                <h2>Nuevo usuario</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label for="new_username">Nombre de usuario *</label>
                        <input type="text" id="new_username" name="username" class="form-control" required
                               placeholder="Ej: jgarcia" autocomplete="off"
                               pattern="[a-zA-Z0-9_]+" title="Solo letras, numeros y guion bajo">
                        <div class="hint">Sin espacios ni caracteres especiales</div>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Contrasena *</label>
                        <input type="password" id="new_password" name="password" class="form-control" required
                               placeholder="Minimo 6 caracteres" minlength="6" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="new_name">Nombre completo *</label>
                        <input type="text" id="new_name" name="name" class="form-control" required
                               placeholder="Ej: Juan Garcia">
                    </div>
                    <div class="form-group">
                        <label for="new_email">Email</label>
                        <input type="email" id="new_email" name="email" class="form-control"
                               placeholder="Ej: juan@multicar.autos">
                    </div>
                    <div class="form-group">
                        <label for="new_role">Rol</label>
                        <select id="new_role" name="role" class="form-control">
                            <option value="editor">Editor — puede gestionar vehiculos y leads</option>
                            <option value="admin">Administrador — acceso completo</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        Crear usuario
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
