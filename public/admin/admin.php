<?php
require_once __DIR__ . '/../../backend/core/auth.php';
requireRole('admin');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="app-header">
    <div class="header-left">
        <div class="section-title" id="header-section-title">Dashboard</div>
        <div class="welcome-text">Bienvenido, <?= htmlspecialchars($user['name']) ?></div>
    </div>
    <div class="header-right">
        <button type="button" class="profile-button" id="profileToggle">
            <span class="profile-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
            <span class="profile-info">
                <strong><?= htmlspecialchars($user['name']) ?></strong>
                <small>Administrador</small>
            </span>
            <span class="profile-caret">▾</span>
        </button>
        <div class="profile-menu" id="profileMenu">
            <a href="#" onclick="event.preventDefault()">Mi perfil</a>
            <a href="#" onclick="event.preventDefault()">Configuración</a>
            <a href="/index.php?logout=1">Cerrar sesión</a>
        </div>
    </div>
</header>
<div class="app-layout">

    <!-- ── Sidebar ── -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="icon">🍽️</div>
            <div>
                <h2>Restaurant</h2>
                <small>Panel Admin</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-item active" data-tab="tab-dashboard" onclick="switchSection(this)">
                <span class="icon">📊</span> Dashboard
            </a>
            <a class="nav-item" data-tab="tab-orders" onclick="switchSection(this)">
                <span class="icon">📋</span> Órdenes
            </a>
            <a class="nav-item" data-tab="tab-revenue" onclick="switchSection(this)">
                <span class="icon">💰</span> Ingresos
            </a>
            <a class="nav-item" data-tab="tab-menu" onclick="switchSection(this)">
                <span class="icon">🍔</span> Menú
            </a>
            <a class="nav-item" data-tab="tab-users" onclick="switchSection(this)">
                <span class="icon">👥</span> Usuarios
            </a>
            <div class="nav-divider"></div>
            <a class="nav-item" href="/index.php?logout=1">
                <span class="icon">🚪</span> Cerrar Sesión
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div>
                    <div class="name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="role-label">Administrador</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ── Main Content ── -->
    <main class="main-content">

        <!-- ══════════════════════════════════════
             DASHBOARD TAB
        ══════════════════════════════════════ -->
        <section id="tab-dashboard" class="tab-section active">

            <div class="page-header">
                <div>
                    <h1>Dashboard</h1>
                    <p class="subtitle">Resumen general del restaurante</p>
                </div>
                <button class="btn btn-ghost btn-sm btn-refresh" id="btn-refresh" onclick="handleRefresh(this)">
                    <span class="refresh-icon" id="refresh-icon">🔄</span>
                    Actualizar
                </button>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a class="quick-action-btn" data-tab="tab-orders" onclick="switchSection(this)">
                    <span class="qa-icon">📋</span> Ver Órdenes
                </a>
                <a class="quick-action-btn" data-tab="tab-menu" onclick="switchSection(this)">
                    <span class="qa-icon">🍔</span> Gestionar Menú
                </a>
                <a class="quick-action-btn" data-tab="tab-revenue" onclick="switchSection(this)">
                    <span class="qa-icon">📈</span> Ver Reportes
                </a>
                <a class="quick-action-btn" data-tab="tab-users" onclick="switchSection(this)">
                    <span class="qa-icon">👥</span> Nuevo Usuario
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid" id="stats-grid">

                <!-- Revenue Hoy -->
                <div class="stat-card card-revenue">
                    <div class="stat-card-top">
                        <div class="stat-icon" style="background:var(--success-soft);color:var(--success)">💵</div>
                        <span class="stat-trend flat" id="trend-today-revenue">— vs ayer</span>
                    </div>
                    <div class="stat-value count-up" id="stat-today-revenue">$0.00</div>
                    <div class="stat-label">Ingresos Hoy</div>
                    <div class="stat-sparkline" id="spark-revenue"></div>
                </div>

                <!-- Órdenes Hoy -->
                <div class="stat-card card-orders">
                    <div class="stat-card-top">
                        <div class="stat-icon" style="background:var(--info-soft);color:var(--info)">📦</div>
                        <span class="stat-trend flat" id="trend-today-orders">— hoy</span>
                    </div>
                    <div class="stat-value count-up" id="stat-today-orders">0</div>
                    <div class="stat-label">Órdenes Hoy</div>
                    <div class="stat-sparkline" id="spark-orders"></div>
                </div>

                <!-- Órdenes Activas -->
                <div class="stat-card card-active">
                    <div class="stat-card-top">
                        <div class="stat-icon" style="background:var(--warning-soft);color:var(--warning)">⏳</div>
                        <span class="stat-trend flat" id="trend-active">en curso</span>
                    </div>
                    <div class="stat-value count-up" id="stat-active-orders">0</div>
                    <div class="stat-label">Órdenes Activas</div>
                    <div class="stat-sparkline" id="spark-active"></div>
                </div>

                <!-- Ingresos Mes -->
                <div class="stat-card card-month">
                    <div class="stat-card-top">
                        <div class="stat-icon" style="background:var(--primary-soft);color:var(--primary)">📈</div>
                        <span class="stat-trend flat" id="trend-month">este mes</span>
                    </div>
                    <div class="stat-value count-up" id="stat-month-revenue">$0.00</div>
                    <div class="stat-label">Ingresos del Mes</div>
                    <div class="stat-sparkline" id="spark-month"></div>
                </div>

                <!-- Ticket Promedio -->
                <div class="stat-card card-avg">
                    <div class="stat-card-top">
                        <div class="stat-icon" style="background:var(--danger-soft);color:var(--danger)">🎯</div>
                        <span class="stat-trend flat" id="trend-avg">promedio</span>
                    </div>
                    <div class="stat-value count-up" id="stat-avg-order">$0.00</div>
                    <div class="stat-label">Ticket Promedio</div>
                    <div class="stat-sparkline" id="spark-avg"></div>
                </div>

                <!-- Mesas -->
                <div class="stat-card card-tables">
                    <div class="stat-card-top">
                        <div class="stat-icon" style="background:rgba(168,85,247,0.12);color:#a855f7">🪑</div>
                        <span class="stat-trend flat" id="trend-tables">hoy</span>
                    </div>
                    <div class="stat-value count-up" id="stat-tables">0</div>
                    <div class="stat-label">Mesas Atendidas</div>
                    <div class="stat-sparkline" id="spark-tables"></div>
                </div>

            </div><!-- /stats-grid -->

            <!-- Charts Row -->
            <div class="grid-2" style="margin-bottom:1.5rem">

                <!-- Revenue Chart -->
                <div class="card">
                    <div class="card-header"><h3>📊 Ingresos Últimos 7 Días</h3></div>
                    <div class="card-body">
                        <div class="chart-container" id="revenue-chart-wrap">
                            <div class="chart-bar-group" id="revenue-chart"></div>
                            <div class="chart-labels" id="revenue-labels"></div>
                        </div>
                        <!-- Empty state (hidden when data loads) -->
                        <div class="empty-state" id="chart-empty" style="display:none">
                            <div class="empty-state-icon">📉</div>
                            <div class="empty-state-title">Sin datos esta semana</div>
                            <div class="empty-state-desc">Las ventas registradas aparecerán aquí como barras de ingresos diarios.</div>
                        </div>
                    </div>
                </div>

                <!-- Top Items -->
                <div class="card">
                    <div class="card-header"><h3>🏆 Más Vendidos</h3></div>
                    <div class="card-body" style="padding-top:0.75rem">
                        <div id="top-items-list">
                            <!-- Empty state while loading -->
                            <div class="empty-state" id="top-items-empty">
                                <div class="empty-state-icon">🏅</div>
                                <div class="empty-state-title">Aún no hay ranking</div>
                                <div class="empty-state-desc">Tus platillos más vendidos aparecerán aquí cuando se registren órdenes.</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /grid-2 charts -->

            <!-- Activity Feed Row -->
            <div class="card">
                <div class="card-header">
                    <h3>⚡ Actividad Reciente</h3>
                    <span style="font-size:0.75rem;color:var(--text-muted)" id="last-updated-label"></span>
                </div>
                <div class="card-body" style="padding-top:0.5rem;padding-bottom:0.5rem">
                    <div id="activity-feed">
                        <div class="empty-state">
                            <div class="empty-state-icon">📡</div>
                            <div class="empty-state-title">Sin actividad reciente</div>
                            <div class="empty-state-desc">Aquí verás las últimas órdenes, pagos y cambios de estado en tiempo real.</div>
                        </div>
                    </div>
                </div>
            </div>

        </section><!-- /tab-dashboard -->


        <!-- ══════════════════════════════════════
             ORDERS TAB
        ══════════════════════════════════════ -->
        <section id="tab-orders" class="tab-section">
            <div class="page-header">
                <div>
                    <h1>Historial de Órdenes</h1>
                    <p class="subtitle">Todas las órdenes del sistema</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center">
                    <select class="form-control" id="filter-status" onchange="loadOrders()" style="width:auto">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="preparing">Preparando</option>
                        <option value="ready">Lista</option>
                        <option value="served">Servida</option>
                        <option value="paid">Pagada</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                    <button class="btn btn-ghost btn-sm" onclick="loadOrders()">🔄</button>
                </div>
            </div>
            <div class="card">
                <div class="card-body table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mesa</th>
                                <th>Mesero</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body"></tbody>
                    </table>
                    <div class="empty-state" id="orders-empty" style="display:none">
                        <div class="empty-state-icon">📋</div>
                        <div class="empty-state-title">No hay órdenes</div>
                        <div class="empty-state-desc">Las órdenes creadas por los meseros aparecerán aquí.</div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════
             REVENUE TAB
        ══════════════════════════════════════ -->
        <section id="tab-revenue" class="tab-section">
            <div class="page-header">
                <div>
                    <h1>Reporte de Ingresos</h1>
                    <p class="subtitle">Análisis financiero detallado</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center">
                    <input type="date" class="form-control" id="revenue-from" style="width:auto">
                    <input type="date" class="form-control" id="revenue-to" style="width:auto">
                    <button class="btn btn-primary btn-sm" onclick="loadRevenueReport()">Consultar</button>
                </div>
            </div>
            <div class="card">
                <div class="card-body table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Transacciones</th>
                                <th>Ingreso Total</th>
                                <th>Promedio</th>
                            </tr>
                        </thead>
                        <tbody id="revenue-table-body"></tbody>
                    </table>
                    <div class="empty-state" id="revenue-empty" style="display:none">
                        <div class="empty-state-icon">💰</div>
                        <div class="empty-state-title">Sin ingresos en el rango</div>
                        <div class="empty-state-desc">Selecciona un rango de fechas y presiona Consultar para ver el reporte.</div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════
             MENU TAB
        ══════════════════════════════════════ -->
        <section id="tab-menu" class="tab-section">
            <div class="page-header">
                <div>
                    <h1>Gestión del Menú</h1>
                    <p class="subtitle">Administra los platillos disponibles</p>
                </div>
                <button class="btn btn-primary btn-sm" type="button" onclick="openMenuModal()">+ Añadir producto</button>
            </div>
            <div class="card">
                <div class="card-body table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Platillo</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Imagen</th>
                                <th>Disponible</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="menu-table-body"></tbody>
                    </table>
                    <div class="empty-state" id="menu-empty" style="display:none">
                        <div class="empty-state-icon">🍔</div>
                        <div class="empty-state-title">Menú vacío</div>
                        <div class="empty-state-desc">Agrega platillos para que los meseros puedan crear órdenes.</div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════
             USERS TAB
        ══════════════════════════════════════ -->
        <section id="tab-users" class="tab-section">
            <div class="page-header">
                <div>
                    <h1>Gestión de Usuarios</h1>
                    <p class="subtitle">Administra el personal del restaurante</p>
                </div>
                <button class="btn btn-primary btn-sm" onclick="openModal('modal-user')">+ Nuevo Usuario</button>
            </div>
            <div class="card">
                <div class="card-body table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body"></tbody>
                    </table>
                    <div class="empty-state" id="users-empty" style="display:none">
                        <div class="empty-state-icon">👥</div>
                        <div class="empty-state-title">Sin usuarios</div>
                        <div class="empty-state-desc">Crea usuarios para que el personal pueda acceder al sistema.</div>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- ── New User Modal ── -->
