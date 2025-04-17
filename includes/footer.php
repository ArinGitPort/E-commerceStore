<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Bunniwinkle Footer</title>
  <!-- Bootstrap CSS (optional for layout styling) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for social icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <!-- Custom Footer Styles -->
  <link rel="stylesheet" href="../assets/css/footer.css" />
</head>
<body>

  <!-- Footer Container -->
  <footer id="site-footer" class="footer mt-auto text-white">
    <div class="container text-center text-md-start">
      <div class="row align-items-start">

        <!-- Logo / About -->
        <div class="col-md-4 mb-4 mb-md-0">
          <h4 class="fw-bold">Bunniwinkle</h4>
          <p class="small">Cute, custom, and comforting creations for every cozy soul 💖</p>
        </div>

        <!-- Quick Links -->
        <div class="col-md-4 mb-4 mb-md-0">
          <h5>Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="shop.php" class="footer-link">Shop</a></li>
            <li><a href="../pages-user/about-us.php" class="footer-link">About Us</a></li>
            <li><a href="../pages-user/contact-us.php" class="footer-link">Contact</a></li>
          </ul>
        </div>

        <!-- Social Icons -->
        <div class="col-md-4">
          <h5>Follow Us</h5>
          <div class="d-flex gap-3">
            <a href="https://web.facebook.com/bunniwinkle" class="footer-icon" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.instagram.com/bunniwinkle/" class="footer-icon" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://www.tiktok.com/@bunniwinklestore" class="footer-icon" target="_blank"><i class="fab fa-tiktok"></i></a>
          </div>
        </div>

      </div>

      <hr  />

      <!-- Copyright -->
      <div class="text-center small" style="color: black;">
        &copy; <?php echo date("Y"); ?> Bunniwinkle. All rights reserved.
      </div>
    </div>
  </footer>

  <!-- Bootstrap Bundle JS (optional if already included globally) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
