<?php
/**
 * Database Configuration
 * MySQL connection via PDO for phpMyAdmin/XAMPP/WAMP
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'restaurant_db');
define('DB_USER', 'restaurant');
define('DB_PASS', '123456');
define('DB_CHARSET', 'utf8mb4');

define('TAX_RATE', 0.16); // 16% tax
define('APP_NAME', 'Restaurant Manager');
define('TABLES_COUNT', 20); // Number of restaurant tables

/**
 * Get PDO database connection
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode([
                'error' => true,
                'message' => 'Database connection failed. Make sure MySQL is running and the database exists.',
                'detail' => $e->getMessage()
            ]));
        }
    }
    return $pdo;
}
?>