<div class="modal-overlay" id="modal-user">
    <div class="modal">
        <h2>Nuevo Usuario</h2>
        <form id="form-new-user" onsubmit="createNewUser(event)">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" class="form-control" id="new-username" required>
            </div>
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" class="form-control" id="new-name" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" class="form-control" id="new-password" required>
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select class="form-control" id="new-role">
                    <option value="server">Mesero</option>
                    <option value="cook">Cocinero</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div style="display:flex;gap:0.5rem;justify-content:flex-end;margin-top:1.5rem">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-user')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Menu Item Modal ── -->
<div class="modal-overlay" id="modal-menu-item">
    <div class="modal">
        <h2 id="menu-modal-title">Nuevo producto</h2>
        <form id="form-menu-item" onsubmit="saveMenuItem(event)">
            <input type="hidden" id="menu-item-id">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" class="form-control" id="menu-item-name" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" id="menu-item-description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <select class="form-control" id="menu-item-category" required>
                    <option value="entrada">Entrada</option>
                    <option value="plato_fuerte">Plato fuerte</option>
                    <option value="postre">Postre</option>
                    <option value="bebida">Bebida</option>
                    <option value="acompanamiento">Acompañamiento</option>
                </select>
            </div>
            <div class="form-group">
                <label>Precio</label>
                <input type="number" class="form-control" step="0.01" min="0" id="menu-item-price" required>
            </div>
            <div class="form-group">
                <label>Imagen</label>
                <input type="file" class="form-control" id="menu-item-image" accept="image/*">
                <div class="image-preview" id="menu-image-preview">No hay imagen seleccionada</div>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:0.75rem;">
                <input type="checkbox" id="menu-item-available" checked>
                <label for="menu-item-available" style="margin:0;">Disponible</label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.25rem">
                <button type="button" class="btn btn-ghost" onclick="closeMenuModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Order Detail Modal ── -->
