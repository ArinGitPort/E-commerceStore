<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Item Inventory Table</title>
  <!-- Include your sidebar CSS and the table CSS -->
  <link rel="stylesheet" href="/assets/css/inventory.css" />
</head>
<body>

<div class="pageWrapper">
  <!-- Include the Sidebar -->
  <?php include '../includes/sidebar.php'; ?>

  <!-- Main Content Area -->
  <div class="mainContent">
    <!-- The Inventory Card -->
    <div class="inventory-card">
      <!-- Header row with buttons and filters -->
      <div class="inventory-header">
        <button class="md-btn md-primary">+ ADD ITEM</button>
        <button class="md-btn">IMPORT</button>
        <button class="md-btn">EXPORT</button>

        <div class="filters">
          <select class="md-select">
            <option>All items</option>
            <option>Add-on</option>
            <option>Artaftercoffee (AAC)</option>
            <option>ARTLIYAAAAH (ALI)</option>
          </select>
          <select class="md-select">
            <option>All Items</option>
            <option>In Stock Only</option>
            <option>Out of Stock</option>
          </select>
          <button class="md-icon-btn">
            <img src="search-icon.png" alt="Search" />
          </button>
        </div>
      </div>

      <!-- Data Table -->
      <table class="md-table">
        <thead>
          <tr>
            <th><input type="checkbox" onclick="toggleAll(this)" /></th>
            <th>Item name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Cost</th>
            <th>Margin</th>
            <th>In stock</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><input type="checkbox" /></td>
            <td>3D Charm</td>
            <td>Add-on</td>
            <td>₱50.00</td>
            <td>₱0.00</td>
            <td>100%</td>
            <td>–</td>
          </tr>
          <tr>
            <td><input type="checkbox" /></td>
            <td>AAC-AP</td>
            <td>Artaftercoffee (AAC)</td>
            <td>₱69.00</td>
            <td>₱0.00</td>
            <td>100%</td>
            <td>45</td>
          </tr>
          <tr>
            <td><input type="checkbox" /></td>
            <td>AAC-WP-S</td>
            <td>Artaftercoffee (AAC)</td>
            <td>₱49.00</td>
            <td>₱0.00</td>
            <td>100%</td>
            <td>203</td>
          </tr>
          <!-- Add more rows as needed -->
        </tbody>
      </table>

      <!-- Pagination Row -->
      <div class="pagination-row">
        <div class="pagination-info">
          <span>Page: <strong>1</strong> of 97</span>
        </div>
        <div class="pagination-controls">
          <label for="rowsPerPage">Rows per page:</label>
          <select id="rowsPerPage" class="md-select">
            <option>5</option>
            <option selected>10</option>
            <option>25</option>
            <option>50</option>
          </select>
          <button class="md-icon-btn">&lt;</button>
          <button class="md-icon-btn">&gt;</button>
        </div>
      </div>
    </div> <!-- end .inventory-card -->
  </div> <!-- end .mainContent -->
</div> <!-- end .pageWrapper -->

<script>
  // Check/uncheck all row checkboxes
  function toggleAll(master) {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(chk => chk.checked = master.checked);
  }
</script>

</body>
</html>
