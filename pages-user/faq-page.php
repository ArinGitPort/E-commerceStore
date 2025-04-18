<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQs - Bunniwinkle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon & CSS -->
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
    <link rel="stylesheet" href="../assets/css/faq-page.css">
    <link rel="stylesheet" href="../assets/css/shared-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Navigation -->
    <?php include '../includes/user-navbar.php'; ?>

    <!-- Hero Banner -->
    <section class="faq-hero container-fluid d-flex align-items-center">
        <div class="container text-center text-white">
            <h1 class="display-3 fw-bold">Frequently Asked Questions</h1>
            <p class="lead mt-3" style="color: white;">Find answers to common questions about our products and services</p>
        </div>
    </section>

    <!-- Main FAQ Content -->
    <main class="container my-5">
        <!-- Search Section -->
        <section class="search-section mb-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="input-group">
                        <input type="text" id="faq-search" class="form-control form-control-lg" placeholder="Search FAQs...">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Categories -->
        <section class="faq-categories mb-5">
            <h2 class="text-center mb-4 fw-bold">Browse by Category</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="#shipping" class="category-card d-block p-4 text-center rounded shadow-sm h-100">
                        <div class="icon-wrapper mb-3">
                            <i class="fas fa-truck fa-3x text-primary"></i>
                        </div>
                        <h3>Shipping</h3>
                        <p class="mb-0">Delivery times and policies</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="#orders" class="category-card d-block p-4 text-center rounded shadow-sm h-100">
                        <div class="icon-wrapper mb-3">
                            <i class="fas fa-shopping-bag fa-3x text-primary"></i>
                        </div>
                        <h3>Orders</h3>
                        <p class="mb-0">Payment and order management</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="#products" class="category-card d-block p-4 text-center rounded shadow-sm h-100">
                        <div class="icon-wrapper mb-3">
                            <i class="fas fa-palette fa-3x text-primary"></i>
                        </div>
                        <h3>Products</h3>
                        <p class="mb-0">Materials and customization</p>
                    </a>
                </div>
            </div>
        </section>

        <!-- FAQ Accordion -->
        <section class="faq-accordion">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Shipping Section -->
                    <h2 id="shipping" class="category-title mb-4 fw-bold">Shipping Questions</h2>
                    <div class="accordion mb-5" id="shippingAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#shippingOne">
                                    How long does shipping take?
                                </button>
                            </h3>
                            <div id="shippingOne" class="accordion-collapse collapse show" data-bs-parent="#shippingAccordion">
                                <div class="accordion-body">
                                    <p>We typically process orders within 1-2 business days. Domestic shipping takes 3-5 business days, while international shipping varies between 7-14 business days. During peak seasons, please allow an additional 1-2 days for processing.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#shippingTwo">
                                    What shipping carriers do you use?
                                </button>
                            </h3>
                            <div id="shippingTwo" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion">
                                <div class="accordion-body">
                                    <p>We partner with several reliable carriers including USPS, FedEx, and DHL for international shipments. The carrier used will depend on your location and the shipping method selected at checkout.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#shippingThree">
                                    Do you offer expedited shipping?
                                </button>
                            </h3>
                            <div id="shippingThree" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion">
                                <div class="accordion-body">
                                    <p>Yes, we offer expedited shipping options at checkout. Express shipping typically delivers within 2-3 business days domestically and 3-5 business days internationally after processing.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Section -->
                    <h2 id="orders" class="category-title mb-4 fw-bold">Order Questions</h2>
                    <div class="accordion mb-5" id="ordersAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#ordersOne">
                                    What payment methods do you accept?
                                </button>
                            </h3>
                            <div id="ordersOne" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body">
                                    <p>We accept all major credit cards (Visa, Mastercard, American Express, Discover), PayPal, and PayMongo. All transactions are securely processed through our encrypted payment gateway.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#ordersTwo">
                                    Can I modify or cancel my order?
                                </button>
                            </h3>
                            <div id="ordersTwo" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body">
                                    <p>You may modify or cancel your order within 1 hour of placement by contacting our customer service team. After this window, most orders enter our processing system and cannot be changed.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#ordersThree">
                                    Do you offer bulk or wholesale pricing?
                                </button>
                            </h3>
                            <div id="ordersThree" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body">
                                    <p>No, we currently don't offer bulk orders.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <h2 id="products" class="category-title mb-4 fw-bold">Product Questions</h2>
                    <div class="accordion" id="productsAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#productsOne">
                                    What materials are your products made from?
                                </button>
                            </h3>
                            <div id="productsOne" class="accordion-collapse collapse" data-bs-parent="#productsAccordion">
                                <div class="accordion-body">
                                    <p>Our products are crafted from premium, eco-friendly materials including organic clays, and non-toxic paints.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#productsTwo">
                                    Do you offer customization options?
                                </button>
                            </h3>
                            <div id="productsTwo" class="accordion-collapse collapse" data-bs-parent="#productsAccordion">
                                <div class="accordion-body">
                                    <p>Many of our products are pre-made and cannot be customized. However, each crafted product is unique.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#productsThree">
                                    What is your return policy?
                                </button>
                            </h3>
                            <div id="productsThree" class="accordion-collapse collapse" data-bs-parent="#productsAccordion">
                                <div class="accordion-body">
                                    <p>We accept returns within 7 days of delivery for damaged items. Please initiate returns through your My Orders Page.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact CTA -->
        <section class="contact-cta text-center py-5 my-5 bg-light rounded">
            <h2 class="mb-4 fw-bold">Still have questions?</h2>
            <p class="lead mb-4">Our customer service team is happy to help</p>
            <a href="../pages-user/contact-us.php" class="btn btn-primary btn-lg px-4">
                Contact Us <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </section>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Smooth Scroll -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>

    <!-- FAQ Search Filter -->
    <script>
        document.getElementById('faq-search').addEventListener('input', function () {
            const keyword = this.value.toLowerCase().trim();
            const faqItems = document.querySelectorAll('.accordion-item');

            faqItems.forEach(item => {
                const content = item.innerText.toLowerCase();
                if (content.includes(keyword)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