<div class="modal-overlay" id="modal-order-detail">
    <div class="modal">
        <h2>Detalles de Orden</h2>
        <div id="order-detail-content"></div>
        <div style="text-align:right;margin-top:1.5rem">
            <button class="btn btn-ghost" onclick="closeModal('modal-order-detail')">Cerrar</button>
        </div>
    </div>
</div>

<script src="../assets/js/utils.js"></script>
<script src="dashboard.js"></script>

<!-- ── Dashboard UI enhancements ── -->
<script>
// ── Refresh button with spinner ──────────────────────────────
function handleRefresh(btn) {
    btn.classList.add('spinning');
    btn.disabled = true;
    loadDashboard().finally(() => {
        btn.classList.remove('spinning');
        btn.disabled = false;
        updateLastUpdatedLabel();
    });
}

function updateLastUpdatedLabel() {
    const el = document.getElementById('last-updated-label');
    if (el) el.textContent = 'Actualizado hace un momento';
}

// ── Count-up animation ───────────────────────────────────────
function animateCountUp(el, targetText) {
    const isCurrency = targetText.startsWith('$');
    const rawNum = parseFloat(targetText.replace(/[$,]/g, '')) || 0;
    if (rawNum === 0) { el.textContent = targetText; return; }

    const duration = 900;
    const start = performance.now();

    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        // ease out
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = rawNum * eased;

        if (isCurrency) {
            el.textContent = '$' + current.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            el.textContent = Math.floor(current).toLocaleString('es-MX');
        }

        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = targetText;
    }
    requestAnimationFrame(step);
}

