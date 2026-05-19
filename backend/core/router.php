<?php
/**
 * API Router
 * Handles AJAX requests from frontend views
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/revenue.php';
require_once __DIR__ . '/notifications.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {

        // ===== ORDER ACTIONS =====
        case 'create_order':
            requireRole(['admin', 'server']);
            $data = json_decode(file_get_contents('php://input'), true);
            $result = createOrder($user['id'], $data['table_number'], $data['items'], $data['notes'] ?? '');
            echo json_encode(['success' => true, 'order_id' => $result]);
            break;

        case 'get_orders':
            $filters = [
                'status' => $_GET['status'] ?? null,
                'server_id' => $_GET['server_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
            ];
            if ($user['role'] === 'server') {
                $filters['server_id'] = $user['id'];
            }
            $orders = getOrders($filters);
            echo json_encode($orders);
            break;

        case 'get_order':
            $order = getOrderById($_GET['id']);
            echo json_encode($order);
            break;

        case 'update_status':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = updateOrderStatus($data['order_id'], $data['new_status'], $user['id']);
            echo json_encode(['success' => $result]);
            break;

        case 'get_kitchen_queue':
            requireRole(['admin', 'cook']);
            $queue = getKitchenQueue();
            echo json_encode($queue);
            break;

        // ===== MENU ACTIONS =====
        case 'get_menu':
            $menu = getMenuItems();
            echo json_encode($menu);
            break;

        case 'get_menu_item':
            requireRole('admin');
            $item = getMenuItemById($_GET['id']);
            if (!$item) {
                throw new Exception('Producto no encontrado');
            }
            echo json_encode($item);
            break;

        case 'create_menu_item':
            requireRole('admin');
            $data = json_decode(file_get_contents('php://input'), true);
            $id = createMenuItem(
                $data['name'],
                $data['description'] ?? '',
                $data['category'],
                $data['price'],
                $data['image_data'] ?? null,
                $data['available'] ?? 1
            );
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'update_menu_item':
            requireRole('admin');
            $data = json_decode(file_get_contents('php://input'), true);
            updateMenuItem(
                $data['id'],
                $data['name'],
                $data['description'] ?? '',
                $data['category'],
                $data['price'],
                $data['image_data'] ?? null,
                $data['available'] ?? 1
            );
            echo json_encode(['success' => true]);
            break;

        case 'delete_menu_item':
            requireRole('admin');
            $data = json_decode(file_get_contents('php://input'), true);
            deleteMenuItem($data['id']);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_menu_item':
            requireRole('admin');
            $data = json_decode(file_get_contents('php://input'), true);
            toggleMenuItem($data['id']);
            echo json_encode(['success' => true]);
            break;

        // ===== REVENUE ACTIONS =====
        case 'get_dashboard_stats':
            requireRole('admin');
            echo json_encode(getDashboardStats());
            break;

        case 'get_revenue':
            requireRole('admin');
            $from = $_GET['from'] ?? date('Y-m-d');
            $to = $_GET['to'] ?? date('Y-m-d');
            echo json_encode(getRevenueByRange($from, $to));
            break;

        case 'get_top_items':
            requireRole('admin');
            $limit = $_GET['limit'] ?? 10;
            echo json_encode(getTopItems($limit));
            break;

        case 'get_revenue_chart':
            requireRole('admin');
            $days = $_GET['days'] ?? 7;
            echo json_encode(getRevenueChart($days));
            break;

        // ===== PAYMENT ACTIONS =====
        case 'process_payment':
            requireRole(['admin', 'server']);
            $data = json_decode(file_get_contents('php://input'), true);
            $result = processPayment($data['order_id'], $data['payment_method'], $data['reference'] ?? null);
            echo json_encode(['success' => $result]);
            break;

        // ===== USER ACTIONS (admin) =====
        case 'get_users':
            requireRole('admin');
            echo json_encode(getAllUsers());
            break;

        case 'create_user':
            requireRole('admin');
            $data = json_decode(file_get_contents('php://input'), true);
            $result = createUser($data['username'], $data['password'], $data['name'], $data['role']);
            echo json_encode(['success' => $result]);
            break;

        case 'toggle_user':
            requireRole('admin');
            $data = json_decode(file_get_contents('php://input'), true);
            toggleUserStatus($data['id']);
            echo json_encode(['success' => true]);
            break;

        // ===== NOTIFICATIONS =====
        case 'get_notifications':
            echo json_encode(getNotifications($user['role'], $user['id']));
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
