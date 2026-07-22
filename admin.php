<?php
require_once __DIR__ . '/db.php';
requireAdmin();

$conn = getDBConnection();

$inventoryItems = [];
$categories = [];
$inventoryResult = $conn->query('SELECT id, name, category, quantity, price, unit, description, updated_at FROM inventory ORDER BY name ASC');
while ($row = $inventoryResult->fetch_assoc()) {
    $inventoryItems[] = $row;
    $categories[$row['category']] = true;
}

$userCounts = ['admin' => 0, 'user' => 0];
$usersResult = $conn->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role");
while ($row = $usersResult->fetch_assoc()) {
    $userCounts[$row['role']] = (int)$row['total'];
}

$activityItems = [];
$activityResult = $conn->query("
    SELECT
        al.id,
        al.action,
        al.details,
        al.logged_at,
        al.item_id,
        u.full_name,
        u.username
    FROM activity_log al
    LEFT JOIN users u ON u.id = al.user_id
    ORDER BY al.logged_at DESC, al.id DESC
    LIMIT 12
");
while ($row = $activityResult->fetch_assoc()) {
    $activityItems[] = $row;
}

$stats = [
    'items' => count($inventoryItems),
    'low_stock' => 0,
    'inventory_value' => 0,
    'admins' => $userCounts['admin'],
];

foreach ($inventoryItems as $item) {
    if ((int)$item['quantity'] <= 5) {
        $stats['low_stock']++;
    }
    $stats['inventory_value'] += ((float)$item['price'] * (int)$item['quantity']);
}

$displayName = $_SESSION['username'] ?? 'Administrator';
$initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $displayName), 0, 2));
if ($initials === '') {
    $initials = 'AD';
}
$todayDate = date('Y-m-d');

$allUsers = [];
$usersListResult = $conn->query("SELECT id, username, full_name, role, first_name, middle_name, last_name, age, occupation, salary FROM users ORDER BY role ASC, full_name ASC");
while ($row = $usersListResult->fetch_assoc()) {
    $allUsers[] = $row;
}

$conn->close();

