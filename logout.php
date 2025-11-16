<?php
session_start();
session_unset();
session_destroy();
// Delete the "Remember Me" cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/'); // Expire the cookie
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - SOS Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card shadow">
                    <div class="card-body py-5">
                        <div class="text-success mb-3">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                        <h3 class="text-success">Logged Out Successfully</h3>
                        <p class="text-muted">You have been successfully logged out of the system.</p>
                        <a href="login.php" class="btn btn-primary mt-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Again
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>