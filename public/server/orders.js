/**
 * Server Interface JavaScript
 */

let menuItems = [];
let cart = [];
const TAX_RATE = 0.16;

// Section switching
function switchSection(el) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.tab-section').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });
    const target = document.getElementById(el.dataset.tab);
    target.classList.add('active');
    target.style.display = 'block';

    if (el.dataset.tab === 'tab-active') loadActiveOrders();
    if (el.dataset.tab === 'tab-history') loadHistory();
}

// Init display
document.querySelectorAll('.tab-section').forEach(s => {
    s.style.display = s.classList.contains('active') ? 'block' : 'none';
});

// ===== MENU & CART =====
async function loadMenu() {
    try {
        menuItems = await apiRequest('get_menu');
        renderMenu('all');
    } catch (e) {}
}

function renderMenu(category) {
    const grid = document.getElementById('menu-grid');
    const filtered = category === 'all' ? menuItems.filter(m => m.available == 1) :
        menuItems.filter(m => m.category === category && m.available == 1);

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="empty-state"><p>Sin items en esta categoría</p></div>';
        return;
    }

    grid.innerHTML = filtered.map(item => `
        <div class="menu-item-card ${cart.find(c => c.id == item.id) ? 'selected' : ''}"
             onclick="addToCart(${item.id})">
            <div class="item-category">${categoryLabel(item.category)}</div>
            <div class="item-name">${item.name}</div>
            <div class="item-price">${formatMoney(item.price)}</div>
        </div>
    `).join('');
}

function filterMenu(category, btn) {
    btn.parentElement.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderMenu(category);
}

function addToCart(itemId) {
    const item = menuItems.find(m => m.id == itemId);
    if (!item) return;

    const existing = cart.find(c => c.id == itemId);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            id: item.id,
            name: item.name,
            price: parseFloat(item.price),
            quantity: 1,
            special_instructions: ''
        });
    }
    updateCart();
    renderMenu(document.querySelector('.card-header .tab-btn.active')?.textContent === 'Todos' ? 'all' :
        document.querySelector('.card-header .tab-btn.active')?.getAttribute('onclick')?.match(/'(\w+)'/)?.[1] || 'all');
}

function removeFromCart(itemId) {
    cart = cart.filter(c => c.id != itemId);
    updateCart();
}

function changeQty(itemId, delta) {
    const item = cart.find(c => c.id == itemId);
    if (!item) return;
    item.quantity += delta;
    if (item.quantity <= 0) {
        removeFromCart(itemId);
        return;
    }
    updateCart();
}

function updateCart() {
    const container = document.getElementById('cart-items');
    const btnSubmit = document.getElementById('btn-submit-order');

    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="icon">🛒</div><p>Selecciona platillos del menú</p></div>';
        document.getElementById('cart-subtotal').textContent = '$0.00';
        document.getElementById('cart-tax').textContent = '$0.00';
        document.getElementById('cart-total').textContent = '$0.00';
        btnSubmit.disabled = true;
        return;
    }

    btnSubmit.disabled = false;
    let subtotal = 0;

    container.innerHTML = cart.map(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        return `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid var(--border)">
                <div style="flex:1">
                    <div style="font-weight:600;font-size:0.85rem">${item.name}</div>
                    <div style="font-size:0.78rem;color:var(--text-muted)">${formatMoney(item.price)} c/u</div>
                </div>
                <div style="display:flex;align-items:center;gap:0.4rem">
                    <button class="btn btn-ghost btn-sm" onclick="changeQty(${item.id}, -1)" style="padding:0.2rem 0.5rem">−</button>
                    <span style="font-weight:700;min-width:20px;text-align:center">${item.quantity}</span>
                    <button class="btn btn-ghost btn-sm" onclick="changeQty(${item.id}, 1)" style="padding:0.2rem 0.5rem">+</button>
                    <button class="btn btn-ghost btn-sm" onclick="removeFromCart(${item.id})" style="padding:0.2rem 0.5rem;color:var(--danger)">✕</button>
                </div>
                <div style="min-width:60px;text-align:right;font-weight:600">${formatMoney(itemTotal)}</div>
            </div>
        `;
    }).join('');

    const tax = subtotal * TAX_RATE;
    const total = subtotal + tax;
    document.getElementById('cart-subtotal').textContent = formatMoney(subtotal);
    document.getElementById('cart-tax').textContent = formatMoney(tax);
    document.getElementById('cart-total').textContent = formatMoney(total);
}

