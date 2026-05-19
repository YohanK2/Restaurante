<?php
require_once __DIR__ . '/../../backend/core/auth.php';
requireRole(['admin', 'server']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesero - Restaurant Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="icon">🍽️</div>
            <div>
                <h2>Restaurant</h2>
                <small>Panel Mesero</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-item active" data-tab="tab-new-order" onclick="switchSection(this)">
                <span class="icon">➕</span> Nueva Orden
            </a>
            <a class="nav-item" data-tab="tab-active" onclick="switchSection(this)">
                <span class="icon">📋</span> Órdenes Activas
            </a>
            <a class="nav-item" data-tab="tab-history" onclick="switchSection(this)">
                <span class="icon">📜</span> Historial
            </a>
            <div class="nav-divider"></div>
            <a class="nav-item" href="/proyecto/index.php?logout=1">
                <span class="icon">🚪</span> Cerrar Sesión
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div>
                    <div class="name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="role-label">Mesero</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">

        <!-- NEW ORDER TAB -->
        <section id="tab-new-order" class="tab-section active">
            <div class="page-header">
                <div>
                    <h1>Nueva Orden</h1>
                    <p class="subtitle">Selecciona mesa y platillos</p>
                </div>
            </div>

            <div class="grid-2" style="grid-template-columns: 1fr 380px;">
                <!-- Menu Selection -->
                <div>
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3>Menú</h3>
                            <div class="tab-nav" style="margin-bottom:0">
                                <button class="tab-btn active" onclick="filterMenu('all', this)">Todos</button>
                                <button class="tab-btn" onclick="filterMenu('entrada', this)">Entradas</button>
                                <button class="tab-btn" onclick="filterMenu('plato_fuerte', this)">Fuertes</button>
                                <button class="tab-btn" onclick="filterMenu('bebida', this)">Bebidas</button>
                                <button class="tab-btn" onclick="filterMenu('postre', this)">Postres</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="menu-grid" id="menu-grid"></div>
                        </div>
                    </div>
                </div>

                <!-- Order Cart -->
                <div>
                    <div class="card" style="position:sticky;top:1rem">
                        <div class="card-header">
                            <h3>🛒 Orden Actual</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Mesa</label>
                                <select class="form-control" id="order-table">
                                    <?php for ($i = 1; $i <= TABLES_COUNT; $i++): ?>
                                    <option value="<?= $i ?>">Mesa <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div id="cart-items" style="margin:1rem 0">
                                <div class="empty-state">
                                    <div class="icon">🛒</div>
                                    <p>Selecciona platillos del menú</p>
                                </div>
                            </div>

                            <div style="border-top:1px solid var(--border);padding-top:1rem;margin-top:1rem">
                                <div class="flex-between" style="margin-bottom:0.3rem">
                                    <span style="color:var(--text-muted)">Subtotal</span>
                                    <span id="cart-subtotal">$0.00</span>
                                </div>
                                <div class="flex-between" style="margin-bottom:0.3rem">
                                    <span style="color:var(--text-muted)">Impuesto (16%)</span>
                                    <span id="cart-tax">$0.00</span>
                                </div>
                                <div class="flex-between" style="font-size:1.2rem;font-weight:700;margin-top:0.5rem">
                                    <span>Total</span>
                                    <span style="color:var(--success)" id="cart-total">$0.00</span>
                                </div>
                            </div>

                            <div class="form-group mt-2">
                                <label>Notas</label>
                                <input type="text" class="form-control" id="order-notes" placeholder="Instrucciones especiales...">
                            </div>

                            <button class="btn btn-primary" style="width:100%;margin-top:0.5rem" onclick="submitOrder()" id="btn-submit-order" disabled>
                                Enviar a Cocina 🔥
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ACTIVE ORDERS TAB -->
        <section id="tab-active" class="tab-section">
            <div class="page-header">
                <div>
                    <h1>Órdenes Activas</h1>
                    <p class="subtitle">Tus órdenes en proceso</p>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="loadActiveOrders()">🔄 Actualizar</button>
            </div>

            <div id="notifications-bar" style="margin-bottom:1.5rem"></div>
            <div class="order-queue" id="active-orders-grid"></div>
        </section>

        <!-- HISTORY TAB -->
        <section id="tab-history" class="tab-section">
            <div class="page-header">
                <div>
                    <h1>Historial</h1>
                    <p class="subtitle">Órdenes completadas</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body table-container">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Mesa</th><th>Estado</th><th>Total</th><th>Fecha</th><th>Acciones</th></tr>
                        </thead>
                        <tbody id="history-table"></tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="modal-payment">
    <div class="modal">
        <h2>Procesar Pago</h2>
        <input type="hidden" id="pay-order-id">
        <div id="pay-order-total" style="font-size:1.5rem;font-weight:700;color:var(--success);text-align:center;margin:1rem 0"></div>
        <div class="form-group">
            <label>Método de Pago</label>
            <select class="form-control" id="pay-method">
                <option value="cash">Efectivo 💵</option>
                <option value="card">Tarjeta 💳</option>
                <option value="transfer">Transferencia 📱</option>
            </select>
        </div>
        <div class="form-group">
            <label>Referencia (opcional)</label>
            <input type="text" class="form-control" id="pay-reference" placeholder="Número de referencia">
        </div>
        <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:1.5rem">
            <button class="btn btn-ghost" onclick="closeModal('modal-payment')">Cancelar</button>
            <button class="btn btn-success" onclick="processPayment()">Confirmar Pago ✅</button>
        </div>
    </div>
</div>

<script src="../assets/js/utils.js"></script>
<script src="orders.js"></script>
</body>
</html>
