/**
 * Shared JavaScript Utilities
 */

const API_URL = '/backend/core/router.php';

/**
 * Make API request
 */
async function apiRequest(action, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' },
    };

    let url = `${API_URL}?action=${action}`;

    if (method === 'GET' && data) {
        const params = new URLSearchParams(data);
        url += '&' + params.toString();
    }

    if (method === 'POST' && data) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();
        if (result.error) {
            showToast(result.error, 'urgent');
            throw new Error(result.error);
        }
        return result;
    } catch (err) {
        console.error('API Error:', err);
        throw err;
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info', detail = '') {
    let bar = document.querySelector('.notification-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.className = 'notification-bar';
        document.body.appendChild(bar);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-title">${message}</div>
        ${detail ? `<div class="toast-detail">${detail}</div>` : ''}
    `;
    bar.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Format currency
 */
function formatMoney(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

/**
 * Format date/time
 */
function formatDateTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Format relative time
 */
function timeAgo(dateStr) {
    const now = new Date();
    const date = new Date(dateStr);
    const diff = Math.floor((now - date) / 60000);
    if (diff < 1) return 'Ahora';
    if (diff < 60) return `${diff} min`;
    if (diff < 1440) return `${Math.floor(diff / 60)}h`;
    return `${Math.floor(diff / 1440)}d`;
}

/**
 * Get status badge HTML
 */
function statusBadge(status) {
    const labels = {
        pending: 'Pendiente',
        preparing: 'Preparando',
        ready: 'Lista',
        served: 'Servida',
        paid: 'Pagada',
        cancelled: 'Cancelada'
    };
    return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
}

/**
 * Translate category names
 */
function categoryLabel(cat) {
    const labels = {
        entrada: '🥗 Entradas',
        plato_fuerte: '🍖 Platos Fuertes',
        postre: '🍰 Postres',
        bebida: '🥤 Bebidas',
        acompanamiento: '🥑 Acompañamientos'
    };
    return labels[cat] || cat;
}

/**
 * Simple tab switching
 */
function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabGroup = btn.closest('.tab-nav');
            const targetId = btn.dataset.tab;

            tabGroup.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const parent = tabGroup.parentElement;
            parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            parent.querySelector(`#${targetId}`).classList.add('active');
        });
    });
}

/**
 * Open/close modal
 */
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

/**
 * Poll for updates
 */
function startPolling(callback, intervalMs = 5000) {
    callback();
    return setInterval(callback, intervalMs);
}

// Initialize tabs on DOM ready
document.addEventListener('DOMContentLoaded', initTabs);
