<?php

require_once '../config/db_connection.php'; // ensure this path is correct

function clean_input($data)
{
  return htmlspecialchars(trim($data));
}

$success = false;
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = clean_input($_POST['firstName'] ?? '');
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirmPassword'] ?? '';
  $agreed = isset($_POST['termsAgreement']);

  if (!$firstName || !$email || !$password || !$confirmPassword) {
    $error = "Please fill in all fields.";
  } elseif (!$agreed) {
    $error = "You must agree to the terms.";
  } elseif ($password !== $confirmPassword) {
    $error = "Passwords do not match.";
  } elseif (strlen($password) < 8) {
    $error = "Password must be at least 8 characters.";
  } else {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetchColumn() > 0) {
      $error = "Email is already registered.";
    } else {
      // Get role_id for 'Customer'
      $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'Customer' LIMIT 1");
      $roleStmt->execute();
      $role_id = $roleStmt->fetchColumn();

      if (!$role_id) {
        $error = "Default role 'Customer' not found.";
      } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // Set account as active by default (is_active = 1)
        $is_active = 1;
        $insert = $pdo->prepare("INSERT INTO users (name, email, password, role_id, is_active) VALUES (?, ?, ?, ?, ?)");
        $success = $insert->execute([$firstName, $email, $hashedPassword, $role_id, $is_active]);

        if (!$success) {
          $error = "Something went wrong. Please try again.";
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bunniwinkle - Register</title>
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
          🎉 Registration successful! <a href="../pages/login.php">Click here to login</a>
        </div>
      <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= $error ?></div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form action="" method="POST" id="registerForm" novalidate>
        <label for="firstName">Name</label>
        <input type="text" name="firstName" id="firstName" required placeholder="Enter your name" />

        <label for="email">Email</label>
        <input
          type="email"
          name="email"
          id="email"
          required
          placeholder="Enter your email"
          pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
          title="Please enter a valid email address, e.g. user@example.com" />

        <label for="password">Password</label>
        <input
          type="password"
          name="password"
          id="password"
          required
          placeholder="Create a password"
          minlength="8"
          pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
          title="8+ characters, with uppercase, lowercase, number and special character" />

        <div id="passwordStrengthMsg" class="error-msg">
          Password must be at least 8 characters, include uppercase, lowercase, number & special character.
        </div>

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
    const emailInput = document.getElementById("email");
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");
    const pwStrength = document.getElementById("passwordStrengthMsg");

    // Regex patterns
    const emailRe = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
    const pwRe = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

    // Live password‐strength feedback
    password.addEventListener("input", () => {
      if (!pwRe.test(password.value)) {
        pwStrength.style.display = "block";
      } else {
        pwStrength.style.display = "none";
      }
    });

    form.addEventListener("submit", (event) => {
      // Clear previous custom messages
      emailInput.setCustomValidity("");
      password.setCustomValidity("");
      confirmPassword.setCustomValidity("");

      // Email validation
      if (!emailRe.test(emailInput.value.trim())) {
        emailInput.setCustomValidity("Please enter a valid email (e.g. user@example.com).");
      }

      // Password complexity validation
      if (!pwRe.test(password.value)) {
        password.setCustomValidity(
          "Password must be ≥8 chars, include uppercase, lowercase, number & special character."
        );
        pwStrength.style.display = "block";
      }

      // Confirm password match
      if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
      }

      // If any field is invalid, prevent submission and show messages
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });
  </script>

</body>

</html>