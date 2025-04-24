<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Bunniwinkle - Our Story</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon & CSS -->
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico" />
    <link rel="stylesheet" href="../assets/css/about-us.css" />
    <link rel="stylesheet" href="../assets/css/shared-styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <!-- Bootstrap & Animate.css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>

<body>

    <!-- ✅ Navigation -->
    <?php include '../includes/user-navbar.php'; ?>

    <!-- ✅ Hero Banner -->
    <section class="about-hero container-fluid d-flex align-items-center">
        <div class="container text-center text-white">
            <h1 class="display-3 fw-bold animate__animated animate__fadeInDown">Our Story</h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s" style="color: white;">
                Crafting happiness, one cute product at a time
            </p>
        </div>
    </section>

    <!-- ✅ Our Story Section -->
    <section class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 animate__animated animate__fadeInLeft mb-4 mb-lg-0">
                <img src="../assets/images/company assets/vintagephoto.png"
                     class="img-fluid rounded shadow our-story-img"
                     alt="Our Beginnings">
            </div>
            <div class="col-lg-6 animate__animated animate__fadeInRight" style="margin-top: 2rem;">
                <h2 class="fw-bold mb-4">From Small Beginnings</h2>
                <p class="lead">
                    Bunniwinkle started in 2018 as a passion project in a tiny home studio.
                </p>
                <p>
                    What began as handmade gifts for friends quickly grew into a community of customers who loved our
                    unique, heartwarming designs. Today, we're a small but dedicated team committed to spreading joy
                    through our carefully crafted products.
                </p>
                <div class="signature mt-4">
                    <p class="mb-0">With love,</p>
                    <img src="../assets/images/company assets/signature.png"
                         alt="Founder's Signature"
                         style="height: 50px;">
                </div>
            </div>
        </div>
    </section>

    <!-- ✅ Mission Section -->
    <section class="mission-section py-5">
        <div class="container text-center">
            <div class="mission-card p-4 p-md-5 rounded shadow animate__animated animate__zoomIn">
                <h2 class="fw-bold mb-3">Vision</h2>
                <p class="mission-statement lead">
                    "To create a heartwarming and enchanting haven for creativity, our cute artist shared space store
                    envisions a world where the charm of adorable artistry brings joy to every corner. We aspire to be
                    a vibrant community that celebrates the magic of cuteness, fostering collaboration among artists
                    and enchanting our customers with a delightful and curated collection of whimsical creations."
                </p>
            </div>
        </div>
    </section>

    <!-- ✅ Team Section -->
    <section class="container py-5">
        <h2 class="text-center fw-bold mb-5">Meet The Team</h2>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4 text-center team-member animate__animated animate__fadeInUp mb-5 mb-lg-0">
                <img src="../assets/images/team/team-1.jpg" class="rounded-circle mb-3" alt="Team Member">
                <h4>Janine Geronimo</h4>
                <p class="text-muted">Founder & Creative Director</p>
                <p>"I believe in the power of small joys to brighten our days."</p>
            </div>
            <div class="col-md-6 col-lg-4 text-center team-member animate__animated animate__fadeInUp animate__delay-1s mb-5 mb-lg-0">
                <img src="../assets/images/team/team-2.jpg" class="rounded-circle mb-3" alt="Team Member">
                <h4>Grayserr Geronimo</h4>
                <p class="text-muted">Production Manager</p>
                <p>"Every stitch and detail matters in creating something special."</p>
            </div>
            <div class="col-md-6 col-lg-4 text-center team-member animate__animated animate__fadeInUp animate__delay-2s">
                <img src="../assets/images/team/team-3.jpg" class="rounded-circle mb-3" alt="Team Member">
                <h4>Paulo</h4>
                <p class="text-muted">Customer Happiness</p>
                <p>"Your smile is why we do what we do!"</p>
            </div>
        </div>
    </section>

    <!-- ✅ Values Section -->
    <section class="values-section py-5">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Our Core Values</h2>
            <div class="row justify-content-center">
                <div class="col-sm-6 col-md-4 col-lg-3 value-card text-center p-4 animate__animated animate__fadeIn">
                    <div class="value-icon mb-3">❤️</div>
                    <h5>Heartfelt Craft</h5>
                    <p>Every product made with care and attention</p>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 value-card text-center p-4 animate__animated animate__fadeIn animate__delay-1s">
                    <div class="value-icon mb-3">🌱</div>
                    <h5>Sustainability</h5>
                    <p>Eco-friendly materials and processes</p>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 value-card text-center p-4 animate__animated animate__fadeIn animate__delay-2s">
                    <div class="value-icon mb-3">🤝</div>
                    <h5>Community</h5>
                    <p>Building connections through our products</p>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 value-card text-center p-4 animate__animated animate__fadeIn animate__delay-3s">
                    <div class="value-icon mb-3">✨</div>
                    <h5>Joyful Design</h5>
                    <p>Creating pieces that spark happiness</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ✅ CTA Section -->
    <section class="container py-5 text-center">
        <div class="cta-box p-4 p-md-5 rounded shadow animate__animated animate__pulse">
            <h3 class="fw-bold mb-3">Join Our Journey</h3>
            <p class="lead mb-4">Discover what makes Bunniwinkle special</p>
            <div class="d-flex flex-column flex-md-row justify-content-center">
                <a href="../pages-user/shop.php" class="btn btn-primary btn-lg me-md-3 mb-3 mb-md-0">Shop Now</a>
                <a href="../pages-user/contact-us.php" class="btn btn-outline-primary btn-lg">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- ✅ Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation trigger on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.animate__animated');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add(entry.target.dataset.animate);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            animateElements.forEach(element => {
                observer.observe(element);
            });
        });
    </script>
</body>
</html>
