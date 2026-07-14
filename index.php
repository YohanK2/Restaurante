<?php
/**
 * Main Entry Point - Login & Routing
 */

require_once __DIR__ . '/backend/core/auth.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        $dashboard = getRoleDashboard($_SESSION['role']);
        header("Location: $dashboard");
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    header('Location: /index.php');
    exit;
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getRoleDashboard($_SESSION['role']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Manager - Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/style.css">
    <link rel="stylesheet" href="/public/assets/css/principal.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">🍽️</div>
                <h1>Restaurant Manager</h1>
                <p>Sistema de Gestión de Órdenes</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Ingresa tu usuario" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-login">Iniciar Sesión</button>
            </form>

            <div class="login-footer">
                <p><strong>Cuentas de prueba:</strong></p>
                <p>admin / admin123 · server1 / server123 · cook1 / cook123</p>
            </div>
        </div>
    </div>
</body>
</html>
