// js/main.js — Queen's Supermarket Inventory System

// ── TOAST NOTIFICATIONS ──────────────────────────────
function showToast(message, type = "default") {
  const container = document.getElementById("toast-container");
  if (!container) return;
  const icons = { success: "✅", error: "❌", default: "ℹ️" };
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span>${icons[type] || icons.default}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateX(60px)";
    toast.style.transition = "all 0.3s ease";
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ── MODAL HELPERS ────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add("open");
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove("open");
}

// Close modal when clicking overlay backdrop
document.querySelectorAll(".modal-overlay").forEach((overlay) => {
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) overlay.classList.remove("open");
  });
});

// ── SIDEBAR TOGGLE (MOBILE) ──────────────────────────
const sidebar = document.querySelector(".sidebar");
const overlay = document.querySelector(".sidebar-overlay");
const hamburger = document.querySelector(".hamburger");

function toggleSidebar() {
  sidebar?.classList.toggle("open");
  overlay?.classList.toggle("open");
}

hamburger?.addEventListener("click", toggleSidebar);
overlay?.addEventListener("click", toggleSidebar);

// ── PANEL SWITCHING (ADMIN) ─────────────────────────
function showPanel(panelId) {
  const invPanel = document.getElementById("inventoryPanel");
  const activityPanel = document.getElementById("activityPanel");
  const usersPanel = document.getElementById("usersPanel");
  const navUsers = document.getElementById("nav-users");
  const navInv = document.getElementById("nav-inventory");
  const navActivity = document.getElementById("nav-activity");
  const titleH1 = document.querySelector(".topbar-title h1");
  const titleP = document.querySelector(".topbar-title p");
  const pageHero = document.querySelector(".page-hero");
  const statsGrid = document.querySelector(".stats-grid");
  
  if (!invPanel || !activityPanel || !usersPanel) return;

  // Reset displays
  invPanel.style.display = 'none';
  activityPanel.style.display = 'none';
  usersPanel.style.display = 'none';

  // Reset active classes
  navInv?.classList.remove('active');
  navActivity?.classList.remove('active');
  navUsers?.classList.remove('active');

  if (panelId === 'users') {
    usersPanel.style.display = 'block';
    navUsers?.classList.add('active');
    if (pageHero) pageHero.style.display = 'none';
    if (statsGrid) statsGrid.style.display = 'none';
    if (titleH1) titleH1.textContent = "User Management";
    if (titleP) titleP.textContent = "View and manage staff and admin accounts.";
  } else if (panelId === 'activity') {
    activityPanel.style.display = 'block';
    navActivity?.classList.add('active');
    if (pageHero) pageHero.style.display = 'none';
    if (statsGrid) statsGrid.style.display = 'none';
    if (titleH1) titleH1.textContent = "Activity Log";
    if (titleP) titleP.textContent = "Recent admin and user actions recorded by the system.";
  } else {
    invPanel.style.display = 'block';
    navInv?.classList.add('active');
    if (pageHero) pageHero.style.display = 'grid';
    if (statsGrid) statsGrid.style.display = 'grid';
    if (titleH1) titleH1.textContent = "Admin Dashboard";
    if (titleP) titleP.textContent = "Manage inventory items and store operations.";
  }

  // Persist view state in URL
  const newUrl = window.location.pathname + '?view=' + panelId;
  window.history.replaceState({ panelId }, '', newUrl);
}

// ── LOGIN PAGE TABS ──────────────────────────────────
function switchTab(tabName) {
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.tab === tabName);
  });
  document.querySelectorAll(".tab-panel").forEach((panel) => {
    panel.classList.toggle("active", panel.id === `panel-${tabName}`);
  });
}

document.querySelectorAll(".tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => switchTab(btn.dataset.tab));
});

// ── INVENTORY SEARCH & FILTER ────────────────────────
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const tableBody = document.getElementById("inventoryTableBody");
const priceMin = document.getElementById("priceMin");
const priceMax = document.getElementById("priceMax");

