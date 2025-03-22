<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home</title>

  <!-- layout.css for overall layout (sidebar + main) -->
  <link rel="stylesheet" href="/assets/css/layout.css">
  <!-- home.css for slideshow or home-specific styles -->
  <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
  <div class="pageWrapper">
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Outer container for the main display area -->
    <div class="mainDisplayContainer">
      <div class="mainDisplay">
        <!-- The white box container -->
        <div class="homeContainer">
          <h2 class="slideshow-title">WELCOME TO BUNNIWINKLE INVENTORY SYSTEM</h2>

          <div class="slideshow-container">
            <div class="mySlides fade">
              <div class="numbertext">1 / 3</div>
              <img src="imgBG/bunniBG.jpg" class="slideshow-image">
            </div>
            <div class="mySlides fade">
              <div class="numbertext">2 / 3</div>
              <img src="imgBG/bunniliveshop.jpg" class="slideshow-image">
            </div>
            <div class="mySlides fade">
              <div class="numbertext">3 / 3</div>
              <img src="imgBG/bunniartscraft.jpg" class="slideshow-image">
            </div>

            <p class="introducePara">
              Welcome to bunniwinkle inventory system app. The goal of this app is 
              to make monitoring of orders and products easier.
            </p>

            <!-- Next/Prev Arrows -->
            <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
            <a class="next" onclick="plusSlides(1)">&#10095;</a>
          </div>

          <!-- Dots Indicators -->
          <div style="text-align:center">
            <span class="dot" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
          </div>
        </div> <!-- end .homeContainer -->
      </div>   <!-- end .mainDisplay -->
    </div>     <!-- end .mainDisplayContainer -->
  </div>       <!-- end .pageWrapper -->

  <script>
    let slideIndex = 0;
    showSlidesAuto();

    function plusSlides(n) {
      showSlides(slideIndex += n);
    }

    function currentSlide(n) {
      showSlides(slideIndex = n - 1);
    }

    function showSlidesAuto() {
      let i;
      let slides = document.getElementsByClassName("mySlides");
      let dots = document.getElementsByClassName("dot");
      for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
      }
      slideIndex++;
      if (slideIndex > slides.length) {
        slideIndex = 1;
      }
      for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
      }
      slides[slideIndex - 1].style.display = "block";
      dots[slideIndex - 1].className += " active";
      setTimeout(showSlidesAuto, 3000);
    }

    function showSlides(n) {
      let i;
      let slides = document.getElementsByClassName("mySlides");
      let dots = document.getElementsByClassName("dot");
      if (n > slides.length) {
        slideIndex = 1;
      }
      if (n < 1) {
        slideIndex = slides.length;
      }
      for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
      }
      for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
      }
      slides[slideIndex - 1].style.display = "block";
      dots[slideIndex - 1].className += " active";
    }
  </script>
</body>
</html>