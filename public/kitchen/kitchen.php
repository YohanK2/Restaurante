<?php
require_once __DIR__ . '/../../backend/core/auth.php';
requireRole(['admin', 'cook']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina - Restaurant Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cocina.css">
</head>
<body>

    <!-- Kitchen Header (no sidebar, full-width layout) -->
    <header class="kitchen-header">
        <div class="logo-area">
            <div class="icon">👨‍🍳</div>
            <h1>
                Pantalla de Cocina
                <small>Hola, <?= htmlspecialchars($user['name']) ?></small>
            </h1>
        </div>
        <div class="kitchen-stats">
            <div class="kitchen-stat">
                <div class="value" style="color:var(--warning)" id="count-pending">0</div>
                <div class="label">Pendientes</div>
            </div>
            <div class="kitchen-stat">
                <div class="value" style="color:var(--info)" id="count-preparing">0</div>
                <div class="label">Preparando</div>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:center">
                <button class="btn btn-ghost btn-sm" onclick="loadQueue()">🔄 Actualizar</button>
                <a href="/proyecto/index.php?logout=1" class="btn btn-ghost btn-sm">🚪 Salir</a>
            </div>
        </div>
    </header>

    <div class="kitchen-body">
        <!-- Pending Orders -->
        <div class="queue-section" id="section-pending">
            <h2>🔴 Nuevas Órdenes <span class="badge badge-pending pulse" id="badge-pending">0</span></h2>
            <div class="order-queue" id="queue-pending"></div>
        </div>

        <!-- Preparing Orders -->
        <div class="queue-section" id="section-preparing">
            <h2>🟡 En Preparación <span class="badge badge-preparing" id="badge-preparing">0</span></h2>
            <div class="order-queue" id="queue-preparing"></div>
        </div>
    </div>

    <script src="../assets/js/utils.js"></script>
    <script src="queue.js"></script>
</body>
</html>
