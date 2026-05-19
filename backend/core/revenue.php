<?php
/**
 * Revenue & Reporting Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get dashboard stats for admin
 */
function getDashboardStats() {
    $db = getDB();

    // Today's revenue
    $todayRevenue = $db->query("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM transactions
        WHERE DATE(created_at) = CURDATE()
    ")->fetch()['total'];

    // Today's orders count
    $todayOrders = $db->query("
        SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()
    ")->fetch()['total'];

    // Active orders (not paid/cancelled)
    $activeOrders = $db->query("
        SELECT COUNT(*) as total FROM orders WHERE status NOT IN ('paid', 'cancelled')
    ")->fetch()['total'];

    // Monthly revenue
    $monthRevenue = $db->query("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM transactions
        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ")->fetch()['total'];

    // Average order value today
    $avgOrder = $db->query("
        SELECT COALESCE(AVG(total), 0) as average
        FROM orders
        WHERE DATE(created_at) = CURDATE() AND status = 'paid'
    ")->fetch()['average'];

    // Total customers (unique tables today)
    $tablesToday = $db->query("
        SELECT COUNT(DISTINCT table_number) as total FROM orders WHERE DATE(created_at) = CURDATE()
    ")->fetch()['total'];

    return [
        'today_revenue'  => round($todayRevenue, 2),
        'today_orders'   => (int) $todayOrders,
        'active_orders'  => (int) $activeOrders,
        'month_revenue'  => round($monthRevenue, 2),
        'avg_order'      => round($avgOrder, 2),
        'tables_served'  => (int) $tablesToday,
    ];
}

/**
 * Get revenue by date range
 */
function getRevenueByRange($from, $to) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            DATE(created_at) as date,
            COUNT(*) as transactions_count,
            SUM(amount) as total_revenue,
            AVG(amount) as avg_transaction
        FROM transactions
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$from, $to]);
    return $stmt->fetchAll();
}

/**
 * Get top selling menu items
 */
function getTopItems($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            mi.name,
            mi.category,
            mi.price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.subtotal) as total_revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'paid'
        GROUP BY mi.id, mi.name, mi.category, mi.price
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->execute([(int) $limit]);
    return $stmt->fetchAll();
}

/**
 * Get revenue data for chart (last N days)
 */
function getRevenueChart($days = 7) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            DATE(created_at) as date,
            SUM(amount) as revenue
        FROM transactions
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([(int) $days]);
    return $stmt->fetchAll();
}
?>
