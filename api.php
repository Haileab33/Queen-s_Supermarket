<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $extra = []) {
    http_response_code($success ? 200 : 400);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit();
}

$action = $_POST['action'] ?? '';

$cart_actions = ['get_cart', 'add_to_cart', 'update_cart_qty', 'remove_from_cart', 'clear_cart'];
$user_actions = ['update_own_account'];

if (!isAdmin() && !in_array($action, $cart_actions) && !in_array($action, $user_actions)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit();
}

$conn = getDBConnection();
$actorId = currentUserId();

if (in_array($action, ['get_cart', 'add_to_cart', 'update_cart_qty', 'remove_from_cart', 'clear_cart'])) {
    if (!$actorId) {
        respond(false, 'Session expired or invalid user. Please log in again.');
    }
}

if ($action === 'get_cart') {
    $stmt = $conn->prepare("
        SELECT c.id, c.inventory_id, c.quantity, i.name, i.price, i.unit, i.quantity as stock, i.category
        FROM cart c
        JOIN inventory i ON c.inventory_id = i.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param('i', $actorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    $conn->close();
    respond(true, 'Cart fetched.', ['items' => $items]);
}

if ($action === 'add_to_cart') {
    $itemId = (int)($_POST['inventory_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);

    if ($itemId <= 0) respond(false, 'Invalid item.');

    // Check stock
    $stmt = $conn->prepare("SELECT name, quantity FROM inventory WHERE id = ?");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) respond(false, 'Item not found.');
    if ($item['quantity'] < $qty) respond(false, 'Not enough stock available.');

    // Check if already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND inventory_id = ?");
    $stmt->bind_param('ii', $actorId, $itemId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $newQty = $existing['quantity'] + $qty;
        if ($item['quantity'] < $newQty) respond(false, 'Cannot add more, exceeds available stock.');
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param('ii', $newQty, $existing['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, inventory_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $actorId, $itemId, $qty);
    }

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(true, 'Item added to cart.');
    } else {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to add item.');
    }
}

if ($action === 'update_cart_qty') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $newQty = (int)($_POST['quantity'] ?? 1);

    if ($cartId <= 0 || $newQty <= 0) respond(false, 'Invalid request.');

    // Check stock
    $stmt = $conn->prepare("
        SELECT i.quantity as stock 
        FROM cart c 
        JOIN inventory i ON c.inventory_id = i.id 
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->bind_param('ii', $cartId, $actorId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) respond(false, 'Cart item not found.');
    if ($data['stock'] < $newQty) respond(false, 'Requested quantity exceeds available stock.');

    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('iii', $newQty, $cartId, $actorId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(true, 'Cart updated.');
    } else {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to update cart.');
    }
}

if ($action === 'remove_from_cart') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $cartId, $actorId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    respond(true, 'Item removed.');
}

if ($action === 'clear_cart') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $actorId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    respond(true, 'Cart cleared.');
}

if ($action === 'add_item') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pcs');
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $category === '') {
        respond(false, 'Name and category are required.');
    }

    if ($quantity < 0 || $price < 0) {
        respond(false, 'Quantity and price must be zero or greater.');
    }

    $stmt = $conn->prepare('INSERT INTO inventory (name, category, quantity, price, unit, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssidssi', $name, $category, $quantity, $price, $unit, $description, $actorId);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to add item.');
    }

    $itemId = $stmt->insert_id;
    $stmt->close();
    logActivity($conn, $actorId, 'ADD_ITEM', $itemId, 'Added inventory item: ' . $name);
    $conn->close();
    respond(true, 'Inventory item added successfully.');
}

if ($action === 'edit_item') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pcs');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0) {
        respond(false, 'Invalid inventory item.');
    }

    if ($name === '' || $category === '') {
        respond(false, 'Name and category are required.');
    }

    if ($quantity < 0 || $price < 0) {
        respond(false, 'Quantity and price must be zero or greater.');
    }

    $stmt = $conn->prepare('UPDATE inventory SET name = ?, category = ?, quantity = ?, price = ?, unit = ?, description = ? WHERE id = ?');
    $stmt->bind_param('ssidssi', $name, $category, $quantity, $price, $unit, $description, $id);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to update item.');
    }

    $stmt->close();
    logActivity($conn, $actorId, 'EDIT_ITEM', $id, 'Updated inventory item: ' . $name);
    $conn->close();
    respond(true, 'Inventory item updated successfully.');
}

if ($action === 'delete_item') {
    $id = (int)($_POST['id'] ?? $_POST['deleteItemId'] ?? 0);

    if ($id <= 0) {
        respond(false, 'Invalid inventory item.');
    }

    $find = $conn->prepare('SELECT name FROM inventory WHERE id = ?');
    $find->bind_param('i', $id);
    $find->execute();
    $result = $find->get_result();
    $item = $result->fetch_assoc();
    $find->close();

    if (!$item) {
        $conn->close();
        respond(false, 'Inventory item not found.');
    }

    $stmt = $conn->prepare('DELETE FROM inventory WHERE id = ?');
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to delete item.');
    }

    $stmt->close();
    logActivity($conn, $actorId, 'DELETE_ITEM', $id, 'Deleted inventory item: ' . $item['name']);
    $conn->close();
    respond(true, 'Inventory item deleted successfully.');
}

