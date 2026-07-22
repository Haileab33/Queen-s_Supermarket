<?php
require_once __DIR__ . '/db.php';
requireLogin();

$conn = getDBConnection();

$inventoryItems = [];
$categories = [];
$inventoryValue = 0;
$lowStockCount = 0;

$result = $conn->query('SELECT id, name, category, quantity, price, unit, description FROM inventory ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    if ((int)$row['quantity'] <= 0) continue;
    $inventoryItems[] = $row;
    $categories[$row['category']] = true;
    $inventoryValue += ((float)$row['price'] * (int)$row['quantity']);
    if ((int)$row['quantity'] <= 5) {
        $lowStockCount++;
    }
}

$displayName = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $displayName), 0, 2));
if ($initials === '') {
    $initials = 'U';
}

// Fetch current user data for account management
$currentUser = [];
if ($userId = currentUserId()) {
    $userResult = $conn->query("SELECT id, username, first_name, middle_name, last_name, full_name, age, occupation, salary FROM users WHERE id = $userId");
    $currentUser = $userResult->fetch_assoc() ?: [];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Queen's Supermarket</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <span class="brand-icon">QS</span>
        <h2>Queen's Supermarket</h2>
        <p>Inventory System</p>
      </div>

      <div class="sidebar-user">
        <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="user-info">
          <div class="name"><?= htmlspecialchars($displayName) ?></div>
          <div class="role-badge user-role"><?= htmlspecialchars($role) ?></div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">Workspace</div>
        <a class="nav-item" data-page="dashboard.php" href="dashboard.php">
          <span class="nav-icon">🏠</span>
          <span>Inventory View</span>
        </a>
        <a class="nav-item" href="javascript:void(0)" onclick="openModal('manageAccountModal')">
          <span class="nav-icon">👤</span>
          <span>Manage Account</span>
        </a>
        <?php if (isAdmin()): ?>
          <div class="nav-label">Administration</div>
          <a class="nav-item" href="admin.php?view=inventory">
            <span class="nav-icon">📦</span>
            <span>Inventory Manager</span>
          </a>
          <a class="nav-item" href="admin.php?view=users">
            <span class="nav-icon">👥</span>
            <span>User List</span>
          </a>
        <?php endif; ?>
      </nav>

      <div class="sidebar-footer">
        <a class="btn-logout" href="logout.php">Log Out</a>
      </div>
    </aside>

    <div class="sidebar-overlay"></div>

    <main class="main-content">
      <header class="topbar">
        <div class="topbar-title">
          <h1>User Dashboard</h1>
          <p>Browse the latest inventory saved in the database.</p>
        </div>
        <div class="topbar-actions">
          <div class="cart-badge-container">
            <button class="btn btn-outline btn-icon" title="View Cart" onclick="toggleCart(true)">
              🛒
            </button>
            <span id="cartCountBadge" class="cart-count-badge" style="display:none;">0</span>
          </div>
          <button class="hamburger" type="button" aria-label="Open navigation">
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </header>

      <section class="page-content">
        <section class="page-hero">
          <div class="page-hero-copy">
            <div class="eyebrow">Store Snapshot</div>
            <h2>Dashboard</h2>
            <p>Search the full inventory, check category availability, and spot low-stock products before they become a problem.</p>
          </div>
          <div class="page-hero-note">
            <span class="hero-note-label">Access Level</span>
            <strong><?= htmlspecialchars(ucfirst($role)) ?></strong>
            <p>Access your inventory data below.</p>
          </div>
        </section>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon icon-green">#</div>
            <div class="stat-info">
              <div class="value" data-animate="<?= count($inventoryItems) ?>"><?= count($inventoryItems) ?></div>
              <div class="label">Items Available</div>
            </div>
          </div>
          <?php if (isAdmin()): ?>
            <div class="stat-card">
              <div class="stat-icon icon-gold">ETB</div>
              <div class="stat-info">
                <div class="value" data-animate="<?= number_format($inventoryValue, 2, '.', '') ?>" data-prefix="$"><?= number_format($inventoryValue, 2) ?></div>
                <div class="label">Inventory Value</div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div>
              <h2 class="panel-title">Inventory List</h2>
              <p class="panel-subtitle">A live read-only catalog for your current store data.</p>
            </div>
          </div>
          <div class="search-bar" style="padding:0 24px 16px;">
            <div class="search-input-wrap">
              <span class="search-icon">&#x1F50D;</span>
              <input type="text" id="searchInput" class="search-input" placeholder="Search items by name">
            </div>
            <input type="number" id="priceMin" class="search-input" placeholder="Min price" min="0" step="0.01" style="min-width:100px;">
            <input type="number" id="priceMax" class="search-input" placeholder="Max price" min="0" step="0.01" style="min-width:100px;">
            <select id="categoryFilter" class="filter-select">
              <option value="">All Categories</option>
              <?php foreach (array_keys($categories) as $category): ?>
                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
              <?php endforeach; ?>
            </select>
            <span id="filterCount" class="role-badge user-role"><?= count($inventoryItems) ?> items</span>
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
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="inventoryTableBody">
                <?php foreach ($inventoryItems as $item): ?>
                  <?php
                    $quantity = (int)$item['quantity'];
                    if ($quantity <= 0) continue;
                    $stockClass = $quantity <= 5 ? 'stock-low' : 'stock-ok';
                  ?>
                  <tr data-name="<?= htmlspecialchars($item['name']) ?>" data-category="<?= htmlspecialchars($item['category']) ?>" data-price="<?= (float)$item['price'] ?>">
                    <td class="item-name">
                      <?= htmlspecialchars($item['name']) ?>
                      <small><?= htmlspecialchars($item['description'] ?: 'No description provided') ?></small>
                    </td>
                    <td><span class="category-badge"><?= htmlspecialchars($item['category']) ?></span></td>
                    <td><span class="stock-badge <?= $stockClass ?>"><?= $quantity ?></span></td>
                    <td class="price" data-price="<?= (float)$item['price'] ?>">$<?= number_format((float)$item['price'], 2) ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?: 'pcs') ?></td>
                    <td>
                      <button class="btn btn-primary btn-sm" onclick="addToCart(<?= (int)$item['id'] ?>)">Add to Cart</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div id="emptyState" class="empty-state" style="display:none;">
            <div class="empty-icon">...</div>
            <h3>No matching inventory items</h3>
            <p>Try a different search term, price range, or category filter.</p>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div id="manageAccountModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Manage Account</h3>
        <button type="button" class="modal-close" onclick="closeModal('manageAccountModal')">x</button>
      </div>
      <form id="manageAccountForm">
        <div class="modal-body">
          <div class="form-row form-row-3col">
            <div class="form-group">
              <label for="acctFirstName">First Name</label>
              <input type="text" id="acctFirstName" name="first_name" class="form-control" value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label for="acctMiddleName">Middle Name</label>
              <input type="text" id="acctMiddleName" name="middle_name" class="form-control" placeholder="Optional" value="<?= htmlspecialchars($currentUser['middle_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="acctLastName">Last Name</label>
              <input type="text" id="acctLastName" name="last_name" class="form-control" value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-row form-row-3col">
            <div class="form-group">
              <label for="acctAge">Age</label>
              <input type="number" id="acctAge" name="age" class="form-control" min="1" max="120" value="<?= htmlspecialchars($currentUser['age'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="acctOccupation">Occupation</label>
              <input type="text" id="acctOccupation" name="occupation" class="form-control" value="<?= htmlspecialchars($currentUser['occupation'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="acctSalary">Salary (ETB)</label>
              <input type="number" id="acctSalary" name="salary" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($currentUser['salary'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" readonly style="background:#f5f0eb;cursor:not-allowed;">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('manageAccountModal')">Cancel</button>
          <button type="submit" class="btn btn-gold">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div id="cartSidebar" class="cart-sidebar">
    <div class="cart-header">
      <h2>Your Cart</h2>
      <button class="cart-close" onclick="toggleCart(false)">&times;</button>
    </div>
    <div id="cartItemsContainer" class="cart-items">
      <!-- Items will be injected here -->
    </div>
    <div class="cart-footer">
      <div class="delivery-options" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--cream-dark);">
        <label class="checkbox-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 0.9rem; color: var(--slate);">
          <input type="checkbox" id="pickupToggle" onchange="fetchCart()" style="width: 18px; height: 18px; cursor: pointer;">
          <span>I will pick up my items (No shipping fee)</span>
        </label>
      </div>
      <div id="cartSummary" class="cart-summary">
        <!-- Summary details injected here -->
      </div>
      <div class="cart-actions">
        <button class="btn btn-gold btn-block" onclick="proceedToCheckout()">Proceed to Checkout</button>
        <button class="btn btn-outline btn-block btn-sm" onclick="clearCart()">Clear Cart</button>
      </div>
    </div>
  </div>

  <div id="toast-container"></div>
  <script src="main.js?v=<?= time() ?>"></script>
</body>
</html>
