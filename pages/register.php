<!-- PLEASE DONT FORGET TO ADD PHP MAILER -->


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register | Bunniwinkle</title>
  <link rel="stylesheet" href="../assets/css/register.css" />
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="logo-container">
        <img class="logo-image" src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Logo" />
      </div>

      <!-- PHP Feedback -->
      <?php if ($success): ?>
        <div class="alert alert-success" role="alert" id="successAlert">
          🎉 Registered successfully! Please check your email to activate your account.
        </div>
      <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= $error ?></div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form action="" method="POST" id="registerForm" novalidate>
        <label for="firstName">Name</label>
        <input type="text" name="firstName" id="firstName" required placeholder="Enter your name" />

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required placeholder="Enter your email" />

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required placeholder="Create a password" minlength="8" />
        <div id="passwordStrengthMsg" class="error-msg">Password must be at least 8 characters.</div>

        <label for="confirmPassword">Confirm Password</label>
        <input type="password" name="confirmPassword" id="confirmPassword" required placeholder="Confirm your password" minlength="8" />

        <div class="options-row">
          <div class="checkbox-wrapper">
            <input type="checkbox" id="showPassword" onclick="togglePassword()" />
            <label for="showPassword">Show Password</label>
          </div>

          <div class="checkbox-wrapper">
            <input type="checkbox" id="termsAgreement" name="termsAgreement" required />
            <label for="termsAgreement">I agree to the <a href="terms.php" target="_blank">Terms</a></label>
          </div>
        </div>

        <button type="submit" class="login-btn">Register</button>

        <div class="register-link">
          <p>Already have an account? <a href="../pages/login.php">Login here</a></p>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePassword() {
      const pw = document.getElementById("password");
      const cpw = document.getElementById("confirmPassword");
      const type = pw.type === "password" ? "text" : "password";
      pw.type = type;
      cpw.type = type;
    }

    const form = document.getElementById("registerForm");
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");
    const pwStrength = document.getElementById("passwordStrengthMsg");

    password.addEventListener("input", () => {
      pwStrength.style.display = password.value.length >= 8 ? "none" : "block";
    });

    form.addEventListener("submit", (event) => {
      confirmPassword.setCustomValidity("");
      if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
      }
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });
  </script>
</body>
</html>