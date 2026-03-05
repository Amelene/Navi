<?php
session_start();

// Include database config
require_once '../config/database.php';

// If already logged in, redirect to crew dashboard
if (isset($_SESSION['crew_logged_in']) && $_SESSION['crew_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crew_no = $_POST['crew_no'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        // Fetch crew from database - no password required
        $crew = $db->fetchOne(
            "SELECT cm.*, u.id as user_id, u.role,
                    v.vessel_name, d.department_name, p.position_name
             FROM crew_master cm
             LEFT JOIN users u ON cm.auth_user_id = u.id
             LEFT JOIN vessels v ON cm.vessel_id = v.id
             LEFT JOIN departments d ON cm.department_id = d.id
             LEFT JOIN positions p ON cm.position_id = p.id
             WHERE cm.crew_no = ? AND cm.user_status = 'active'",
            [$crew_no]
        );
        
        if ($crew) {
            // Login successful - no password verification needed
            $_SESSION['crew_logged_in'] = true;
            $_SESSION['crew_id'] = $crew['id'];
            $_SESSION['crew_no'] = $crew['crew_no'];
            $_SESSION['crew_name'] = $crew['first_name'] . ' ' . $crew['last_name'];
            $_SESSION['crew_position'] = $crew['position_name'];
            $_SESSION['crew_vessel'] = $crew['vessel_name'];
            $_SESSION['crew_role'] = $crew['role'] ?? 'crew';
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid Crew ID or crew account is inactive';
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Login - Navi Shipping</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Ship Image with Registration -->
        <div class="left-section">
            <div class="overlay"></div>
            <div class="content">
                <div class="registration-prompt">
                    <h2>Don't have an account?</h2>
                    <p>Register to apply</p>
                    <p class="subtitle">Good to see you again! Continue your maritime journey by signing in.</p>
                    <a href="apply.php" class="apply-btn">APPLY</a>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="right-section">
            <!-- Logo -->
            <div class="logo-container">
                <img src="../assets/image/logo.png" alt="NS Logo" class="logo">
            </div>

            <!-- Login Form -->
            <div class="login-form-container">
                <h1 class="login-title">Crew Portal Login</h1>
                <p class="login-subtitle">Enter your Crew ID to access your account</p>

                <?php if ($error): ?>
                    <div class="error-message" style="background: #fee; color: #c33; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fcc;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="login-form">
                    <div class="form-group">
                        <input 
                            type="text" 
                            name="crew_no" 
                            class="form-select" 
                            placeholder="Enter Crew ID (e.g., CRW-2025-001)"
                            required
                            value="<?php echo htmlspecialchars($_POST['crew_no'] ?? ''); ?>"
                            style="height: 50px; padding: 0 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; width: 100%;"
                            autofocus
                        >
                    </div>

                    <button type="submit" class="login-btn">LOG IN</button>

                    <div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 6px; font-size: 13px; color: #126E82;">
                        <strong>Demo Crew ID:</strong><br>
                        CRW-2025-001, CRW-2025-002, CRW-2025-003, or CRW-2025-004
                    </div>

                    <!-- <div style="margin-top: 15px; text-align: center;">
                        <a href="../login.php" style="color: #126E82; text-decoration: none; font-size: 14px;">
                            ← Back to Admin Login
                        </a>
                    </div> -->
                </form>
            </div>
        </div>
    </div>
</body>
</html>
