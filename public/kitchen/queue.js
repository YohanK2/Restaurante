/**
 * Kitchen Queue JavaScript
 * Auto-polls for new orders every 5 seconds
 */

async function loadQueue() {
    try {
        const orders = await apiRequest('get_kitchen_queue');

        const pending = orders.filter(o => o.status === 'pending');
        const preparing = orders.filter(o => o.status === 'preparing');

        // Update counters
        document.getElementById('count-pending').textContent = pending.length;
        document.getElementById('count-preparing').textContent = preparing.length;
        document.getElementById('badge-pending').textContent = pending.length;
        document.getElementById('badge-preparing').textContent = preparing.length;

        // Render pending
        renderQueue('queue-pending', pending, 'preparing', 'Empezar a Preparar 🔥');

        // Render preparing
        renderQueue('queue-preparing', preparing, 'ready', 'Marcar Lista ✅');

        // Sound alert for new pending orders
        if (pending.length > 0) {
            document.title = `(${pending.length}) 🔴 Nuevas Órdenes - Cocina`;
        } else {
            document.title = 'Cocina - Restaurant Manager';
        }

    } catch (e) {
        console.error('Error loading queue:', e);
    }
}

function renderQueue(containerId, orders, nextStatus, buttonText) {
    const container = document.getElementById(containerId);

    if (orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1">
                <div class="icon">${nextStatus === 'preparing' ? '✨' : '👨‍🍳'}</div>
                <p>${nextStatus === 'preparing' ? 'No hay órdenes nuevas' : 'Nada en preparación'}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = orders.map(order => {
        const elapsed = getElapsedMinutes(order.created_at);
        const timeClass = elapsed > 20 ? 'critical' : elapsed > 10 ? 'warn' : 'ok';
        const isUrgent = elapsed > 15;

        return `
            <div class="order-card ${isUrgent ? 'urgent' : ''}">
                <div class="order-card-header">
                    <div>
                        <span class="order-number">Orden #${order.id}</span>
                        <span class="table-badge">Mesa ${order.table_number}</span>
                    </div>
                    <span class="time-elapsed ${timeClass}">
                        ⏱ ${elapsed} min
                    </span>
                </div>

                <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.5rem">
                    Mesero: ${order.server_name}
                </div>

                <div class="item-list">
                    ${order.items.map(item => `
                        <div class="item-row">
                            <span class="item-qty">${item.quantity}x</span>
                            <span class="item-name">${item.item_name}</span>
                            ${item.special_instructions ? `<span class="item-note">📝 ${item.special_instructions}</span>` : ''}
                        </div>
                    `).join('')}
                </div>

                ${order.notes ? `
                    <div style="background:var(--warning-soft);padding:0.5rem 0.8rem;border-radius:8px;margin:0.5rem 0;font-size:0.82rem;color:var(--warning)">
                        📋 ${order.notes}
                    </div>
                ` : ''}

                <div class="order-footer">
                    <span class="order-time">${formatDateTime(order.created_at)}</span>
                    <button class="btn ${nextStatus === 'ready' ? 'btn-success' : 'btn-warning'} btn-sm"
                            onclick="updateStatus(${order.id}, '${nextStatus}')">
                        ${buttonText}
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function getElapsedMinutes(dateStr) {
    const now = new Date();
    const created = new Date(dateStr);
    return Math.floor((now - created) / 60000);
}

async function updateStatus(orderId, newStatus) {
    try {
        await apiRequest('update_status', 'POST', {
            order_id: orderId,
            new_status: newStatus
        });

        const msg = newStatus === 'preparing' ? 'Orden en preparación' : '¡Orden lista para servir!';
        const type = newStatus === 'preparing' ? 'warning' : 'success';
        showToast(msg, type, `Orden #${orderId}`);

        loadQueue();
    } catch (e) {
        showToast('Error al actualizar estado', 'urgent');
    }
}

// Initialize with auto-polling every 5 seconds
document.addEventListener('DOMContentLoaded', () => {
    startPolling(loadQueue, 5000);
});