function filterInventory() {
  if (!tableBody) return;
  const query = (searchInput?.value || "").toLowerCase().trim();
  const cat = (categoryFilter?.value || "").toLowerCase();
  const minP = parseFloat(priceMin?.value) || 0;
  const maxP = parseFloat(priceMax?.value) || Infinity;
  const rows = tableBody.querySelectorAll("tr[data-name]");
  let visible = 0;

  rows.forEach((row) => {
    const name = (row.dataset.name || "").toLowerCase();
    const rowCat = (row.dataset.category || "").toLowerCase();
    const price = parseFloat(row.dataset.price) || 0;
    const matchName = !query || name.includes(query);
    const matchCat = !cat || rowCat === cat;
    const matchPrice = price >= minP && price <= maxP;
    const show = matchName && matchCat && matchPrice;
    row.style.display = show ? "" : "none";
    if (show) visible++;
  });

  const empty = document.getElementById("emptyState");
  if (empty) empty.style.display = visible === 0 ? "" : "none";

  updateFilterCount(visible);
}

function updateFilterCount(count) {
  const el = document.getElementById("filterCount");
  if (el) el.textContent = `${count} item${count !== 1 ? "s" : ""}`;
}

searchInput?.addEventListener("input", filterInventory);
categoryFilter?.addEventListener("change", filterInventory);
priceMin?.addEventListener("input", filterInventory);
priceMax?.addEventListener("input", filterInventory);

// ── USER SEARCH & FILTER (ADMIN) ─────────────────────
const userSearchInput = document.getElementById("userSearchInput");
const userRoleFilter = document.getElementById("userRoleFilter");
const userTableBody = document.getElementById("userTableBody");

function filterUsers() {
  if (!userTableBody) return;
  const query = (userSearchInput?.value || "").toLowerCase().trim();
  const roleFilter = (userRoleFilter?.value || "").toLowerCase();
  const rows = userTableBody.querySelectorAll("tr[data-name]");
  let visible = 0;

  rows.forEach((row) => {
    const name = (row.dataset.name || "").toLowerCase();
    const username = (row.dataset.username || "").toLowerCase();
    const userRole = (row.dataset.role || "").toLowerCase();
    const matchesSearch = !query || name.includes(query) || username.includes(query) || userRole.includes(query);
    const matchesRole = !roleFilter || userRole === roleFilter;
    const show = matchesSearch && matchesRole;
    row.style.display = show ? "" : "none";
    if (show) visible++;
  });

  const empty = document.querySelector(".empty-state");
  if (empty) empty.style.display = visible === 0 ? "" : "none";
}

userSearchInput?.addEventListener("input", filterUsers);
userRoleFilter?.addEventListener("change", filterUsers);

// Activity log filters
const activityLog = document.querySelector(".activity-log");
const activityTypeFilter = document.getElementById("activityTypeFilter");
const activityActorFilter = document.getElementById("activityActorFilter");
const activityTodayOnly = document.getElementById("activityTodayOnly");

function filterActivityLog() {
  if (!activityLog) return;

  const type = (activityTypeFilter?.value || "").toLowerCase();
  const query = (activityActorFilter?.value || "").toLowerCase().trim();
  const todayOnly = !!activityTodayOnly?.checked;
  const today = activityLog.dataset.today || "";
  const items = activityLog.querySelectorAll(".activity-item");
  let visible = 0;

  items.forEach((item) => {
    const itemType = (item.dataset.activityType || "").toLowerCase();
    const actor = (item.dataset.actor || "").toLowerCase();
    const details = (item.dataset.details || "").toLowerCase();
    const itemDate = item.dataset.date || "";

    const matchesType = !type || itemType === type;
    const matchesQuery = !query || actor.includes(query) || details.includes(query);
    const matchesToday = !todayOnly || itemDate === today;
    const show = matchesType && matchesQuery && matchesToday;

    item.style.display = show ? "" : "none";
    if (show) visible++;
  });

  const empty = document.getElementById("activityEmptyState");
  if (empty) empty.style.display = visible === 0 ? "" : "none";

  const count = document.getElementById("activityCount");
  if (count) count.textContent = `${visible} recent event${visible !== 1 ? "s" : ""}`;
}

