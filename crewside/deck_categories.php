<?php
session_start();

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get department from URL
$department = $_GET['dept'] ?? 'DECK';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon type=image/png href=../assets/image/logo.png>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($department); ?> Categories - Navi Shipping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="deck_categories.css?v=<?php echo time(); ?>">
</head>
<body class="<?php echo (strtoupper($department) === 'STEWARD') ? 'steward-page' : ''; ?>">
    <!-- Header -->
    <header class="header">
        <link rel="icon type=image/png href=../assets/image/logo.png>
<div class="header-content">
            <div class="logo-section">
                <img src="../assets/image/logo.png" alt="NS Logo" class="logo">
            </div>
            
            <nav class="nav-tabs">
                <a href="#" class="nav-tab active">NSC Examination</a>
                <a href="abstract_entry.php" class="nav-tab">Abstract</a>
            </nav>
            
            <div class="user-section">
                <div class="user-icon">
                    <i class="fas fa-user"></i>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Category Cards -->
            <div class="category-grid">
                <?php if (strtoupper($department) === 'STEWARD'): ?>
                    <!-- COOK Card -->
                    <div class="category-card steward-card" onclick="window.location.href='crew_information.php?dept=<?php echo urlencode($department); ?>&category=COOK'">
                        <img src="../assets/image/deck.jpg" alt="Cook" class="card-image">
                        <div class="card-content">
                            <h2 class="card-title">COOK</h2>
                            <p class="card-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                                sed do eiusmod tempor incididunt ut labore et dolore 
                                magna aliqua.
                            </p>
                            <div class="card-footer">
                                <span class="duration">
                                    <i class="far fa-clock"></i> 30 Minutes
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- MESSMAN Card -->
                    <div class="category-card steward-card" onclick="window.location.href='crew_information.php?dept=<?php echo urlencode($department); ?>&category=MESSMAN'">
                        <img src="../assets/image/deck.jpg" alt="Messman" class="card-image">
                        <div class="card-content">
                            <h2 class="card-title">MESSMAN</h2>
                            <p class="card-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                                sed do eiusmod tempor incididunt ut labore et dolore 
                                magna aliqua.
                            </p>
                            <div class="card-footer">
                                <span class="duration">
                                    <i class="far fa-clock"></i> 30 Minutes
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- MANAGEMENT Card -->
                    <div class="category-card" onclick="window.location.href='crew_information.php?dept=<?php echo urlencode($department); ?>&category=MANAGEMENT'">
                        <img src="../assets/image/deck.jpg" alt="Management" class="card-image">
                        <div class="card-content">
                            <h2 class="card-title">MANAGEMENT</h2>
                            <p class="card-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                                sed do eiusmod tempor incididunt ut labore et dolore 
                                magna aliqua.
                            </p>
                            <div class="card-footer">
                                <span class="duration">
                                    <i class="far fa-clock"></i> 30 Minutes
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- OPERATIONAL Card -->
                    <div class="category-card" onclick="window.location.href='crew_information.php?dept=<?php echo urlencode($department); ?>&category=OPERATIONAL'">
                        <img src="../assets/image/deck.jpg" alt="Operational" class="card-image">
                        <div class="card-content">
                            <h2 class="card-title">OPERATIONAL</h2>
                            <p class="card-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                                sed do eiusmod tempor incididunt ut labore et dolore 
                                magna aliqua.
                            </p>
                            <div class="card-footer">
                                <span class="duration">
                                    <i class="far fa-clock"></i> 30 Minutes
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- SUPPORT Card -->
                    <div class="category-card" onclick="window.location.href='crew_information.php?dept=<?php echo urlencode($department); ?>&category=SUPPORT'">
                        <img src="../assets/image/deck.jpg" alt="Support" class="card-image">
                        <div class="card-content">
                            <h2 class="card-title">SUPPORT</h2>
                            <p class="card-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                                sed do eiusmod tempor incididunt ut labore et dolore 
                                magna aliqua.
                            </p>
                            <div class="card-footer">
                                <span class="duration">
                                    <i class="far fa-clock"></i> 30 Minutes
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="back-link">
                <a href="index.php">â† Back to Departments</a>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

