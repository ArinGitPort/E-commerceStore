<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Contact Bunniwinkle - Get in Touch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon & CSS -->
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico" />
    <link rel="stylesheet" href="../assets/css/contact-us.css" />
    <link rel="stylesheet" href="../assets/css/shared-styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <!-- Bootstrap & Animate.css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <!-- ✅ Navigation -->
    <?php include '../includes/user-navbar.php'; ?>

    <!-- ✅ Hero Banner -->
    <section class="contact-hero container-fluid d-flex align-items-center">
        <div class="container text-center text-white">
            <h1 class="display-3 fw-bold animate__animated animate__fadeInDown">Let's Connect</h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s" style="color: white;">
                We're here to help and answer any questions
            </p>
        </div>
    </section>

    <!-- ✅ Form & Map Section -->
    <section class="container py-5">
        <div class="row g-5 h-100">
            <!-- Contact Form -->
            <div class="col-lg-6 animate__animated animate__fadeInLeft">
                <div class="contact-card p-4 p-md-5 rounded shadow h-100">
                    <h2 class="fw-bold mb-4">Send Us a Message</h2>
                    <form id="contactForm">
                        <div class="mb-4">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control form-control-lg" id="name" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control form-control-lg" id="email" required>
                        </div>
                        <div class="mb-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control form-control-lg" id="subject" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control form-control-lg" id="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Send Message <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Map -->
            <div class="col-lg-6 h-100 animate__animated animate__fadeInRight">
                <div class="map-container h-100 rounded shadow-lg">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m23!1m12!1m3!1d1352.6941709316757!2d120.86598499510596!3d14.898062495972969!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m8!3e6!4m0!4m5!1s0x339655492a11ccc5%3A0x4238c3eadc27d91b!2sLongos%20pulilan%20bulacan%2C%2078%20Do%C3%B1a%20Remedios%20Trinidad%20Hwy%2C%20Pulilan%2C%20Bulacan!3m2!1d14.892594599999999!2d120.86630849999999!5e1!3m2!1sen!2sph!4v1744985658222!5m2!1sen!2sph"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </section>



    <!-- ✅ Contact Info Section -->
    <section class="contact-info-section bg-light py-5 animate__animated animate__fadeInUp">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Contact Information</h2>
            <div class="row g-4">
                <!-- Address -->
                <div class="col-md-4">
                    <div class="contact-item text-center p-4 bg-white rounded shadow-sm">
                        <div class="icon-box bg-primary text-white rounded-circle mx-auto mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Our Studio</h5>
                        <p>M.E. Mateo Commercial Building DRT Highway, Longos, Pulilan, Bulacan, Philippines
                            <br>Francisco Building Blas Ople Diversion Rd, Malolos, Bulacan (Beside Sea of Smiles)
                        </p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="col-md-4">
                    <div class="contact-item text-center p-4 bg-white rounded shadow-sm">
                        <div class="icon-box bg-primary text-white rounded-circle mx-auto mb-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Call Us</h5>
                        <p>+63 947 206 3801
                            <br>Mon-Fri: 9am - 5pm PST
                        </p>
                    </div>
                </div>

                <!-- Email -->
                <div class="col-md-4">
                    <div class="contact-item text-center p-4 bg-white rounded shadow-sm">
                        <div class="icon-box bg-primary text-white rounded-circle mx-auto mb-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email Us</h5>
                        <p>bunniwinkle@gmail.com</p>
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            <div class="social-links text-center mt-5">
                <h5 class="mb-3">Follow Us</h5>
                <a href="https://web.facebook.com/bunniwinkle" class="btn btn-outline-primary btn-lg me-2" target="_blank">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="https://www.instagram.com/bunniwinkle/" class="btn btn-outline-primary btn-lg me-2" target="_blank">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.tiktok.com/@bunniwinklestore" class="btn btn-outline-primary btn-lg me-2" target="_blank">
                    <i class="fab fa-tiktok"></i>
                </a>
            </div>
        </div>
    </section>



    <!-- ✅ Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation trigger on scroll
            const animateElements = document.querySelectorAll('.animate__animated');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add(entry.target.dataset.animate || 'animate__fadeIn');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            animateElements.forEach(element => observer.observe(element));

            // Form submission handling
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Collect form data
                    const formData = {
                        name: document.getElementById('name').value.trim(),
                        email: document.getElementById('email').value.trim(),
                        subject: document.getElementById('subject').value.trim(),
                        message: document.getElementById('message').value.trim()
                    };

                    // Validate form data
                    if (!formData.name || !formData.email || !formData.subject || !formData.message) {
                        alert('Please fill out all fields.');
                        return;
                    }

                    // Simulate form submission
                    alert('Thank you for your message! We\'ll get back to you soon.');
                    contactForm.reset();
                });
            }
        });
    </script>
</body>

</html>