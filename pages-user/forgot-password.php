
<!-- PLEASE DONT FORGET TO ADD PHP MAILER -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Bunniwinkle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/forgot-password.css">

</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-container">
                <img class="logo-image" src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Logo">
            </div>

            <!-- Flash Messages -->
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Reset Request Form -->
            <form action="forgot-password.php" method="POST">
                <h2>Forgot Password</h2>
                <p>Enter your email to receive a reset token.</p>

                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Your email" class="form-control mb-3">

                <button type="submit" class="login-btn">Generate Token</button>

                <div class="text-center mt-3">
                    <a href="../pages/login.php">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>