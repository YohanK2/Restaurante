-- ============================================
-- Restaurant Order Management System
-- Database: restaurant_db
-- Import this file in phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

-- ============================================
-- USERS TABLE (roles: admin, server, cook)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'server', 'cook') NOT NULL DEFAULT 'server',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- MENU ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('entrada', 'plato_fuerte', 'postre', 'bebida', 'acompanamiento') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- ORDERS TABLE
-- ============================================
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
) ENGINE=InnoDB;

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
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
) ENGINE=InnoDB;

-- ============================================
-- TRANSACTIONS TABLE (payments)
-- ============================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'transfer') NOT NULL DEFAULT 'cash',
    reference_number VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- STATUS LOGS TABLE (audit trail)
-- ============================================
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
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA: Default Users
-- Passwords: admin123, server123, cook123
-- ============================================
INSERT INTO users (username, password_hash, name, role) VALUES
('admin', '$2y$10$.vYQAFZscRhHTIfzW9PzDuh5ZFUp7gNWyxxqjcWlLAGBG1an.p2lC', 'Administrador', 'admin'),

-- ============================================
-- SEED DATA: Sample Menu Items
-- ============================================
INSERT INTO menu_items (name, description, category, price) VALUES
-- Entradas
('Nachos Supremos', 'Totopos con queso fundido, jalapeños, guacamole y crema', 'entrada', 8.50),
('Sopa de Tortilla', 'Sopa tradicional con tiras de tortilla, aguacate y queso', 'entrada', 6.00),
('Ensalada César', 'Lechuga romana, crutones, parmesano y aderezo césar', 'entrada', 7.50),
-- Platos Fuertes
('Tacos al Pastor', 'Tres tacos de cerdo adobado con piña, cilantro y cebolla', 'plato_fuerte', 12.00),
('Enchiladas Verdes', 'Tres enchiladas de pollo bañadas en salsa verde con crema', 'plato_fuerte', 11.50),
('Filete de Res', 'Filete de 300g a la parrilla con guarnición de vegetales', 'plato_fuerte', 22.00),
('Pollo a la Plancha', 'Pechuga de pollo con arroz y ensalada fresca', 'plato_fuerte', 14.00),
('Burrito Especial', 'Burrito grande relleno de carne, frijoles, arroz y queso', 'plato_fuerte', 13.50),
-- Postres
('Flan Napolitano', 'Flan casero con caramelo', 'postre', 5.50),
('Churros con Chocolate', 'Seis churros con salsa de chocolate caliente', 'postre', 6.50),
-- Bebidas
('Agua de Horchata', 'Vaso de 500ml de horchata casera', 'bebida', 3.00),
('Limonada Natural', 'Limonada fresca con hielo', 'bebida', 3.50),
('Refresco', 'Coca-Cola, Sprite o Fanta', 'bebida', 2.50),
('Cerveza', 'Cerveza nacional o importada', 'bebida', 4.50),
-- Acompañamientos
('Guacamole Extra', 'Porción extra de guacamole fresco', 'acompanamiento', 3.50),
('Arroz con Frijoles', 'Porción de arroz rojo y frijoles refritos', 'acompanamiento', 4.00);
