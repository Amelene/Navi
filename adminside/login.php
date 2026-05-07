<?php
session_start();

// Include database config
require_once '../config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        // Fetch user from database (admin/staff only)
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND user_status = 'active' AND role IN ('admin','staff')",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again.';
        if (DB_DEBUG) {
            $error .= '<br>' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon type=image/png href=../assets/image/logo.png>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Navi Shipping</title>
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-wrapper">
        <!-- Left side - Logo -->
        <div class="logo-section">
            <div class="logo-container">
                <img src="../assets/image/logo.png" alt="Navi Shipping Logo" class="main-logo">
            </div>
        </div>
        
        <!-- Right side - Login Form -->
        <div class="form-section">
            <div class="login-box">
                <h1 class="login-title">Admin Access Only</h1>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php" class="login-form">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your email address"
                            required
                            autocomplete="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="eye-off-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">LOG IN</button>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px; font-size: 13px; color: #856404;">
                        <strong>Demo Credentials:</strong><br>
                        Email: admin@navishipping.com<br>
                        Password: admin123
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }
    </script>
</body>
</html>

