<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inventory - Sidebar</title>

  <!-- Link to external CSS file -->
  <link rel="stylesheet" href="../assets/css/sidebar.css">

  <!-- Font Awesome for Icons -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
    integrity="sha512-..."
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
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
      <div class="dashboardProfile text-center p-3">
        <!-- avatar -->
        <img
          class="userIcon rounded-circle mb-2"
          src="<?= htmlspecialchars($_SESSION['avatar'] ?? '/assets/images/company assets/bunniwinkelanotherlogo.jpg') ?>"
          alt="User Avatar"
          style="width: 200px; height: 200px; object-fit: cover;">

        <!-- name -->
        <div class="fw-bold">
          <?= htmlspecialchars($_SESSION['name'] ?? 'Guest') ?>
        </div>

        <!-- role -->
        <small class="text-secondary">
          <?= htmlspecialchars($_SESSION['role_name'] ?? '') ?>
        </small>
      </div>


      <!-- Navigation List -->
      <ul class="listDashboard">
        <?php
        // Get user role from session
        $userRole = $_SESSION['role_name'] ?? '';

        // Define access permissions for each menu item by role
        $menuAccess = [
          'Dashboard' => ['Super Admin', 'Admin', 'Staff', 'Brand Partners'],
          'Order Management' => ['Super Admin', 'Admin', 'Staff'],
          'Return Process' => ['Super Admin', 'Admin', 'Staff'],
          'Inventory Management' => ['Super Admin', 'Admin', 'Staff', 'Brand Partners'],
          'Account Management' => ['Super Admin', 'Admin', 'Staff'],
          'Membership Management' => ['Super Admin', 'Admin'],
          'Reports' => ['Super Admin', 'Admin', 'Staff', 'Brand Partners'],
          'Audit Logs' => ['Super Admin', 'Admin'],
          'Logout' => ['Super Admin', 'Admin', 'Staff', 'Brand Partners']
        ];

        // Define specific report access by role
        $reportAccess = [
          'Sales Report' => ['Super Admin', 'Admin', 'Staff', 'Brand Partners'],
          'Returns Report' => ['Super Admin', 'Admin', 'Brand Partners'],
          'Inventory Report' => ['Super Admin', 'Admin', 'Brand Partners'],
          'Membership Report' => ['Super Admin', 'Admin', 'Staff'],
        ];

        // Helper function to check access
        function hasAccess($item, $role, $accessArray)
        {
          return in_array($role, $accessArray[$item] ?? []);
        }
        ?>
        <li>
          <div class="listDiv">
            <i class="fas fa-home imgIcon"></i>
            <a href="../pages/dashboard.php" class="menuDashboard">Dashboard</a>
          </div>
        </li>

        <!-- Order Management -->
        <?php if (hasAccess('Order Management', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-shopping-cart imgIcon"></i>
              <a href="../pages/order-management.php" class="menuDashboard">Order Management</a>
            </div>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-shopping-cart imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Order Management</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Return Process -->
        <?php if (hasAccess('Return Process', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-undo imgIcon"></i>
              <a href="../pages/process_returns.php" class="menuDashboard">Return Process</a>
            </div>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-undo imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Return Process</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Inventory Management -->
        <?php if (hasAccess('Inventory Management', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-warehouse imgIcon"></i>
              <a href="/pages/inventory.php" class="menuDashboard">Inventory Management</a>
            </div>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-warehouse imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Inventory Management</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Account Management -->
        <?php if (hasAccess('Account Management', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-user-circle imgIcon"></i>
              <a href="/pages/account-management.php" class="menuDashboard">Account Management</a>
            </div>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-user-circle imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Account Management</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Membership Management -->
        <?php if (hasAccess('Membership Management', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-id-card imgIcon"></i>
              <a href="/pages/subscription-management.php" class="menuDashboard">Membership Management</a>
            </div>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-id-card imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Membership Management</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Reports Dropdown -->
        <?php if (hasAccess('Reports', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv dropdown-toggle" id="auditLogsToggle">
              <i class="fas fa-chart-bar imgIcon"></i>
              <span class="menuDashboard">Reports</span>
            </div>
            <ul class="dropdown-content-vertical" id="dropdownContent">
              <!-- Sales Reports -->
              <?php if (hasAccess('Sales Report', $userRole, $reportAccess)): ?>
                <li><a href="../pages/sales_report.php" class="dropdown-item">
                    <i class="fas fa-chart-line"></i> Sales Report
                  </a></li>
              <?php else: ?>
                <li><span class="dropdown-item text-muted">
                    <i class="fas fa-chart-line"></i> Sales Report
                  </span></li>
              <?php endif; ?>

              <!-- Returned Products -->
              <?php if (hasAccess('Returns Report', $userRole, $reportAccess)): ?>
                <li><a href="/pages/return-analysis-report.php" class="dropdown-item">
                    <i class="fas fa-undo"></i> Returns Report
                  </a></li>
              <?php else: ?>
                <li><span class="dropdown-item text-muted">
                    <i class="fas fa-undo"></i> Returns Report
                  </span></li>
              <?php endif; ?>

              <!-- Inventory Status -->
              <?php if (hasAccess('Inventory Report', $userRole, $reportAccess)): ?>
                <li><a href="/pages/Inventory-status-report.php" class="dropdown-item">
                    <i class="fas fa-boxes"></i> Inventory Report
                  </a></li>
              <?php else: ?>
                <li><span class="dropdown-item text-muted">
                    <i class="fas fa-boxes"></i> Inventory Report
                  </span></li>
              <?php endif; ?>

              <!-- Membership Report -->
              <?php if (hasAccess('Membership Report', $userRole, $reportAccess)): ?>
                <li><a href="/pages/membership-report.php" class="dropdown-item">
                    <i class="fas fa-users"></i> Membership Report
                  </a></li>
              <?php else: ?>
                <li><span class="dropdown-item text-muted">
                    <i class="fas fa-users"></i> Membership Report
                  </span></li>
              <?php endif; ?>

  
            </ul>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-chart-bar imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Reports</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Audit Logs -->
        <?php if (hasAccess('Audit Logs', $userRole, $menuAccess)): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-history imgIcon"></i>
              <a href="/pages/audit_logs.php" class="menuDashboard">Audit Logs</a>
            </div>
          </li>
        <?php else: ?>
          <li>
            <div class="listDiv disabled">
              <i class="fas fa-history imgIcon text-muted"></i>
              <span class="menuDashboard text-muted">Audit Logs</span>
            </div>
          </li>
        <?php endif; ?>

        <!-- Database Backup - Only for Admins -->
        <?php if ($userRole === 'Super Admin' || $userRole === 'Admin'): ?>
          <li>
            <div class="listDiv">
              <i class="fas fa-database imgIcon"></i>
              <a href="/admin/backup_management.php" class="menuDashboard">Database Backup</a>
            </div>
          </li>
        <?php endif; ?>

        <!-- Logout - always accessible -->
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