activityTypeFilter?.addEventListener("change", filterActivityLog);
activityActorFilter?.addEventListener("input", filterActivityLog);
activityTodayOnly?.addEventListener("change", filterActivityLog);

// ── ADMIN: ADD / EDIT ITEM ───────────────────────────
function openAddModal() {
  document.getElementById("modalTitle").textContent = "Add New Item";
  document.getElementById("itemForm").reset();
  document.getElementById("itemId").value = "";
  const btn = document.getElementById("itemSubmitBtn");
  if (btn) btn.textContent = "Add Item";
  openModal("itemModal");
}

function openEditModal(item) {
  document.getElementById("modalTitle").textContent = "Edit Item";
  document.getElementById("itemId").value = item.id;
  document.getElementById("itemName").value = item.name;
  document.getElementById("itemCategory").value = item.category;
  document.getElementById("itemQuantity").value = item.quantity;
  document.getElementById("itemPrice").value = item.price;
  document.getElementById("itemUnit").value = item.unit;
  document.getElementById("itemDescription").value = item.description || "";
  const btn = document.getElementById("itemSubmitBtn");
  if (btn) btn.textContent = "Update Item";
  openModal("itemModal");
}

function confirmDelete(id, name) {
  document.getElementById("deleteItemId").value = id;
  document.getElementById("deleteItemName").textContent = name;
  openModal("deleteModal");
}

// ── ITEM FORM SUBMIT (AJAX) ──────────────────────────
document
  .getElementById("itemForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById("itemId").value;
    formData.append("action", id ? "edit_item" : "add_item");

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, "success");
        closeModal("itemModal");
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || "An error occurred.", "error");
      }
    } catch {
      showToast("Network error. Please try again.", "error");
    }
  });

// ── DELETE FORM SUBMIT (AJAX) ────────────────────────
document
  .getElementById("deleteForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "delete_item");

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, "success");
        closeModal("deleteModal");
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || "An error occurred.", "error");
      }
    } catch {
      showToast("Network error. Please try again.", "error");
    }
  });

// ── CREATE ADMIN FORM (AJAX) ─────────────────────────
document
  .getElementById("createAdminForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "create_admin");

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, "success");
        closeModal("createAdminModal");
        this.reset();
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(data.message || "An error occurred.", "error");
      }
    } catch {
      showToast("Network error. Please try again.", "error");
    }
  });

// ── ADMIN: EDIT USER ────────────────────────────────
function openEditUserModal(user) {
  document.getElementById("editUserId").value = user.id;
  document.getElementById("editUserFirstName").value = user.first_name || "";
  document.getElementById("editUserMiddleName").value = user.middle_name || "";
  document.getElementById("editUserLastName").value = user.last_name || "";
  document.getElementById("editUserAge").value = user.age || "";
  document.getElementById("editUserOccupation").value = user.occupation || "";
  document.getElementById("editUserSalary").value = user.salary || "";
  document.getElementById("editUserRole").value = user.role || "user";
  document.getElementById("editUserUsername").value = user.username || "";
  document.getElementById("editUserPassword").value = "";
  document.getElementById("editUserConfirmPassword").value = "";
  openModal("editUserModal");
}

document
  .getElementById("editUserForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "update_user");

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, "success");
        closeModal("editUserModal");
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || "An error occurred.", "error");
      }
    } catch {
      showToast("Network error. Please try again.", "error");
    }
  });

