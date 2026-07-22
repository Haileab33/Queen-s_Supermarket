<?php
// includes/db.php
// Database connection configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'queens_supermarket');

// Hardcoded admin credentials (as required)
define('ADMIN_USERNAME', '1adimn');
define('ADMIN_PASSWORD', '1adimn');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function sanitize($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['is_hardcoded_admin']);
}

function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') 
        || isset($_SESSION['is_hardcoded_admin']);
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function logActivity($conn, $user_id, $action, $item_id = null, $details = '') {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, item_id, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $action, $item_id, $details);
    $stmt->execute();
    $stmt->close();
}

// Ensure critical tables exist
$connForSetup = getDBConnection();
$connForSetup->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL DEFAULT '',
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    first_name VARCHAR(100) NOT NULL DEFAULT '',
    middle_name VARCHAR(100) NOT NULL DEFAULT '',
    last_name VARCHAR(100) NOT NULL DEFAULT '',
    age INT DEFAULT NULL,
    occupation VARCHAR(255) DEFAULT NULL,
    salary DECIMAL(12,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$connForSetup->query("CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    description TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)");
$connForSetup->query("CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    item_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)");
$connForSetup->query("CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
)");
$connForSetup->close();
?>