async function submitOrder() {
    if (cart.length === 0) return;

    const data = {
        table_number: parseInt(document.getElementById('order-table').value),
        notes: document.getElementById('order-notes').value,
        items: cart.map(c => ({
            menu_item_id: c.id,
            quantity: c.quantity,
            special_instructions: c.special_instructions
        }))
    };

    try {
        const result = await apiRequest('create_order', 'POST', data);
        showToast(`Orden #${result.order_id} enviada a cocina`, 'success', `Mesa ${data.table_number}`);
        cart = [];
        updateCart();
        document.getElementById('order-notes').value = '';
    } catch (e) {
        showToast('Error al crear la orden', 'urgent');
    }
}

// ===== ACTIVE ORDERS =====
async function loadActiveOrders() {
    try {
        // Load notifications
        const notifs = await apiRequest('get_notifications');
        const bar = document.getElementById('notifications-bar');
        if (notifs && notifs.length > 0) {
            bar.innerHTML = notifs.map(n => `
                <div class="toast ${n.urgency}" style="position:relative;animation:none;margin-bottom:0.5rem">
                    <div class="toast-title">${n.message}</div>
                    <div class="toast-detail">${n.detail || ''}</div>
                </div>
            `).join('');
        } else {
            bar.innerHTML = '';
        }

        // Load orders
        const orders = await apiRequest('get_orders', 'GET', {});
        const active = orders.filter(o => !['paid', 'cancelled'].includes(o.status));
        const grid = document.getElementById('active-orders-grid');

        if (active.length === 0) {
            grid.innerHTML = '<div class="empty-state"><div class="icon">📋</div><p>No tienes órdenes activas</p></div>';
            return;
        }

        grid.innerHTML = active.map(o => `
            <div class="order-card">
                <div class="order-card-header">
                    <span class="order-number">Orden #${o.id}</span>
                    <span class="table-badge">Mesa ${o.table_number}</span>
                </div>
                <div class="flex-between mb-2">
                    ${statusBadge(o.status)}
                    <span class="order-time">${timeAgo(o.created_at)}</span>
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--success);margin:0.5rem 0">
                    ${formatMoney(o.total)}
                </div>
                <div class="order-footer">
                    ${o.status === 'ready' ? `<button class="btn btn-success btn-sm" onclick="markServed(${o.id})">Marcar Servida ✅</button>` : ''}
                    ${o.status === 'served' ? `<button class="btn btn-primary btn-sm" onclick="openPayment(${o.id}, '${o.total}')">Cobrar 💰</button>` : ''}
                    ${o.status === 'pending' ? `<span style="font-size:0.8rem;color:var(--warning)">⏳ En espera de cocina</span>` : ''}
                    ${o.status === 'preparing' ? `<span style="font-size:0.8rem;color:var(--info)">👨‍🍳 Preparando...</span>` : ''}
                </div>
            </div>
        `).join('');
    } catch (e) {}
}

async function markServed(orderId) {
    await apiRequest('update_status', 'POST', { order_id: orderId, new_status: 'served' });
    showToast('Orden marcada como servida', 'success');
    loadActiveOrders();
}

function openPayment(orderId, total) {
    document.getElementById('pay-order-id').value = orderId;
    document.getElementById('pay-order-total').textContent = formatMoney(total);
    openModal('modal-payment');
}

async function processPayment() {
    const data = {
        order_id: parseInt(document.getElementById('pay-order-id').value),
        payment_method: document.getElementById('pay-method').value,
        reference: document.getElementById('pay-reference').value
    };
    try {
        await apiRequest('process_payment', 'POST', data);
        showToast('Pago procesado exitosamente', 'success');
        closeModal('modal-payment');
        loadActiveOrders();
    } catch (e) {
        showToast('Error al procesar pago', 'urgent');
    }
}

// ===== HISTORY =====
async function loadHistory() {
    try {
        const orders = await apiRequest('get_orders');
        const completed = orders.filter(o => ['paid', 'cancelled'].includes(o.status));
        const tbody = document.getElementById('history-table');

        if (completed.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Sin historial</td></tr>';
            return;
        }

        tbody.innerHTML = completed.map(o => `
            <tr>
                <td><strong>#${o.id}</strong></td>
                <td>Mesa ${o.table_number}</td>
                <td>${statusBadge(o.status)}</td>
                <td><strong>${formatMoney(o.total)}</strong></td>
                <td>${formatDateTime(o.created_at)}</td>
                <td><button class="btn btn-ghost btn-sm" onclick="viewOrder(${o.id})">Ver</button></td>
            </tr>
        `).join('');
    } catch (e) {}
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    loadMenu();
    // Poll for ready orders every 10s
    startPolling(loadActiveOrders, 10000);
});
