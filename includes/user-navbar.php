<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bunniwinkle Navigation</title>
  <link rel="stylesheet" href="../assets/css/navbar.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">

    <!-- Logo -->
    <a href="../pages-user/homepage.php">
      <img src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Bunniwinkle Logo" class="nav-logo" />
    </a>

    <!-- Hamburger (mobile) -->
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <!-- Navigation Links -->
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item"><a href="../pages-user/shop.php" class="nav-link">Shop</a></li>
      <li class="nav-item"><a href="#" class="nav-link">About Us</a></li>
      <li class="nav-item"><a href="#" class="nav-link">Contact Us</a></li>
      <li class="nav-item dropdown">
        <a href="#" class="nav-link">Others <span class="dropdown-icon">▼</span></a>
        <ul class="dropdown-menu">
          <li class="dropdown-item"><a href="#">FAQ</a></li>
          <li class="dropdown-item"><a href="#">Blog</a></li>
          <li class="dropdown-item"><a href="#">Resources</a></li>
        </ul>
      </li>
    </ul>

    <!-- Icon Section -->
    <div class="nav-icons">
      <a href="#" class="icon"><i class="fas fa-search"></i></a>
      <a href="#" class="icon"><i class="fas fa-user"></i></a>
      <a href="#" class="icon"><i class="fas fa-shopping-bag"></i></a>
    </div>

  </div>
</nav>

<script>
  document.getElementById('mobileMenuToggle').addEventListener('click', function () {
    this.classList.toggle('active');
    document.getElementById('navMenu').classList.toggle('active');
  });
</script>

</body>
</html>