// Override stat value setters to use count-up
// Call this after your API responses update the DOM
function setStatValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    animateCountUp(el, value);
}

// ── Mini sparklines (decorative bars from weekly data) ────────
function renderSparkline(containerId, data, color) {
    const container = document.getElementById(containerId);
    if (!container || !data || !data.length) return;
    container.innerHTML = '';
    const max = Math.max(...data, 1);
    data.forEach(v => {
        const bar = document.createElement('div');
        bar.className = 'spark-bar';
        bar.style.height = Math.max(3, (v / max) * 100) + '%';
        bar.style.background = color || 'var(--gradient-primary)';
        container.appendChild(bar);
    });
}

// ── Activity feed helper ──────────────────────────────────────
function renderActivity(items) {
    const feed = document.getElementById('activity-feed');
    if (!feed) return;
    if (!items || !items.length) return; // keep empty state

    const colorMap = { paid: 'green', pending: 'amber', cancelled: 'red', serving: 'blue', default: 'blue' };

    feed.innerHTML = items.map(item => `
        <div class="activity-item">
            <div class="activity-dot ${colorMap[item.type] || colorMap.default}"></div>
            <div class="activity-text">${item.text}</div>
            <div class="activity-time">${item.time}</div>
        </div>
    `).join('');
}

// ── Top items with progress bars ─────────────────────────────
function renderTopItems(items) {
    const container = document.getElementById('top-items-list');
    if (!container) return;

    if (!items || !items.length) {
        // keep empty state
        return;
    }

    const max = Math.max(...items.map(i => i.count), 1);

    container.innerHTML = items.map((item, idx) => `
        <div class="top-item">
            <div class="top-item-rank">${idx + 1}</div>
            <div class="top-item-name">${item.name}</div>
            <div class="top-item-bar-wrap">
                <div class="top-item-bar" style="width:${(item.count / max * 100).toFixed(0)}%"></div>
            </div>
            <div class="top-item-count">${item.count} vendidos</div>
        </div>
    `).join('');
}

// ── Quick action links ────────────────────────────────────────
document.querySelectorAll('.quick-action-btn[data-tab]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        // reuse the nav-item mechanism
        const fakeNav = document.querySelector(`.nav-item[data-tab="${this.dataset.tab}"]`);
        if (fakeNav) switchSection(fakeNav);
    });
});

// Run after dashboard loads
document.addEventListener('DOMContentLoaded', () => {
    updateLastUpdatedLabel();
});
</script>
</body>
</html>