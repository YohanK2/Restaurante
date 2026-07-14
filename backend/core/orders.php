<?php
/**
 * Order Management Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Create a new order with items
 */
function createOrder($serverId, $tableNumber, $items, $notes = '') {
    $db = getDB();
    $db->beginTransaction();

    try {
        $subtotal = 0;

        // Calculate subtotal from items
        foreach ($items as &$item) {
            $stmt = $db->prepare("SELECT price FROM menu_items WHERE id = ? AND available = 1");
            $stmt->execute([$item['menu_item_id']]);
            $menuItem = $stmt->fetch();
            if (!$menuItem) {
                throw new Exception("Item de menú #{$item['menu_item_id']} no disponible");
            }
            $item['unit_price'] = $menuItem['price'];
            $item['subtotal'] = $menuItem['price'] * $item['quantity'];
            $subtotal += $item['subtotal'];
        }

        $tax = round($subtotal * TAX_RATE, 2);
        $total = $subtotal + $tax;

        // Insert order
        $stmt = $db->prepare("
            INSERT INTO orders (server_id, table_number, status, notes, subtotal, tax, total)
            VALUES (?, ?, 'pending', ?, ?, ?, ?)
        ");
        $stmt->execute([$serverId, $tableNumber, $notes, $subtotal, $tax, $total]);
        $orderId = $db->lastInsertId();

        // Insert order items
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal, special_instructions)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $stmt->execute([
                $orderId,
                $item['menu_item_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal'],
                $item['special_instructions'] ?? ''
            ]);
        }

        // Log status
        $logStmt = $db->prepare("INSERT INTO status_logs (order_id, old_status, new_status, changed_by) VALUES (?, NULL, 'pending', ?)");
        $logStmt->execute([$orderId, $serverId]);

        $db->commit();
        return $orderId;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Update order status with audit logging
 */
function updateOrderStatus($orderId, $newStatus, $userId) {
    $db = getDB();

    $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) throw new Exception("Orden no encontrada");

    $validTransitions = [
        'pending'   => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready'     => ['served'],
        'served'    => ['paid'],
        'paid'      => [],
        'cancelled' => [],
    ];

    if (!in_array($newStatus, $validTransitions[$order['status']] ?? [])) {
        throw new Exception("Transición de estado no válida: {$order['status']} → {$newStatus}");
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);

        $logStmt = $db->prepare("INSERT INTO status_logs (order_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$orderId, $order['status'], $newStatus, $userId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Get orders with optional filters
 */
function getOrders($filters = []) {
    $db = getDB();
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = "o.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['server_id'])) {
        $where[] = "o.server_id = ?";
        $params[] = $filters['server_id'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(o.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(o.created_at) <= ?";
        $params[] = $filters['date_to'];
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT o.*, u.name as server_name
        FROM orders o
        LEFT JOIN users u ON o.server_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get single order with its items
 */
function getOrderById($orderId) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT o.*, u.name as server_name
        FROM orders o
        LEFT JOIN users u ON o.server_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) return null;

    $stmt = $db->prepare("
        SELECT oi.*, mi.name as item_name, mi.category
        FROM order_items oi
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll();

    return $order;
}

/**
 * Get kitchen queue (pending and preparing orders)
 */
function getKitchenQueue() {
    $db = getDB();
    $stmt = $db->query("
        SELECT o.*, u.name as server_name
        FROM orders o
        LEFT JOIN users u ON o.server_id = u.id
        WHERE o.status IN ('pending', 'preparing')
        ORDER BY
            CASE o.status WHEN 'pending' THEN 0 WHEN 'preparing' THEN 1 END,
            o.created_at ASC
    ");
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $itemStmt = $db->prepare("
            SELECT oi.*, mi.name as item_name, mi.category
            FROM order_items oi
            LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }

    return $orders;
}

/**
 * Get menu items grouped by category
 */
function getMenuItems($onlyAvailable = false) {
    $db = getDB();
    $where = $onlyAvailable ? "WHERE available = 1" : "";
    return $db->query("SELECT * FROM menu_items $where ORDER BY category, name")->fetchAll();
}

/**
 * Toggle menu item availability
 */
function toggleMenuItem($id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE menu_items SET available = NOT available WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Save a base64 encoded menu image to disk and return the public URL
 */
function saveMenuImage($base64Data) {
    if (empty($base64Data)) {
        return null;
    }

    if (!preg_match('#^data:image/(png|jpe?g|webp|gif);base64,#i', $base64Data, $matches)) {
        throw new Exception('Formato de imagen no válido');
    }

    $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
    $imageData = substr($base64Data, strpos($base64Data, ',') + 1);
    $decoded = base64_decode($imageData);

    if ($decoded === false) {
        throw new Exception('No se pudo decodificar la imagen');
    }

    $uploadDir = __DIR__ . '/../../public/assets/uploads/menu';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('menu_', true) . '.' . $extension;
    $filePath = $uploadDir . '/' . $filename;
    file_put_contents($filePath, $decoded);

    return getBaseUrl() . '/public/assets/uploads/menu/' . $filename;
}

/**
 * Create a new menu item
 */
function createMenuItem($name, $description, $category, $price, $imageData = null, $available = 1) {
    $db = getDB();
    $imageUrl = saveMenuImage($imageData);

    $stmt = $db->prepare(
        "INSERT INTO menu_items (name, description, category, price, image_url, available) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $description, $category, $price, $imageUrl, $available]);
    return $db->lastInsertId();
}

/**
 * Update an existing menu item
 */
function updateMenuItem($id, $name, $description, $category, $price, $imageData = null, $available = 1) {
    $db = getDB();
    $item = getMenuItemById($id);
    if (!$item) {
        throw new Exception('Producto no encontrado');
    }

    $imageUrl = $item['image_url'];
    if (!empty($imageData)) {
        $imageUrl = saveMenuImage($imageData);
    }

    $stmt = $db->prepare(
        "UPDATE menu_items SET name = ?, description = ?, category = ?, price = ?, image_url = ?, available = ? WHERE id = ?"
    );
    return $stmt->execute([$name, $description, $category, $price, $imageUrl, $available, $id]);
}

/**
 * Delete a menu item
 */
function deleteMenuItem($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get a single menu item by id
 */
function getMenuItemById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Process payment for an order
 */
function processPayment($orderId, $paymentMethod, $reference = null) {
    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare("SELECT total, status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) throw new Exception("Orden no encontrada");
        if ($order['status'] !== 'served') throw new Exception("La orden debe estar servida para procesar el pago");

        $stmt = $db->prepare("INSERT INTO transactions (order_id, amount, payment_method, reference_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$orderId, $order['total'], $paymentMethod, $reference]);

        $stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);

        $logStmt = $db->prepare("INSERT INTO status_logs (order_id, old_status, new_status, changed_by, notes) VALUES (?, 'served', 'paid', ?, ?)");
        $logStmt->execute([$orderId, $_SESSION['user_id'], "Pago: $paymentMethod"]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
?>