$currentView = $_GET['view'] ?? 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Queen's Supermarket</title>
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <span class="brand-icon">QS</span>
        <h2>Queen's Supermarket</h2>
        <p>Admin Control</p>
      </div>

      <div class="sidebar-user">
        <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="user-info">
          <div class="name"><?= htmlspecialchars($displayName) ?></div>
          <div class="role-badge">Admin</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">Administration</div>
        <a class="nav-item <?= $currentView === 'inventory' ? 'active' : '' ?>" id="nav-inventory" href="admin.php?view=inventory" onclick="event.preventDefault(); showPanel('inventory')">
          <span class="nav-icon">📦</span>
          <span>Inventory Manager</span>
        </a>
        <a class="nav-item <?= $currentView === 'activity' ? 'active' : '' ?>" id="nav-activity" href="admin.php?view=activity" onclick="event.preventDefault(); showPanel('activity')">
          <span class="nav-icon">📋</span>
          <span>Activity Log</span>
        </a>
        <a class="nav-item <?= $currentView === 'users' ? 'active' : '' ?>" id="nav-users" href="admin.php?view=users" onclick="event.preventDefault(); showPanel('users')">
          <span class="nav-icon">👥</span>
          <span>User List</span>
        </a>
        <div class="nav-label">Switch Workspace</div>
        <a class="nav-item" data-page="dashboard.php" href="dashboard.php">
          <span class="nav-icon">👁️</span>
          <span>Switch to User View</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <a class="btn-logout" href="logout.php">Log Out</a>
      </div>
    </aside>

    <div class="sidebar-overlay"></div>

    <main class="main-content">
      <header class="topbar">
        <div class="topbar-title">
          <?php if ($currentView === 'users'): ?>
            <h1>User Management</h1>
            <p>View and manage staff and admin accounts.</p>
          <?php elseif ($currentView === 'activity'): ?>
            <h1>Activity Log</h1>
            <p>Recent admin and user actions recorded by the system.</p>
          <?php else: ?>
            <h1>Admin Dashboard</h1>
            <p>Manage inventory items and create staff accounts.</p>
          <?php endif; ?>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-gold btn-sm" type="button" onclick="openModal('createAdminModal')">Create Account</button>
          <button class="btn btn-primary btn-sm" type="button" onclick="openAddModal()">Add Item</button>
          <button class="hamburger" type="button" aria-label="Open navigation">
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </header>

      <section class="page-content">
        <section class="page-hero" style="<?= $currentView === 'inventory' ? 'display:grid;' : 'display:none;' ?>">
          <div class="page-hero-copy">
            <div class="eyebrow">Operations Hub</div>
            <h2>Admin Panel</h2>
            <p>Use the admin workspace to keep the catalog accurate, move quickly on updates, and create new accounts with the right role.</p>
          </div>
          <div class="page-hero-note">
            <strong>Active Session</strong>
            <p>You have full administrative privileges.</p>
          </div>
        </section>

        <div class="stats-grid" style="<?= $currentView === 'inventory' ? 'display:grid;' : 'display:none;' ?>">
          <div class="stat-card">
            <div class="stat-icon icon-green">#</div>
            <div class="stat-info">
              <div class="value" data-animate="<?= $stats['items'] ?>"><?= $stats['items'] ?></div>
              <div class="label">Inventory Items</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon icon-red">!</div>
            <div class="stat-info">
              <div class="value" data-animate="<?= $stats['low_stock'] ?>"><?= $stats['low_stock'] ?></div>
              <div class="label">Low Stock Items</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon icon-gold">$</div>
            <div class="stat-info">
              <div class="value" data-animate="<?= number_format($stats['inventory_value'], 2, '.', '') ?>" data-prefix="$"><?= number_format($stats['inventory_value'], 2) ?></div>
              <div class="label">Inventory Value</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon icon-blue">@</div>
            <div class="stat-info">
              <div class="value" data-animate="<?= $stats['admins'] ?>"><?= $stats['admins'] ?></div>
              <div class="label">Admin Accounts</div>
            </div>
          </div>
        </div>

        <div id="usersPanel" class="panel" style="<?= $currentView === 'users' ? 'display:block;' : 'display:none;' ?>">
          <div class="panel-header">
            <div>
              <h2 class="panel-title">System Users</h2>
              <p class="panel-subtitle">Manage administrative and staff accounts.</p>
            </div>
            <div class="search-bar">
              <div class="search-input-wrap">
                <span class="search-icon">S</span>
                <input type="text" id="userSearchInput" class="search-input" placeholder="Search by name, username, or role">
              </div>
              <select id="userRoleFilter" class="filter-select">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
              <button class="btn btn-gold btn-sm" type="button" onclick="openModal('createAdminModal')">Add User</button>
            </div>
          </div>
          <div class="panel-quick-actions">
            <button class="btn btn-outline btn-sm" type="button" onclick="showPanel('inventory')">📦 Access Inventory Items</button>
            <button class="btn btn-outline btn-sm" type="button" onclick="showPanel('activity')">📋 Access Activity Log</button>
          </div>
          <div class="table-wrap">
            <table class="user-table">
              <thead>
                <tr>
                  <th>User Information</th>
                  <th>Role</th>
                  <th>Occupation & Age</th>
                  <th>Salary</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="userTableBody">
                <?php foreach ($allUsers as $u): ?>
                  <?php
                    $uInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $u['full_name'] ?: $u['username']), 0, 2));
                    if ($uInitials === '') { $uInitials = 'US'; }
                    
                    $age = !empty($u['age']) ? (int)$u['age'] . ' yrs' : '—';
                    $occupation = !empty($u['occupation']) ? htmlspecialchars($u['occupation']) : '—';
                    $salary = !empty($u['salary']) ? '$' . number_format((float)$u['salary'], 2) : '—';
                    $userJson = htmlspecialchars(json_encode([
                        'id' => (int)$u['id'],
                        'username' => $u['username'],
                        'full_name' => $u['full_name'],
                        'role' => $u['role'],
                        'first_name' => $u['first_name'],
                        'middle_name' => $u['middle_name'],
                        'last_name' => $u['last_name'],
                        'age' => $u['age'],
                        'occupation' => $u['occupation'],
                        'salary' => $u['salary'],
                    ]), ENT_QUOTES, 'UTF-8');
                    
                    $avatarColors = ['#e8f0fe', '#fff8e6', '#e6f4ea', '#fce8e6', '#f3e8fd'];
                    $colorIndex = (ord($uInitials[0] ?? 'A') + ord($uInitials[1] ?? 'B')) % count($avatarColors);
                    $avatarBg = $avatarColors[$colorIndex];
                  ?>
                  <tr data-name="<?= htmlspecialchars(strtolower($u['full_name'] ?: $u['username'])) ?>" data-username="<?= htmlspecialchars(strtolower($u['username'])) ?>" data-role="<?= htmlspecialchars($u['role']) ?>">
                    <td class="user-meta-cell">
                      <div class="user-avatar" style="background: <?= $avatarBg ?>;"><?= $uInitials ?></div>
                      <div class="user-meta-info">
                        <span class="user-fullname"><?= htmlspecialchars($u['full_name']) ?></span>
                        <span class="user-username">@<?= htmlspecialchars($u['username']) ?></span>
                      </div>
                    </td>
                    <td>
                      <span class="role-badge <?= $u['role'] === 'admin' ? '' : 'user-role' ?>">
                        <?= htmlspecialchars($u['role']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="user-details-cell">
                        <strong><?= $occupation ?></strong>
                        <span><?= $age ?></span>
                      </div>
                    </td>
                    <td class="user-salary-cell"><?= $salary ?></td>
                    <td>
                      <button class="btn btn-outline btn-sm" type="button" onclick='openEditUserModal(<?= $userJson ?>)'>Edit</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div id="inventoryPanel" class="panel" style="<?= $currentView === 'inventory' ? 'display:block;' : 'display:none;' ?>">
            <div class="panel-header">
              <div>
                <h2 class="panel-title">Inventory Items</h2>
                <p class="panel-subtitle">Quick search, category filtering, and modal editing for the full catalog.</p>
              </div>
              <div class="search-bar">
                <div class="search-input-wrap">
                  <span class="search-icon">S</span>
                  <input type="text" id="searchInput" class="search-input" placeholder="Search items by name">
                </div>
                <select id="categoryFilter" class="filter-select">
                  <option value="">All Categories</option>
                  <?php foreach (array_keys($categories) as $category): ?>
                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                  <?php endforeach; ?>
                </select>
                <span id="filterCount" class="role-badge user-role"><?= count($inventoryItems) ?> items</span>
              <button class="btn btn-primary btn-sm" type="button" onclick="openAddModal()">Add Item</button>
              </div>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="inventoryTableBody">
                  <?php foreach ($inventoryItems as $item): ?>
                    <?php
                      $quantity = (int)$item['quantity'];
                      $stockClass = $quantity <= 0 ? 'stock-out' : ($quantity <= 5 ? 'stock-low' : 'stock-ok');
                      $payload = htmlspecialchars(json_encode([
                          'id' => (int)$item['id'],
                          'name' => $item['name'],
                          'category' => $item['category'],
                          'quantity' => $quantity,
                          'price' => (float)$item['price'],
                          'unit' => $item['unit'] ?? 'pcs',
                          'description' => $item['description'] ?? '',
                      ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr data-name="<?= htmlspecialchars($item['name']) ?>" data-category="<?= htmlspecialchars($item['category']) ?>">
                      <td class="item-name">
                        <?= htmlspecialchars($item['name']) ?>
                        <small><?= htmlspecialchars($item['description'] ?: 'No description provided') ?></small>
                      </td>
                      <td><span class="category-badge"><?= htmlspecialchars($item['category']) ?></span></td>
                      <td><span class="stock-badge <?= $stockClass ?>"><?= $quantity ?></span></td>
                      <td class="price">$<?= number_format((float)$item['price'], 2) ?></td>
                      <td><?= htmlspecialchars($item['unit'] ?: 'pcs') ?></td>
                      <td>
                        <div class="actions">
                          <button type="button" class="btn btn-outline btn-sm" onclick='openEditModal(<?= $payload ?>)'>Edit</button>
                          <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name']), ENT_QUOTES, 'UTF-8') ?>')">Delete</button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div id="emptyState" class="empty-state" style="display:none;">
              <div class="empty-icon">...</div>
              <h3>No matching inventory items</h3>
              <p>Try a different search term or category filter.</p>
            </div>
            <div class="panel-quick-actions">
              <button class="btn btn-outline btn-sm" type="button" onclick="showPanel('activity')">📋 Access Activity Log</button>
              <button class="btn btn-outline btn-sm" type="button" onclick="showPanel('users')">👥 Access User List</button>
            </div>
          </div>

          <div id="activityPanel" class="panel" style="<?= $currentView === 'activity' ? 'display:block;' : 'display:none;' ?>">
            <div class="panel-header">
              <div>
                <h2 class="panel-title">Activity Log</h2>
                <p class="panel-subtitle">Recent admin and user actions recorded by the system.</p>
              </div>
              <div class="search-bar activity-toolbar">
                <div class="search-input-wrap">
                  <span class="search-icon">A</span>
                  <input type="text" id="activityActorFilter" class="search-input" placeholder="Search by actor or details">
                </div>
                <select id="activityTypeFilter" class="filter-select">
                  <option value="">All Activity</option>
                  <option value="login">Logins Only</option>
                  <option value="inventory">Inventory Changes</option>
                  <option value="account">Account Actions</option>
                  <option value="other">Other Events</option>
                </select>
                <label class="toggle-filter">
                  <input type="checkbox" id="activityTodayOnly">
                  <span>Today's Activity</span>
                </label>
                <span id="activityCount" class="role-badge user-role"><?= count($activityItems) ?> recent events</span>
              </div>
            </div>

            <?php if ($activityItems): ?>
              <div class="activity-log" data-today="<?= htmlspecialchars($todayDate) ?>">
                <?php foreach ($activityItems as $activity): ?>
                  <?php
                    $actor = $activity['full_name'] ?: $activity['username'] ?: 'System Admin';
                    $actionLabel = str_replace('_', ' ', $activity['action']);
                    $timeLabel = date('M d, Y g:i A', strtotime($activity['logged_at']));
                    $actionType = 'other';
                    if ($activity['action'] === 'LOGIN') {
                        $actionType = 'login';
                    } elseif (in_array($activity['action'], ['ADD_ITEM', 'EDIT_ITEM', 'DELETE_ITEM'], true)) {
                        $actionType = 'inventory';
                    } elseif (strpos($activity['action'], 'CREATE_') === 0) {
                        $actionType = 'account';
                    }
                  ?>
                  <div
                    class="activity-item"
                    data-activity-type="<?= htmlspecialchars($actionType) ?>"
                    data-actor="<?= htmlspecialchars(strtolower($actor)) ?>"
                    data-details="<?= htmlspecialchars(strtolower(($activity['details'] ?: '') . ' ' . $actionLabel)) ?>"
                    data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($activity['logged_at']))) ?>"
                  >
                    <div class="activity-icon"><?= htmlspecialchars(substr($activity['action'], 0, 1)) ?></div>
                    <div class="activity-content">
                      <div class="activity-meta">
                        <strong><?= htmlspecialchars(ucwords(strtolower($actionLabel))) ?></strong>
                        <span><?= htmlspecialchars($timeLabel) ?></span>
                      </div>
                      <p><?= htmlspecialchars($activity['details'] ?: 'System activity recorded.') ?></p>
                      <div class="activity-footer">
                        <span>By <?= htmlspecialchars($actor) ?></span>
                        <?php if (!empty($activity['item_id'])): ?>
                          <span>Item ID <?= (int)$activity['item_id'] ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div id="activityEmptyState" class="empty-state" style="display:none;">
                <div class="empty-icon">...</div>
                <h3>No matching activity</h3>
                <p>Try another activity type, clear the actor search, or turn off today's filter.</p>
              </div>
            <?php else: ?>
              <div class="empty-state">
                <div class="empty-icon">...</div>
                <h3>No activity yet</h3>
                <p>Recent logins, inventory updates, and account creation events will appear here.</p>
              </div>
            <?php endif; ?>
            <div class="panel-quick-actions">
              <button class="btn btn-outline btn-sm" type="button" onclick="showPanel('inventory')">📦 Access Inventory Items</button>
              <button class="btn btn-outline btn-sm" type="button" onclick="showPanel('users')">👥 Access User List</button>
            </div>
          </div>
      </div>
      </section>
    </main>
  </div>

  <div id="itemModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modalTitle">Add New Item</h3>
        <button type="button" class="modal-close" onclick="closeModal('itemModal')">x</button>
      </div>
      <form id="itemForm">
        <div class="modal-body">
          <input type="hidden" id="itemId" name="id">

          <div class="form-group">
            <label for="itemName">Item Name</label>
            <input type="text" id="itemName" name="name" class="form-control" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="itemCategory">Category</label>
              <input type="text" id="itemCategory" name="category" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="itemUnit">Unit</label>
              <input type="text" id="itemUnit" name="unit" class="form-control" value="pcs" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="itemQuantity">Quantity</label>
              <input type="number" id="itemQuantity" name="quantity" class="form-control" min="0" required>
            </div>
            <div class="form-group">
              <label for="itemPrice">Price</label>
              <input type="number" id="itemPrice" name="price" class="form-control" min="0" step="0.01" required>
            </div>
          </div>

          <div class="form-group">
            <label for="itemDescription">Description</label>
            <textarea id="itemDescription" name="description" class="form-control" rows="4"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('itemModal')">Cancel</button>
          <button type="submit" class="btn btn-primary" id="itemSubmitBtn">Save Item</button>
        </div>
      </form>
    </div>
  </div>

  <div id="deleteModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Delete Item</h3>
        <button type="button" class="modal-close" onclick="closeModal('deleteModal')">x</button>
      </div>
      <form id="deleteForm">
        <div class="modal-body">
          <input type="hidden" id="deleteItemId" name="id">
          <p>Delete <strong id="deleteItemName"></strong> from inventory?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <div id="createAdminModal" class="modal-overlay">
    <div class="modal modal-lg">
      <div class="modal-header">
        <h3>Create Account</h3>
        <button type="button" class="modal-close" onclick="closeModal('createAdminModal')">x</button>
      </div>
      <form id="createAdminForm">
        <div class="modal-body">
          <div class="form-row form-row-3col">
            <div class="form-group">
              <label for="adminFirstName">First Name</label>
              <input type="text" id="adminFirstName" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="adminMiddleName">Middle Name</label>
              <input type="text" id="adminMiddleName" name="middle_name" class="form-control" placeholder="Optional">
            </div>
            <div class="form-group">
              <label for="adminLastName">Last Name</label>
              <input type="text" id="adminLastName" name="last_name" class="form-control" required>
            </div>
          </div>

          <div class="form-row form-row-3col">
            <div class="form-group">
              <label for="adminAge">Age</label>
              <input type="number" id="adminAge" name="age" class="form-control" min="1" max="120" required>
            </div>
            <div class="form-group">
              <label for="adminOccupation">Occupation</label>
              <input type="text" id="adminOccupation" name="occupation" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="adminSalary">Salary (ETB)</label>
              <input type="number" id="adminSalary" name="salary" class="form-control" min="0" step="0.01" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="adminRole">Role</label>
              <select id="adminRole" name="role" class="form-control" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
            </div>
            <div class="form-group">
              <label for="adminUsername">Username</label>
              <input type="text" id="adminUsername" name="username" class="form-control" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="adminPassword">Password</label>
              <input type="password" id="adminPassword" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
              <label for="adminConfirmPassword">Confirm Password</label>
              <input type="password" id="adminConfirmPassword" name="confirm_password" class="form-control" minlength="6" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('createAdminModal')">Cancel</button>
          <button type="submit" class="btn btn-gold">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <div id="editUserModal" class="modal-overlay">
    <div class="modal modal-lg">
      <div class="modal-header">
        <h3>Edit User</h3>
        <button type="button" class="modal-close" onclick="closeModal('editUserModal')">x</button>
      </div>
      <form id="editUserForm">
        <div class="modal-body">
          <input type="hidden" id="editUserId" name="id">
          <div class="form-row form-row-3col">
            <div class="form-group">
              <label for="editUserFirstName">First Name</label>
              <input type="text" id="editUserFirstName" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="editUserMiddleName">Middle Name</label>
              <input type="text" id="editUserMiddleName" name="middle_name" class="form-control" placeholder="Optional">
            </div>
            <div class="form-group">
              <label for="editUserLastName">Last Name</label>
              <input type="text" id="editUserLastName" name="last_name" class="form-control" required>
            </div>
          </div>
          <div class="form-row form-row-3col">
            <div class="form-group">
              <label for="editUserAge">Age</label>
              <input type="number" id="editUserAge" name="age" class="form-control" min="1" max="120">
            </div>
            <div class="form-group">
              <label for="editUserOccupation">Occupation</label>
              <input type="text" id="editUserOccupation" name="occupation" class="form-control">
            </div>
            <div class="form-group">
              <label for="editUserSalary">Salary (ETB)</label>
              <input type="number" id="editUserSalary" name="salary" class="form-control" min="0" step="0.01">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="editUserRole">Role</label>
              <select id="editUserRole" name="role" class="form-control" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="form-group">
              <label for="editUserUsername">Username</label>
              <input type="text" id="editUserUsername" name="username" class="form-control" readonly style="background:#f5f0eb;cursor:not-allowed;">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="editUserPassword">New Password <small>(leave blank to keep current)</small></label>
              <input type="password" id="editUserPassword" name="password" class="form-control" minlength="6" placeholder="Min 6 chars">
            </div>
            <div class="form-group">
              <label for="editUserConfirmPassword">Confirm Password</label>
              <input type="password" id="editUserConfirmPassword" name="confirm_password" class="form-control" placeholder="Repeat new password">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
          <button type="submit" class="btn btn-gold">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div id="toast-container"></div>
  <script src="main.js?v=<?= time() ?>"></script>
</body>
</html>
