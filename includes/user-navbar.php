<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bunniwinkle Navigation</title>
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>
    <nav class="navbar">
        <img src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Bunniwinkle Logo" class="nav-logo">
        
        <div class="mobile-menu-toggle" id="mobileMenuToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <ul class="nav-menu" id="navMenu">
            <li class="nav-item">
                <a href="#" class="nav-link">Shop</a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">About Us</a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">Contact Us</a>
            </li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link">Others<span class="dropdown-icon">▼</span></a>
                <ul class="dropdown-menu">
                    <li class="dropdown-item"><a href="#">FAQ</a></li>
                    <li class="dropdown-item"><a href="#">Blog</a></li>
                    <li class="dropdown-item"><a href="#">Resources</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <script>
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('navMenu').classList.toggle('active');
        });
    </script>
</body>
</html>