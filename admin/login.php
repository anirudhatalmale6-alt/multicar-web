<?php
/**
 * MULTICAR — Admin Login
 */
require_once __DIR__ . '/../includes/init.php';

// Already logged in? Go to dashboard
if (isLoggedIn()) {
    redirect(SITE_URL . '/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Token de seguridad invalido. Recarga la pagina e intentalo de nuevo.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Introduce tu usuario y contrasena.';
        } else {
            $stmt = db()->prepare("SELECT id, username, password, name, role, active FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (!$user['active']) {
                    $error = 'Tu cuenta esta desactivada. Contacta con el administrador.';
                } else {
                    // Regenerate session to prevent fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    redirect(SITE_URL . '/admin/');
                }
            } else {
                $error = 'Usuario o contrasena incorrectos.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — MULTICAR Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/img/favicon.png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1B3A5C 0%, #0f2440 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo .logo-box {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }
        .login-logo .logo-icon {
            width: 56px; height: 56px;
            background: #C8963E;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 26px;
            color: #1B3A5C;
        }
        .login-logo .logo-text {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 2px;
        }
        .login-logo .logo-sub {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 8px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px 36px;
        }
        .login-card h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1B3A5C;
            margin-bottom: 6px;
        }
        .login-card .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            color: #1f2937;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1B3A5C;
            box-shadow: 0 0 0 3px rgba(27,58,92,0.1);
        }
        .form-group input::placeholder { color: #9ca3af; }
        .error-msg {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error-msg svg { width: 18px; height: 18px; flex-shrink: 0; }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #1B3A5C;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            font-family: inherit;
        }
        .btn-login:hover { background: #2a5280; }
        .btn-login:active { transform: scale(0.99); }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            color: rgba(255,255,255,0.4);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="<?= SITE_URL ?>/assets/img/logo-white.png" alt="MULTICAR" style="height:48px;width:auto;margin:0 auto;">
            <div class="logo-sub">Panel de Administracion</div>
        </div>

        <div class="login-card">
            <h2>Iniciar sesion</h2>
            <p class="subtitle">Accede al panel de administracion</p>

            <?php if ($error): ?>
            <div class="error-msg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Tu nombre de usuario"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Contrasena</label>
                    <input type="password" id="password" name="password" placeholder="Tu contrasena"
                           required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-login">Acceder</button>
            </form>
        </div>

        <div class="login-footer">
            &copy; <?= date('Y') ?> MULTICAR — Todos los derechos reservados
        </div>
    </div>
</body>
</html>
