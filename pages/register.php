<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Register - Bunniwinkle</title>
    <link rel="stylesheet" href="../assets/css/register.css" />
    <link rel="icon" href="assets/images/iconlogo/bunniwinkleIcon.ico" />
  </head>
  <body>
    <div class="login-wrapper">
      <div class="login-card">
        <div class="logo-container">
          <img
            class="logo-image"
            src="../assets/images/company assets/bunniwinkelanotherlogo.jpg"
            alt="Logo"
          />
        </div>

        <form action="register_process.php" method="POST" id="registerForm">
          <label for="firstName">First Name</label>
          <input
            type="text"
            name="firstName"
            id="firstName"
            required
            placeholder="Enter your first name"
          />

          <label for="lastName">Last Name</label>
          <input
            type="text"
            name="lastName"
            id="lastName"
            required
            placeholder="Enter your last name"
          />

          <label for="email">Email</label>
          <input
            type="email"
            name="email"
            id="email"
            required
            placeholder="Enter your email"
          />

          <label for="password">Password</label>
          <input
            type="password"
            name="password"
            id="password"
            required
            placeholder="Create a password"
            minlength="8"
          />

          <label for="confirmPassword">Confirm Password</label>
          <input
            type="password"
            name="confirmPassword"
            id="confirmPassword"
            required
            placeholder="Confirm your password"
            minlength="8"
          />

          <div class="options-row">
            <div class="checkbox-wrapper">
              <input
                type="checkbox"
                id="showPassword"
                onclick="togglePassword()"
              />
              <label for="showPassword">Show Password</label>
            </div>

            <div class="checkbox-wrapper">
              <input
                type="checkbox"
                id="termsAgreement"
                name="termsAgreement"
                required
              />
              <label for="termsAgreement">I agree to the <a href="terms.php" target="_blank">Terms</a></label>
            </div>
          </div>

          <button type="submit" class="login-btn">Register</button>

          <div class="register-link">
            <p style="margin-right: 4px">
              Already have an account? <a href="../pages/login.php">Login here</a>
            </p>
          </div>
        </form>
      </div>
    </div>

    <script>
      function togglePassword() {
        const passwordField = document.getElementById("password");
        const confirmPasswordField = document.getElementById("confirmPassword");
        const newType = passwordField.type === "password" ? "text" : "password";
        
        passwordField.type = newType;
        confirmPasswordField.type = newType;
      }

      // Password validation
      document.getElementById('registerForm').addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
          alert('Passwords do not match!');
          event.preventDefault();
        }
        
        // You can add more validation here (like password strength)
      });
    </script>
  </body>
</html>