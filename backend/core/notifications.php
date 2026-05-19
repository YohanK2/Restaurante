<?php
/**
 * Notifications & Status Update Automation
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get notifications for a specific role
 */
function getNotifications($role, $userId = null) {
    $db = getDB();
    $notifications = [];

    switch ($role) {
        case 'cook':
            // New pending orders for kitchen
            $stmt = $db->query("
                SELECT o.id, o.table_number, o.created_at, u.name as server_name,
                       TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as minutes_ago
                FROM orders o
                LEFT JOIN users u ON o.server_id = u.id
                WHERE o.status = 'pending'
                ORDER BY o.created_at ASC
            ");
            $pending = $stmt->fetchAll();
            foreach ($pending as $order) {
                $urgency = $order['minutes_ago'] > 15 ? 'urgent' : ($order['minutes_ago'] > 8 ? 'warning' : 'info');
                $notifications[] = [
                    'type'     => 'new_order',
                    'urgency'  => $urgency,
                    'message'  => "Nueva orden #{$order['id']} - Mesa {$order['table_number']}",
                    'detail'   => "Mesero: {$order['server_name']} · Hace {$order['minutes_ago']} min",
                    'order_id' => $order['id'],
                    'time'     => $order['created_at']
                ];
            }
            break;

        case 'server':
            // Orders ready to serve
            $stmt = $db->prepare("
                SELECT o.id, o.table_number, o.created_at
                FROM orders o
                WHERE o.status = 'ready' AND o.server_id = ?
                ORDER BY o.updated_at DESC
            ");
            $stmt->execute([$userId]);
            $ready = $stmt->fetchAll();
            foreach ($ready as $order) {
                $notifications[] = [
                    'type'     => 'order_ready',
                    'urgency'  => 'success',
                    'message'  => "¡Orden #{$order['id']} lista! - Mesa {$order['table_number']}",
                    'detail'   => 'Pasar a recoger a cocina',
                    'order_id' => $order['id'],
                    'time'     => $order['created_at']
                ];
            }
            break;

        case 'admin':
            // Summary notifications
            $activeCount = $db->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('paid', 'cancelled')")->fetchColumn();
            $todayRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn();

            $notifications[] = [
                'type'    => 'summary',
                'urgency' => 'info',
                'message' => "$activeCount órdenes activas",
                'detail'  => "Ingresos hoy: \${$todayRevenue}",
            ];

            // Delayed orders (> 20 min pending)
            $delayed = $db->query("
                SELECT COUNT(*) FROM orders
                WHERE status = 'pending'
                AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 20
            ")->fetchColumn();
            if ($delayed > 0) {
                $notifications[] = [
                    'type'    => 'delayed',
                    'urgency' => 'urgent',
                    'message' => "$delayed órdenes con retraso",
                    'detail'  => 'Órdenes pendientes por más de 20 minutos',
                ];
            }
            break;
    }

    return $notifications;
}
?>
