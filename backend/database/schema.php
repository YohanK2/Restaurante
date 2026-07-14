<?php
/**
 * Database Schema Initialization
 * Visit: http://localhost/restaurante/backend/database/schema.php
 * This will CREATE the database, tables, and seed data automatically.
 */

// ============================================
// CONFIGURATION
// ============================================
$DB_HOST    = '127.0.0.1';
$DB_PORT    = 3306;
$DB_NAME    = 'restaurant_db';
$DB_USER    = 'root';
$DB_PASS    = '';
$DB_CHARSET = 'utf8mb4';

$messages = [];
$hasError = false;

try {
    // ============================================
    // STEP 1: Connect to MySQL (without database)
    // ============================================
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};charset={$DB_CHARSET}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $messages[] = ['success', '✅ Conexión a MySQL exitosa'];

    // ============================================
    // STEP 2: Create database if not exists
    // ============================================
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = ['success', "✅ Base de datos '{$DB_NAME}' verificada/creada"];

    // ============================================
    // STEP 3: Connect to the database
    // ============================================
    $pdo->exec("USE `{$DB_NAME}`");
    $messages[] = ['success', "✅ Conectado a '{$DB_NAME}'"];

    // ============================================
    // STEP 4: Create tables
    // ============================================
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'server', 'cook') NOT NULL DEFAULT 'server',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    $messages[] = ['success', '✅ Tabla "users" creada'];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            category ENUM('entrada', 'plato_fuerte', 'postre', 'bebida', 'acompanamiento') NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            available TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    $messages[] = ['success', '✅ Tabla "menu_items" creada'];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            server_id INT NOT NULL,
            table_number INT NOT NULL,
            status ENUM('pending', 'preparing', 'ready', 'served', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
            notes TEXT,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB
    ");
    $messages[] = ['success', '✅ Tabla "orders" creada'];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            special_instructions TEXT,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB
    ");
    $messages[] = ['success', '✅ Tabla "order_items" creada'];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'card', 'transfer') NOT NULL DEFAULT 'cash',
            reference_number VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    $messages[] = ['success', '✅ Tabla "transactions" creada'];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS status_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            old_status VARCHAR(20),
            new_status VARCHAR(20) NOT NULL,
            changed_by INT NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB
    ");
    $messages[] = ['success', '✅ Tabla "status_logs" creada'];

    // ============================================
    // STEP 5: Seed users (insert or fix passwords)
    // ============================================
    $defaultUsers = [
        ['admin',   'admin123',  'Administrador',  'admin'],
        ['server1', 'server123', 'Carlos Mesero',   'server'],
        ['server2', 'server123', 'María Mesera',    'server'],
        ['cook1',   'cook123',   'José Cocinero',   'cook'],
        ['cook2',   'cook123',   'Ana Cocinera',    'cook'],
    ];

    $stmtInsert = $pdo->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
    foreach ($defaultUsers as $u) {
        $stmtInsert->execute([$u[0], password_hash($u[1], PASSWORD_DEFAULT), $u[2], $u[3]]);
    }
    $messages[] = ['success', '✅ 5 usuarios creados/actualizados con contraseñas válidas'];

    // ============================================
    // STEP 6: Seed menu items (if empty)
    // ============================================
    $count = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, category, price) VALUES (?, ?, ?, ?)");
        $items = [
            ['Nachos Supremos', 'Totopos con queso fundido, jalapeños, guacamole y crema', 'entrada', 8.50],
            ['Sopa de Tortilla', 'Sopa tradicional con tiras de tortilla, aguacate y queso', 'entrada', 6.00],
            ['Ensalada César', 'Lechuga romana, crutones, parmesano y aderezo césar', 'entrada', 7.50],
            ['Tacos al Pastor', 'Tres tacos de cerdo adobado con piña, cilantro y cebolla', 'plato_fuerte', 12.00],
            ['Enchiladas Verdes', 'Tres enchiladas de pollo bañadas en salsa verde con crema', 'plato_fuerte', 11.50],
            ['Filete de Res', 'Filete de 300g a la parrilla con guarnición de vegetales', 'plato_fuerte', 22.00],
            ['Pollo a la Plancha', 'Pechuga de pollo con arroz y ensalada fresca', 'plato_fuerte', 14.00],
            ['Burrito Especial', 'Burrito grande relleno de carne, frijoles, arroz y queso', 'plato_fuerte', 13.50],
            ['Flan Napolitano', 'Flan casero con caramelo', 'postre', 5.50],
            ['Churros con Chocolate', 'Seis churros con salsa de chocolate caliente', 'postre', 6.50],
            ['Agua de Horchata', 'Vaso de 500ml de horchata casera', 'bebida', 3.00],
            ['Limonada Natural', 'Limonada fresca con hielo', 'bebida', 3.50],
            ['Refresco', 'Coca-Cola, Sprite o Fanta', 'bebida', 2.50],
            ['Cerveza', 'Cerveza nacional o importada', 'bebida', 4.50],
            ['Guacamole Extra', 'Porción extra de guacamole fresco', 'acompanamiento', 3.50],
            ['Arroz con Frijoles', 'Porción de arroz rojo y frijoles refritos', 'acompanamiento', 4.00],
        ];
        foreach ($items as $item) {
            $stmt->execute($item);
        }
        $messages[] = ['success', '✅ 16 platillos del menú insertados'];
    } else {
        $messages[] = ['info', 'ℹ️ El menú ya existía (' . $count . ' platillos encontrados)'];
    }

    $messages[] = ['done', '🎉 ¡Base de datos inicializada correctamente!'];

} catch (PDOException $e) {
    $hasError = true;
    $messages[] = ['error', '❌ Error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Restaurant Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/schema.css">
</head>
<body>
    <div class="container">
        <h1>🍽️ Restaurant Manager</h1>
        <p class="subtitle">Instalación de Base de Datos</p>

        <?php foreach ($messages as $msg): ?>
            <div class="step <?= $msg[0] ?>"><?= $msg[1] ?></div>
        <?php endforeach; ?>

        <?php if (!$hasError): ?>
            <div class="credentials">
                <h3>👤 Cuentas de Prueba</h3>
                <p>Admin: <code>admin</code> / <code>admin123</code></p>
                <p>Mesero: <code>server1</code> / <code>server123</code></p>
                <p>Cocinero: <code>cook1</code> / <code>cook123</code></p>
            </div>
            <div class="actions">
                <a href="/restaurante/index.php" class="btn btn-primary">Ir al Login →</a>
                <a href="/restaurante/backend/database/schema.php" class="btn btn-ghost">Ejecutar de nuevo</a>
            </div>
        <?php else: ?>
            <div class="actions">
                <p style="color:#f43f5e; margin-bottom:1rem;">Verifica que MySQL esté corriendo en XAMPP</p>
                <a href="/restaurante/backend/database/schema.php" class="btn btn-ghost">Reintentar</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
