<?php
require_once __DIR__ . '/db.php';

if (isLoggedIn()) {
  header('Location: ' . (isAdmin() ? 'admin.php' : 'dashboard.php'));
  exit();
}

$error = '';
$success = '';
$active_tab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'login';

  if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
      $error = 'Please enter your username and password.';
    } else {
      if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // Ensure hardcoded admin exists in database for feature support (like cart)
        $conn = getDBConnection();
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows === 0) {
          $hash = password_hash($password, PASSWORD_BCRYPT);
          $role = 'admin';
          $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, 'System Admin', ?)");
          $stmt->bind_param("sss", $username, $hash, $role);
          $stmt->execute();
          $userId = $stmt->insert_id;
          $stmt->close();
        } else {
          $uRow = $res->fetch_assoc();
          $userId = $uRow['id'];
        }
        $check->close();
        $conn->close();

        $_SESSION['user_id'] = $userId;
        $_SESSION['is_hardcoded_admin'] = true;
        $_SESSION['username'] = 'System Admin';
        $_SESSION['role'] = 'admin';
        header('Location: admin.php');
        exit();
      }

      $conn = getDBConnection();
      $stmt = $conn->prepare('SELECT id, username, password, full_name, role FROM users WHERE username = ?');
      $stmt->bind_param('s', $username);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();

      if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['full_name'] ?: $user['username'];
        $_SESSION['role'] = $user['role'];

        logActivity($conn, $user['id'], 'LOGIN', null, 'User logged in');
        $conn->close();

        header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit();
      }

      $error = 'Invalid username or password.';
      $conn->close();
    }
  }

  if ($action === 'register') {
    $active_tab = 'register';
    $username = trim($_POST['reg_username'] ?? '');
    $firstName = trim($_POST['reg_firstname'] ?? '');
    $middleName = trim($_POST['reg_middlename'] ?? '');
    $lastName = trim($_POST['reg_lastname'] ?? '');
    $age = isset($_POST['reg_age']) && $_POST['reg_age'] !== '' ? (int) $_POST['reg_age'] : null;
    $occupation = trim($_POST['reg_occupation'] ?? '');
    $salary = isset($_POST['reg_salary']) && $_POST['reg_salary'] !== '' ? (float) $_POST['reg_salary'] : null;
    $password = $_POST['reg_password'] ?? '';
    $confirm = $_POST['reg_confirm'] ?? '';

    if ($username === '' || $firstName === '' || $lastName === '' || $password === '') {
      $error = 'First name, last name, username, and password are required.';
    } elseif ($age !== null && $age <= 0) {
      $error = 'Age must be a positive number.';
    } elseif ($salary !== null && $salary < 0) {
      $error = 'Salary must be zero or greater.';
    } elseif (strlen($password) < 6) {
      $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
      $error = 'Passwords do not match.';
    } else {
      $conn = getDBConnection();
      $chk = $conn->prepare('SELECT id FROM users WHERE username = ?');
      $chk->bind_param('s', $username);
      $chk->execute();
      $chk->store_result();

      if ($chk->num_rows > 0) {
        $error = 'Username already taken. Please choose another.';
      } else {
        // Construct full name dynamically
        $fullName = trim($firstName . ' ' . $middleName);
        $fullName = trim($fullName . ' ' . $lastName);

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $ins = $conn->prepare("INSERT INTO users (username, password, full_name, role, first_name, middle_name, last_name, age, occupation, salary) VALUES (?, ?, ?, 'user', ?, ?, ?, ?, ?, ?)");
        $age = $age ?? 0;
        $salary = $salary ?? 0.00;
        $ins->bind_param('ssssssisd', $username, $hashed, $fullName, $firstName, $middleName, $lastName, $age, $occupation, $salary);
        if ($ins->execute()) {
          $newUserId = $ins->insert_id;
          logActivity($conn, $newUserId, 'REGISTER', $newUserId, 'User registered account: ' . $username);
          $success = 'Account created! You can now log in.';
          $active_tab = 'login';
        } else {
          $error = 'Registration failed. Please try again.';
        }
        $ins->close();
      }

      $chk->close();
      $conn->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Queen's Supermarket</title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="login-body">
  <div class="login-wrapper">
    <section class="login-card">
      <div class="login-card-header">
        <div>
          <h1>Queen's Supermarket</h1>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <div class="login-tabs">
        <button class="tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" data-tab="login">Sign In</button>
        <button class="tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>" data-tab="register">Create
          Account</button>
      </div>

      <div class="tab-panel <?= $active_tab === 'login' ? 'active' : '' ?>" id="panel-login">
        <form method="POST" action="">
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Enter your username"
              autocomplete="username" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password"
              autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
      </div>

      <div class="tab-panel <?= $active_tab === 'register' ? 'active' : '' ?>" id="panel-register">
        <form method="POST" action="">
          <input type="hidden" name="action" value="register">
          <div class="form-row form-row-3col">
            <div class="form-group">
              <label>First Name</label>
              <input type="text" name="reg_firstname" class="form-control" placeholder="First name" value="<?= htmlspecialchars($_POST['reg_firstname'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="reg_middlename" class="form-control" placeholder="Optional" value="<?= htmlspecialchars($_POST['reg_middlename'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" name="reg_lastname" class="form-control" placeholder="Last name" value="<?= htmlspecialchars($_POST['reg_lastname'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-row form-row-3col">
            <div class="form-group">
              <label>Age</label>
              <input type="number" name="reg_age" class="form-control" placeholder="Age" min="1" max="120" value="<?= htmlspecialchars($_POST['reg_age'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Occupation</label>
              <input type="text" name="reg_occupation" class="form-control" placeholder="Occupation" value="<?= htmlspecialchars($_POST['reg_occupation'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Salary (ETB)</label>
              <input type="number" name="reg_salary" class="form-control" placeholder="Salary" min="0" step="0.01" value="<?= htmlspecialchars($_POST['reg_salary'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Role</label>
              <select class="form-control" disabled>
                <option selected>User</option>
              </select>
              <input type="hidden" name="role" value="user">
            </div>
            <div class="form-group">
              <label>Username</label>
              <input type="text" name="reg_username" class="form-control" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Password</label>
              <input type="password" name="reg_password" class="form-control" placeholder="Min 6 chars" required>
            </div>
            <div class="form-group">
              <label>Confirm Password</label>
              <input type="password" name="reg_confirm" class="form-control" placeholder="Repeat password" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
      </div>
    </section>
  </div>

  <div id="toast-container"></div>
  <script src="main.js?v=<?= time() ?>"></script>
</body>

</html>