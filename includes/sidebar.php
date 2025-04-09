<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inventory - Sidebar</title>
  
  <!-- Link to external CSS file -->
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  
  <!-- Font Awesome for Icons -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
    integrity="sha512-..."
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
</head>
<body>
  <div class="pageWrapper">
    <!-- SIDEBAR -->
    <aside class="sidebarContainer" id="sidebar">
      <!-- Sidebar Header (holds the toggle button) -->
      <div class="sidebarHeader">
        <button class="collapse-toggle" id="sidebarToggle">
          <span class="toggle-icon"><i class="fas fa-bars"></i></span>
        </button>
      </div>
      
      <!-- Logo/Profile -->
      <div class="dashboardProfile">
        <img 
          class="userIcon" 
          src="/assets/images/company assets/bunniwinkelanotherlogo.jpg" 
          alt="User Icon"
        >
      </div>
      
      <!-- Navigation List -->
      <ul class="listDashboard">
        <li>
          <div class="listDiv">
            <i class="fas fa-home imgIcon"></i>
            <a href="/pages/dashboard.php" class="menuDashboard">Dashboard</a>
          </div>
        </li>
        <li>
          <div class="listDiv">
            <i class="fas fa-shopping-cart imgIcon"></i>
            <a href="../pages/order-management.php" class="menuDashboard">Order Management</a>
          </div>
        </li>
        <li>
          <div class="listDiv">
            <i class="fas fa-warehouse imgIcon"></i>
            <a href="/pages/inventory.php" class="menuDashboard">Inventory Management</a>
          </div>
        </li>
        <li>
          <div class="listDiv">
            <i class="fas fa-user-circle imgIcon"></i>
            <a href="/pages/account-management.php" class="menuDashboard">Account Management</a>
          </div>
        </li>
        <li>
          <div class="listDiv">
            <i class="fas fa-chart-line imgIcon"></i>
            <a href="graph.php" class="menuDashboard">Sales Graph</a>
          </div>
        </li>
        <!-- Dropdown for Audit Logs -->
        <li>
          <div class="listDiv dropdown-toggle" id="auditLogsToggle">
            <i class="fas fa-history imgIcon"></i>
            <span class="menuDashboard">Audit Logs</span>
          </div>
          <ul class="dropdown-content-vertical" id="dropdownContent">
            <li><a href="added_items.php" class="dropdown-item">Added Products</a></li>
            <li><a href="deleted_items.php" class="dropdown-item">Unavailable Products</a></li>
            <li><a href="permdeleted_items.php" class="dropdown-item">Deleted Products</a></li>
            <li><a href="order_history.php" class="dropdown-item">Completed Orders</a></li>
          </ul>
        </li>
        <!-- Logout -->
        <li>
          <div class="listDiv">
            <i class="fas fa-sign-out-alt imgIcon"></i>
            <a href="../pages/logout.php" class="menuDashboard">Logout</a>
          </div>
        </li>
      </ul>
    </aside>


  <!-- JS for Sidebar & Dropdown -->
  <script>
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const toggleIcon = document.querySelector('.toggle-icon');
    const auditLogsToggle = document.getElementById('auditLogsToggle');
    const dropdownContent = document.getElementById('dropdownContent');

    // Handle sidebar toggling
    function toggleSidebar() {
      // On mobile: open/close off-canvas
      // On desktop: collapse/expand width
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');

      // Change the icon
      if (sidebar.classList.contains('collapsed')) {
        toggleIcon.innerHTML = '<i class="fas fa-bars"></i>';
      } else {
        toggleIcon.innerHTML = '<i class="fas fa-times"></i>';
      }

      // Persist in localStorage
      localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }

    // On small screens, we also want the sidebar to slide in/out
    // We'll handle that by toggling a specific "open" class
    sidebarToggle.addEventListener('click', () => {
      // If on desktop size => collapse
      // If on mobile => overlay
      sidebar.classList.toggle('open');
      toggleSidebar();
    });

    // Toggle the dropdown menu for Audit Logs
    auditLogsToggle.addEventListener('click', () => {
      dropdownContent.classList.toggle('open');
    });

    // On page load, restore sidebar state
    document.addEventListener('DOMContentLoaded', function() {
      const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      if (isCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
        toggleIcon.innerHTML = '<i class="fas fa-bars"></i>';
      }
    });
  </script>
</body>
</html>
