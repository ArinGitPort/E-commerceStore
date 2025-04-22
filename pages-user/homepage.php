<?php
require_once __DIR__ . '/../includes/session-init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Welcome to Bunniwinkle</title>

    <!-- Favicon & CSS -->
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico" />
    <link rel="stylesheet" href="../assets/css/homepage.css" />

    <!-- Bootstrap & Animate.css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<body>

    <!-- ✅ Navigation -->
    <?php include '../includes/user-navbar.php'; ?>

    <!-- ✅ Hero Section -->
    <section class="hero-section container-fluid">
        <div class="row align-items-center px-5" style="min-height: 80vh;">

            <!-- Left Text Content -->
            <div class="col-md-6 hero-text animate__animated animate__fadeInLeft">
                <h1 class="display-4 fw-bold">
                    Experience a <br>
                    <span class="highlight">fresh way</span> to <span class="highlight">shop</span>
                </h1>
                <p class="lead mt-3">
                    Discover handcrafted products that bring warmth and joy to your everyday life.
                </p>
                <a href="../pages-user/user-subscription-application.php" class="shop-btn">Be a member!</a>

            </div>

            <!-- Right Image Content -->
            <div class="col-md-6 text-center animate__animated animate__fadeInRight">
                <img src="../assets/images/company assets/bunniartscraft.jpg" class="img-fluid hero-image" alt="Hero Image">
            </div>

        </div>
    </section>

    <div class="scroll-float-button">
        <button class="btn-scroll-down" onclick="scrollToFooter()">↓ Explore More</button>
    </div>


    <!-- ✅ Features Section -->
    <section class="container feature-section py-5">
        <div class="row text-center">
            <div class="col-md-4">
                <i class="fas fa-truck fa-3x mb-3"></i>
                <h5 class="mt-3">Fast Delivery</h5>
                <p>We ship your favorite items right to your door – fast and secure.</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-gift fa-3x mb-3"></i>
                <h5 class="mt-3">Curated Products</h5>
                <p>Every product is carefully selected for quality and charm.</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-tags fa-3x mb-3"></i>
                <h5 class="mt-3">Exclusive Member Deals</h5>
                <p>Sign up to unlock member-only discounts and offers.</p>
            </div>
        </div>
    </section>


    <!-- ✅ Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scrollToFooter() {
            const footer = document.querySelector('footer');
            if (footer) {
                footer.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }

        // 👇 Auto-hide "Explore More" when footer is visible
        const scrollBtn = document.querySelector('.scroll-float-button');
        const footerObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    scrollBtn.style.display = 'none'; // Hide
                } else {
                    scrollBtn.style.display = 'block'; // Show
                }
            });
        }, {
            threshold: 0.1 // trigger when 10% of the footer is visible
        });

        const footerElement = document.getElementById('site-footer');
        if (footerElement) {
            footerObserver.observe(footerElement);
        }
    </script>
</body>

</html>