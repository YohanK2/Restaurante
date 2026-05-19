/**
 * Admin Dashboard JavaScript
 */

let menuItemsCache = [];

function updateHeaderTitle(tab) {
    const labels = {
        'tab-dashboard': 'Dashboard',
        'tab-orders': 'Órdenes',
        'tab-revenue': 'Ingresos',
        'tab-menu': 'Menú',
        'tab-users': 'Usuarios',
    };
    const titleEl = document.getElementById('header-section-title');
    if (titleEl) titleEl.textContent = labels[tab] || 'Dashboard';
}

// Section switching
function switchSection(el) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
    document.getElementById(el.dataset.tab).classList.add('active');

    // Load data for section
    const tab = el.dataset.tab;
    updateHeaderTitle(tab);
    if (tab === 'tab-dashboard') loadDashboard();
    if (tab === 'tab-orders') loadOrders();
    if (tab === 'tab-revenue') loadRevenueReport();
    if (tab === 'tab-menu') loadMenu();
    if (tab === 'tab-users') loadUsers();
}

// Add active class style for sections
document.querySelectorAll('.tab-section').forEach(s => s.style.display = 'none');
document.querySelector('.tab-section.active').style.display = 'block';

function switchSectionDisplay() {
    document.querySelectorAll('.tab-section').forEach(s => {
        s.style.display = s.classList.contains('active') ? 'block' : 'none';
    });
}

// Override switchSection to handle display
const origSwitch = switchSection;
switchSection = function(el) {
    origSwitch(el);
    switchSectionDisplay();
};

// ===== DASHBOARD =====
async function loadDashboard() {
    try {
        const stats = await apiRequest('get_dashboard_stats');
        document.getElementById('stat-today-revenue').textContent = formatMoney(stats.today_revenue);
        document.getElementById('stat-today-orders').textContent = stats.today_orders;
        document.getElementById('stat-active-orders').textContent = stats.active_orders;
        document.getElementById('stat-month-revenue').textContent = formatMoney(stats.month_revenue);
        document.getElementById('stat-avg-order').textContent = formatMoney(stats.avg_order);
        document.getElementById('stat-tables').textContent = stats.tables_served;
    } catch (e) {
        console.log('Stats not loaded yet');
    }

    // Load chart
    try {
        const chart = await apiRequest('get_revenue_chart', 'GET', { days: 7 });
        renderChart(chart);
    } catch (e) {}

    // Load top items
    try {
        const items = await apiRequest('get_top_items', 'GET', { limit: 5 });
        renderTopItems(items);
    } catch (e) {}
}

function renderChart(data) {
    const chartEl = document.getElementById('revenue-chart');
    const labelsEl = document.getElementById('revenue-labels');

    if (!data || data.length === 0) {
        chartEl.innerHTML = '<div class="empty-state"><p>Sin datos de ingresos</p></div>';
        labelsEl.innerHTML = '';
        return;
    }

    const max = Math.max(...data.map(d => parseFloat(d.revenue)), 1);
    chartEl.innerHTML = data.map(d => {
        const h = (parseFloat(d.revenue) / max) * 180;
        return `<div class="chart-bar" style="height:${h}px" data-value="${formatMoney(d.revenue)}"></div>`;
    }).join('');

    labelsEl.innerHTML = data.map(d => {
        const date = new Date(d.date);
        return `<span>${date.toLocaleDateString('es-MX', { weekday: 'short' })}</span>`;
    }).join('');
}

