<link rel="stylesheet" href="/assets/css/sidebar.css">

<div class="pageWrapper">
  <aside class="sidebarContainer">
    <!-- Logo/Profile -->
    <div class="dashboardProfile">
      <img class="userIcon" src="/assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="User Icon">
    </div>

    <!-- Navigation List -->
    <ul class="listDashboard">
      <li>
        <div class="listDiv">
          <img src="/assets/images/home.png" class="imgIcon">
          <a href="/pages/dashboard.php" class="menuDashboard">HOME</a>
        </div>
      </li>

      <li>
        <div class="listDiv">
          <img src="/assets/images/order.png" class="imgIcon">
          <a href="../pages/customer-order.php" class="menuDashboard">ORDERS</a>
        </div>
      </li>

      <li>
        <div class="listDiv">
          <img src="/assets/images/shopping-list.png" class="imgIcon">
          <a href="/pages/inventory.php" class="menuDashboard">PRODUCTS</a>
        </div>
      </li>

      <li>
        <div class="listDiv">
          <img src="/assets/images/trend.png" class="imgIcon">
          <a href="graph.php" class="menuDashboard">GRAPH</a>
        </div>
      </li>

      <!-- Dropdown -->
      <li class="dropdown">
        <div class="listDiv dropdown-toggle" onclick="toggleDropdown()">
          <img src="/assets/images/history.png" class="imgIcon">
          <span class="menuDashboard">HISTORY</span>
          <span class="dropdown-arrow">&#9662;</span>
        </div>
        <ul class="dropdown-content-vertical" id="dropdownContent">
          <li><a href="added_items.php" class="dropdown-item">Added Products</a></li>
          <li><a href="deleted_items.php" class="dropdown-item">Unavailable Products</a></li>
          <li><a href="permdeleted_items.php" class="dropdown-item">Deleted Products</a></li>
          <li><a href="order_history.php" class="dropdown-item">Completed Orders</a></li>
        </ul>
      </li>

      <li>
        <div class="listDiv">
          <img src="/assets/images/logout.png" class="imgIcon">
          <a href="logout.php" class="menuDashboard">LOG-OUT</a>
        </div>
      </li>
    </ul>
  </aside>
</div>

<script>
  function toggleDropdown() {
    const dropdown = document.getElementById("dropdownContent");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
  }
</script>