// ── MANAGE ACCOUNT FORM (USER) ───────────────────────
document
  .getElementById("manageAccountForm")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "update_own_account");

    try {
      const res = await fetch("api.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, "success");
        closeModal("manageAccountModal");
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || "An error occurred.", "error");
      }
    } catch {
      showToast("Network error. Please try again.", "error");
    }
  });

// ── PRICE FORMAT HELPER ──────────────────────────────
function formatCurrency(value) {
  return (
    "ETB" +
    parseFloat(value)
      .toFixed(2)
      .replace(/\B(?=(\d{3})+(?!\d))/g, ",")
  );
}

// ── CATEGORY COLOR MAP ───────────────────────────────
const categoryColors = {
  "Grains & Cereals": "#c8b400",
  Dairy: "#4a90d9",
  "Meat & Poultry": "#e05a3a",
  Vegetables: "#2ecc71",
  Fruits: "#f39c12",
  Bakery: "#c0842b",
  "Canned Goods": "#7f8c8d",
  Beverages: "#9b59b6",
  Baking: "#e8c07a",
  Condiments: "#1abc9c",
  Household: "#3498db",
  "Personal Care": "#e91e8c",
};

document.querySelectorAll(".category-badge").forEach((badge) => {
  const cat = badge.textContent.trim();
  const color = categoryColors[cat];
  if (color) {
    badge.style.background = color + "22";
    badge.style.color = color;
  }
});

// ── ANIMATE STAT NUMBERS ─────────────────────────────
function animateNumber(el, target, prefix = "", suffix = "") {
  const duration = 1200;
  const start = performance.now();
  const isFloat = String(target).includes(".");
  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    const current = isFloat
      ? (ease * target).toFixed(2)
      : Math.round(ease * target);
    el.textContent = prefix + current.toLocaleString() + suffix;
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

document.querySelectorAll("[data-animate]").forEach((el) => {
  const raw = el.dataset.animate;
  const prefix = el.dataset.prefix || "";
  const suffix = el.dataset.suffix || "";
  animateNumber(el, parseFloat(raw), prefix, suffix);
});

// ── SIDEBAR ACTIVE NAV ───────────────────────────────
const currentPage = window.location.pathname.split("/").pop();
document.querySelectorAll(".nav-item[data-page]").forEach((item) => {
  if (item.dataset.page === currentPage) item.classList.add("active");
});

// ── CART LOGIC ──────────────────────────────────────
function getCartElements() {
  return {
    sidebar: document.getElementById("cartSidebar"),
    itemsContainer: document.getElementById("cartItemsContainer"),
    summary: document.getElementById("cartSummary"),
    badge: document.getElementById("cartCountBadge")
  };
}

function toggleCart(open) {
  console.log("toggleCart called:", open);
  const { sidebar } = getCartElements();
  if (open) {
    sidebar?.classList.add("open");
    fetchCart();
  } else {
    sidebar?.classList.remove("open");
  }
}

async function fetchCart() {
  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: new URLSearchParams({ action: "get_cart" }),
    });
    const data = await res.json();
    if (data.success) {
      renderCart(data.items);
    }
  } catch (err) {
    console.error("Cart fetch error:", err);
  }
}