function renderTopItems(items) {
    const el = document.getElementById('top-items-list');
    if (!items || items.length === 0) {
        el.innerHTML = '<div class="empty-state"><p>Sin datos de ventas</p></div>';
        return;
    }
    el.innerHTML = items.map((item, i) => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;gap:0.75rem">
                <span style="width:24px;height:24px;border-radius:50%;background:var(--gradient-primary);color:white;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700">${i + 1}</span>
                <div>
                    <div style="font-weight:600;font-size:0.88rem">${item.name}</div>
                    <div style="font-size:0.75rem;color:var(--text-muted)">${item.total_sold} vendidos</div>
                </div>
            </div>
            <span style="font-weight:700;color:var(--success)">${formatMoney(item.total_revenue)}</span>
        </div>
    `).join('');
}

// ===== ORDERS =====
async function loadOrders() {
    try {
        const status = document.getElementById('filter-status').value;
        const params = {};
        if (status) params.status = status;
        const orders = await apiRequest('get_orders', 'GET', params);
        renderOrdersTable(orders);
    } catch (e) {}
}

function renderOrdersTable(orders) {
    const tbody = document.getElementById('orders-table-body');
    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No hay órdenes</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(o => `
        <tr>
            <td><strong>#${o.id}</strong></td>
            <td>Mesa ${o.table_number}</td>
            <td>${o.server_name || '-'}</td>
            <td>${statusBadge(o.status)}</td>
            <td><strong>${formatMoney(o.total)}</strong></td>
            <td>${formatDateTime(o.created_at)}</td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="viewOrder(${o.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

async function viewOrder(id) {
    try {
        const order = await apiRequest('get_order', 'GET', { id });
        const content = document.getElementById('order-detail-content');
        content.innerHTML = `
            <div style="margin-bottom:1rem">
                <div class="flex-between">
                    <strong>Orden #${order.id}</strong>
                    ${statusBadge(order.status)}
                </div>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0.3rem">
                    Mesa ${order.table_number} · ${order.server_name} · ${formatDateTime(order.created_at)}
                </p>
            </div>
            <table>
                <thead><tr><th>Item</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr></thead>
                <tbody>
                    ${order.items.map(i => `
                        <tr>
                            <td>${i.item_name}</td>
                            <td>${i.quantity}</td>
                            <td>${formatMoney(i.unit_price)}</td>
                            <td>${formatMoney(i.subtotal)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div style="text-align:right;margin-top:1rem;font-size:0.9rem">
                <div>Subtotal: ${formatMoney(order.subtotal)}</div>
                <div>Impuesto: ${formatMoney(order.tax)}</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--success);margin-top:0.3rem">Total: ${formatMoney(order.total)}</div>
            </div>
        `;
        openModal('modal-order-detail');
    } catch (e) {}
}

// ===== REVENUE REPORT =====
async function loadRevenueReport() {
    const from = document.getElementById('revenue-from').value || new Date(Date.now() - 30*86400000).toISOString().split('T')[0];
    const to = document.getElementById('revenue-to').value || new Date().toISOString().split('T')[0];

    if (!document.getElementById('revenue-from').value) document.getElementById('revenue-from').value = from;
    if (!document.getElementById('revenue-to').value) document.getElementById('revenue-to').value = to;

    try {
        const data = await apiRequest('get_revenue', 'GET', { from, to });
        const tbody = document.getElementById('revenue-table-body');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty-state">Sin datos en este rango</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(d => `
            <tr>
                <td>${d.date}</td>
                <td>${d.transactions_count}</td>
                <td><strong>${formatMoney(d.total_revenue)}</strong></td>
                <td>${formatMoney(d.avg_transaction)}</td>
            </tr>
        `).join('');
    } catch (e) {}
}

// ===== MENU MANAGEMENT =====
function resetMenuForm() {
    document.getElementById('form-menu-item').reset();
    document.getElementById('menu-item-id').value = '';
    document.getElementById('menu-image-preview').innerHTML = 'No hay imagen seleccionada';
    document.getElementById('menu-modal-title').textContent = 'Nuevo producto';
    document.getElementById('menu-item-available').checked = true;
}

function openMenuModal(item = null) {
    resetMenuForm();
    if (item) {
        document.getElementById('menu-item-id').value = item.id;
        document.getElementById('menu-item-name').value = item.name;
        document.getElementById('menu-item-description').value = item.description || '';
        document.getElementById('menu-item-category').value = item.category;
        document.getElementById('menu-item-price').value = item.price;
        document.getElementById('menu-item-available').checked = item.available == 1;
        document.getElementById('menu-modal-title').textContent = 'Editar producto';
        document.getElementById('menu-image-preview').innerHTML = item.image_url
            ? `<img src="${item.image_url}" alt="${item.name}">`
            : 'No hay imagen seleccionada';
    }
    openModal('modal-menu-item');
}

async function loadMenu() {
    try {
        const items = await apiRequest('get_menu');
        menuItemsCache = items;
        const tbody = document.getElementById('menu-table-body');
        tbody.innerHTML = items.map(item => `
            <tr>
                <td>
                    <strong>${item.name}</strong>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.35rem">${item.description || 'Sin descripción'}</div>
                </td>
                <td>${categoryLabel(item.category)}</td>
                <td><strong>${formatMoney(item.price)}</strong></td>
                <td class="menu-image-cell">${item.image_url ? `<img src="${item.image_url}" alt="${item.name}">` : '—'}</td>
                <td>${item.available == 1
                    ? '<span class="badge badge-ready">Sí</span>'
                    : '<span class="badge badge-cancelled">No</span>'}</td>
                <td class="actions-cell">
                    <button class="btn btn-ghost btn-sm" type="button" onclick="editMenuItem(${item.id})">Editar</button>
                    <button class="btn btn-ghost btn-sm" type="button" onclick="toggleMenu(${item.id})">${item.available == 1 ? 'Desactivar' : 'Activar'}</button>
                    <button class="btn btn-danger btn-sm" type="button" onclick="deleteMenuItem(${item.id})">Eliminar</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('Error cargando menú', e);
    }
}

async function readImageAsDataURL(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

async function saveMenuItem(e) {
    e.preventDefault();
    const id = document.getElementById('menu-item-id').value;
    const name = document.getElementById('menu-item-name').value.trim();
    const description = document.getElementById('menu-item-description').value.trim();
    const category = document.getElementById('menu-item-category').value;
    const price = parseFloat(document.getElementById('menu-item-price').value) || 0;
    const available = document.getElementById('menu-item-available').checked ? 1 : 0;
    const imageInput = document.getElementById('menu-item-image');
    const imageFile = imageInput.files[0];
    const imageData = imageFile ? await readImageAsDataURL(imageFile) : null;

    const payload = {
        name,
        description,
        category,
        price,
        available,
    };
    if (imageData) payload.image_data = imageData;

    try {
        if (id) {
            payload.id = parseInt(id, 10);
            await apiRequest('update_menu_item', 'POST', payload);
            showToast('Producto actualizado', 'success');
        } else {
            await apiRequest('create_menu_item', 'POST', payload);
            showToast('Producto agregado', 'success');
        }
        closeMenuModal();
        loadMenu();
    } catch (error) {
        console.error('Error guardando producto', error);
    }
}

function editMenuItem(id) {
    const item = menuItemsCache.find(i => i.id === id);
    if (!item) return;
    openMenuModal(item);
}

async function deleteMenuItem(id) {
    if (!confirm('¿Eliminar este platillo? Esta acción no se puede deshacer.')) return;
    try {
        await apiRequest('delete_menu_item', 'POST', { id });
        showToast('Producto eliminado', 'success');
        loadMenu();
    } catch (error) {
        console.error('Error eliminando producto', error);
    }
}

function closeMenuModal() {
    document.getElementById('form-menu-item').reset();
    closeModal('modal-menu-item');
}

async function toggleMenu(id) {
    await apiRequest('toggle_menu_item', 'POST', { id });
    showToast('Item actualizado', 'success');
    loadMenu();
}

// ===== USER MANAGEMENT =====
async function loadUsers() {
    try {
        const users = await apiRequest('get_users');
        const tbody = document.getElementById('users-table-body');
        const roleLabels = { admin: 'Administrador', server: 'Mesero', cook: 'Cocinero' };
        tbody.innerHTML = users.map(u => `
            <tr>
                <td>${u.id}</td>
                <td><strong>${u.username}</strong></td>
                <td>${u.name}</td>
                <td><span class="badge badge-${u.role === 'admin' ? 'paid' : u.role === 'server' ? 'preparing' : 'ready'}">${roleLabels[u.role]}</span></td>
                <td>${u.active == 1
                    ? '<span class="badge badge-ready">Activo</span>'
                    : '<span class="badge badge-cancelled">Inactivo</span>'}</td>
                <td>
                    <button class="btn btn-ghost btn-sm" onclick="toggleUserActive(${u.id})">
                        ${u.active == 1 ? 'Desactivar' : 'Activar'}
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (e) {}
}

async function toggleUserActive(id) {
    await apiRequest('toggle_user', 'POST', { id });
    showToast('Usuario actualizado', 'success');
    loadUsers();
}

async function createNewUser(e) {
    e.preventDefault();
    const data = {
        username: document.getElementById('new-username').value,
        name: document.getElementById('new-name').value,
        password: document.getElementById('new-password').value,
        role: document.getElementById('new-role').value,
    };
    await apiRequest('create_user', 'POST', data);
    showToast('Usuario creado exitosamente', 'success');
    closeModal('modal-user');
    document.getElementById('form-new-user').reset();
    loadUsers();
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    updateHeaderTitle('tab-dashboard');
    initProfileMenu();
});

function initProfileMenu() {
    const btn = document.getElementById('profileToggle');
    const menu = document.getElementById('profileMenu');
    if (!btn || !menu) return;

    btn.addEventListener('click', (event) => {
        event.stopPropagation();
        menu.classList.toggle('active');
    });

    document.addEventListener('click', () => {
        menu.classList.remove('active');
    });
}