if ($action === 'create_admin') {
    $username = trim($_POST['username'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $occupation = trim($_POST['occupation'] ?? '');
    $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? (float)$_POST['salary'] : null;
    $role = trim($_POST['role'] ?? 'admin');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($username === '' || $firstName === '' || $lastName === '' || $password === '') {
        respond(false, 'First name, last name, username, and password are required.');
    }

    if ($age !== null && $age <= 0) {
        respond(false, 'Age must be a positive number.');
    }

    if ($salary !== null && $salary < 0) {
        respond(false, 'Salary must be zero or greater.');
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        respond(false, 'Invalid role selected.');
    }

    if (strlen($password) < 6) {
        respond(false, 'Password must be at least 6 characters.');
    }

    if ($password !== $confirm) {
        respond(false, 'Passwords do not match.');
    }

    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();
        respond(false, 'Username already exists.');
    }
    $check->close();

    // Construct full name dynamically
    $fullName = trim($firstName . ' ' . $middleName);
    $fullName = trim($fullName . ' ' . $lastName);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $age = $age ?? 0;
    $salary = $salary ?? 0.00;
    $stmt = $conn->prepare('INSERT INTO users (username, password, full_name, role, first_name, middle_name, last_name, age, occupation, salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssssisd', $username, $hash, $fullName, $role, $firstName, $middleName, $lastName, $age, $occupation, $salary);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to create account.');
    }

    $newUserId = $stmt->insert_id;
    $stmt->close();
    logActivity($conn, $actorId, 'CREATE_' . strtoupper($role), $newUserId, 'Created ' . $role . ' account: ' . $username);
    $conn->close();
    respond(true, ucfirst($role) . ' account created successfully.');
}

if ($action === 'update_own_account') {
    if (!$actorId) respond(false, 'Not logged in.');

    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $occupation = trim($_POST['occupation'] ?? '');
    $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? (float)$_POST['salary'] : null;

    if ($firstName === '' || $lastName === '') {
        respond(false, 'First name and last name are required.');
    }

    $age = $age ?? 0;
    $salary = $salary ?? 0.00;
    $fullName = trim($firstName . ' ' . $middleName);
    $fullName = trim($fullName . ' ' . $lastName);

    $stmt = $conn->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, full_name = ?, age = ?, occupation = ?, salary = ? WHERE id = ?');
    $stmt->bind_param('ssssisdi', $firstName, $middleName, $lastName, $fullName, $age, $occupation, $salary, $actorId);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to update account.');
    }

    // Update session name
    $_SESSION['username'] = $fullName;

    $stmt->close();
    logActivity($conn, $actorId, 'EDIT_PROFILE', $actorId, 'User updated own profile.');
    $conn->close();
    respond(true, 'Account updated successfully.');
}

if ($action === 'update_user') {
    $id = (int)($_POST['id'] ?? 0);
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $occupation = trim($_POST['occupation'] ?? '');
    $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? (float)$_POST['salary'] : null;
    $role = trim($_POST['role'] ?? 'user');
    $password = $_POST['password'] ?? '';

    if ($id <= 0 || $firstName === '' || $lastName === '') {
        respond(false, 'First name, last name are required.');
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        respond(false, 'Invalid role selected.');
    }

    $age = $age ?? 0;
    $salary = $salary ?? 0.00;
    $fullName = trim($firstName . ' ' . $middleName);
    $fullName = trim($fullName . ' ' . $lastName);

    if ($password !== '') {
        if (strlen($password) < 6) {
            respond(false, 'Password must be at least 6 characters.');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, full_name = ?, age = ?, occupation = ?, salary = ?, role = ?, password = ? WHERE id = ?');
        $stmt->bind_param('ssssisdsi', $firstName, $middleName, $lastName, $fullName, $age, $occupation, $salary, $role, $hash, $id);
    } else {
        $stmt = $conn->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, full_name = ?, age = ?, occupation = ?, salary = ?, role = ? WHERE id = ?');
        $stmt->bind_param('ssssisdsi', $firstName, $middleName, $lastName, $fullName, $age, $occupation, $salary, $role, $id);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        respond(false, 'Failed to update user.');
    }

    $stmt->close();
    logActivity($conn, $actorId, 'EDIT_USER', $id, 'Updated user account: ' . ($_POST['username'] ?? ''));
    $conn->close();
    respond(true, 'User updated successfully.');
}

$conn->close();
respond(false, 'Unknown action.');
