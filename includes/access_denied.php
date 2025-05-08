<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            text-align: center;
            background-color: #fff;
            border-radius: 5px;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        p {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Access Denied</h1>
        <p>Sorry, you don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
        <a href="index.php" class="btn">Return to Home</a>
    </div>
</body>
</html>