function renderCart(items) {
  const { itemsContainer, summary, badge } = getCartElements();
  
  // Update badge
  const totalItems = items.reduce((acc, item) => acc + (parseInt(item.quantity) || 0), 0);
  if (badge) {
    badge.textContent = totalItems;
    badge.style.display = totalItems > 0 ? "flex" : "none";
  }

  if (!itemsContainer) return;

  if (items.length === 0) {
    itemsContainer.innerHTML = `
      <div class="empty-cart-msg">
        <h3>Your cart is empty</h3>
        <p>Browse our products and add something special.</p>
        <button class="btn btn-outline btn-sm" onclick="toggleCart(false)">Continue Shopping</button>
      </div>
    `;
    if (summary) summary.innerHTML = "";
    return;
  }

  let html = "";
  let subtotal = 0;

  items.forEach((item) => {
    const price = parseFloat(item.price) || 0;
    const qty = parseInt(item.quantity) || 0;
    const itemTotal = price * qty;
    subtotal += itemTotal;
    const initial = item.name.charAt(0).toUpperCase();

    html += `
      <div class="cart-item">
        <div class="cart-item-img">${initial}</div>
        <div class="cart-item-info">
          <h4>${item.name}</h4>
          <p>${item.category} • ${formatCurrency(price)}/${item.unit}</p>
          <div class="cart-item-controls">
            <div class="qty-control">
              <button class="qty-btn" onclick="updateCartQty(${item.id}, ${qty - 1})">-</button>
              <span class="qty-val">${qty}</span>
              <button class="qty-btn" onclick="updateCartQty(${item.id}, ${qty + 1})">+</button>
            </div>
            <a href="javascript:void(0)" class="btn-remove" onclick="removeFromCart(${item.id})">Remove</a>
          </div>
        </div>
      </div>
    `;
  });

  itemsContainer.innerHTML = html;

  // Price Breakdown
  if (summary) {
    const isPickup = document.getElementById("pickupToggle")?.checked || false;
    const tax = subtotal * 0.08; 
    const shipping = (subtotal > 100 || subtotal === 0 || isPickup) ? 0 : 15;
    const total = subtotal + tax + shipping;

    summary.innerHTML = `
      <div class="summary-line">
        <span>Subtotal</span>
        <span>${formatCurrency(subtotal)}</span>
      </div>
      <div class="summary-line">
        <span>Tax (8%)</span>
        <span>${formatCurrency(tax)}</span>
      </div>
      <div class="summary-line">
        <span>Shipping</span>
        <span>${shipping === 0 ? "FREE" : formatCurrency(shipping)}</span>
      </div>
      <div class="summary-line total">
        <span>Total</span>
        <span>${formatCurrency(total)}</span>
      </div>
    `;
  }
}

async function addToCart(inventoryId) {
  console.log("addToCart called for ID:", inventoryId);
  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: new URLSearchParams({
        action: "add_to_cart",
        inventory_id: inventoryId,
        quantity: 1,
      }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, "success");
      fetchCart();
    } else {
      showToast(data.message || "Could not add item.", "error");
    }
  } catch (err) {
    showToast("Network error. Could not add item.", "error");
  }
}

async function updateCartQty(cartId, newQty) {
  if (newQty <= 0) {
    removeFromCart(cartId);
    return;
  }

  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: new URLSearchParams({
        action: "update_cart_qty",
        cart_id: cartId,
        quantity: newQty,
      }),
    });
    const data = await res.json();
    if (data.success) {
      fetchCart();
    } else {
      showToast(data.message, "error");
    }
  } catch (err) {
    showToast("Failed to update cart.", "error");
  }
}

async function removeFromCart(cartId) {
  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: new URLSearchParams({ action: "remove_from_cart", cart_id: cartId }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, "success");
      fetchCart();
    }
  } catch (err) {
    showToast("Failed to remove item.", "error");
  }
}

async function clearCart() {
  if (!confirm("Are you sure you want to clear your entire cart?")) return;
  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: new URLSearchParams({ action: "clear_cart" }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, "success");
      fetchCart();
    }
  } catch (err) {
    showToast("Failed to clear cart.", "error");
  }
}

function proceedToCheckout() {
  showToast("Redirecting to secure checkout...", "success");
}

// Global exposure to ensure inline onclicks always work
window.toggleCart = toggleCart;
window.addToCart = addToCart;
window.updateCartQty = updateCartQty;
window.removeFromCart = removeFromCart;
window.clearCart = clearCart;
window.proceedToCheckout = proceedToCheckout;

// Initial fetch if on dashboard - more robust check
document.addEventListener("DOMContentLoaded", () => {
  const isDashboard = window.location.pathname.includes("dashboard.php") || 
                      document.getElementById("inventoryTableBody") !== null;
  if (isDashboard) {
    fetchCart();
  }
});
