<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Auto-login if cookie exists
if (isset($_COOKIE['remember_user'])) {
    $username = $_COOKIE['remember_user'];

    $sql = "SELECT id_pk, username, name, rank, link FROM login WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        $_SESSION['user_id']   = $user['id_pk'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['rank']      = $user['rank'];
        $_SESSION['link']      = $user['link'];
        $_SESSION['loggedin']  = true;

        header("Location: dashboard.php");
        exit();
    }
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['erp_id']);
    $password = md5($_POST['password']);
    $remember = isset($_POST['remember_me']);

    $sql = "SELECT id_pk, username, name, rank, link FROM login WHERE username = ? AND password = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result(); 

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        $_SESSION['user_id']   = $user['id_pk'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['rank']      = $user['rank'];
        $_SESSION['link']      = $user['link'];
        $_SESSION['loggedin']  = true;

        // Set "Remember Me" cookie
        if ($remember) {
            setcookie("remember_user", $user['username'], time() + (86400 * 30), "/", "", false, true);
        }

        // Update last login
        $update = $conn->prepare("UPDATE login SET last_login = NOW() WHERE id_pk = ?");
        $update->bind_param("i", $user['id_pk']);
        $update->execute();
        $update->close();

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOS Energy - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="login-container">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="text-white fw-bold">SOS Energy</h2>
                            <p class="text-white-50">Dashboard Login</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
                            <div class="mb-3">
                                <label for="erp_id" class="form-label text-white">Username</label>
                                <input type="text" class="form-control form-control-lg" id="erp_id" name="erp_id"
                                    value="<?php echo isset($_COOKIE['remember_user']) ? htmlspecialchars($_COOKIE['remember_user']) : ''; ?>"
                                    required placeholder="Enter your username">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label text-white">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password"
                                    required placeholder="Enter your password">
                                <div class="form-text text-light" id="passwordHelp"></div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me"
                                    <?php echo isset($_COOKIE['remember_user']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-white" for="remember_me">
                                    Remember me
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                    <span id="login-text">Sign In</span>
                                    <div id="loading-spinner" class="spinner-border spinner-border-sm d-none" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <small class="text-white-50">Test User: marjantest / Marjan@123</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordHelp = document.getElementById('passwordHelp');
            const btn = document.getElementById('loginBtn');
            const txt = document.getElementById('login-text');
            const spin = document.getElementById('loading-spinner');

            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSymbol = /[@$!%*?&#]/.test(password);
            const isLongEnough = password.length >= 8;

            if (!isLongEnough || !hasUpper || !hasLower || !hasNumber || !hasSymbol) {
                e.preventDefault();
                passwordHelp.innerHTML = "Password must be at least 8 characters long and include uppercase, lowercase, number, and symbol.";
                passwordHelp.classList.add('text-warning');
                return;
            }

            txt.textContent = 'Signing In...';
            spin.classList.remove('d-none');
            btn.disabled = true;
        });
    </script>
</body>
</html>
