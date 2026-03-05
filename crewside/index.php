<?php
session_start();

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get crew information from session
$crew_name = $_SESSION['crew_name'] ?? 'Crew Member';
$crew_no = $_SESSION['crew_no'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Dashboard - Navi Shipping</title>
    <link rel="stylesheet" href="index.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="../assets/image/logo.png" alt="NS Logo" class="logo">
            </div>
            
            <nav class="nav-tabs">
                <a href="index.php" class="nav-tab active">NSC Examination</a>
                <a href="#" class="nav-tab">Abstract</a>
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
            <?php if (isset($_SESSION['error_message'])): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; text-align: center; font-weight: 600;">
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']); 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div style="background: #dcfce7; color: #16a34a; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0; text-align: center; font-weight: 600;">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Department Cards -->
            <div class="department-grid">
                <!-- DECK Card -->
                <div class="department-card" onclick="window.location.href='deck_categories.php?dept=DECK'">
                    <img src="../assets/image/deck.jpg" alt="Deck Department" class="card-image">
                    <div class="card-content">
                        <h2 class="card-title">DECK</h2>
                        <p class="card-description">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                            sed do eiusmod tempor incididunt ut labore et dolore 
                            magna aliqua.
                        </p>
                    </div>
                </div>

                <!-- ENGINE Card -->
                <div class="department-card" onclick="window.location.href='deck_categories.php?dept=ENGINE'">
                    <img src="../assets/image/deck.jpg" alt="Engine Department" class="card-image">
                    <div class="card-content">
                        <h2 class="card-title">ENGINE</h2>
                        <p class="card-description">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                            sed do eiusmod tempor incididunt ut labore et dolore 
                            magna aliqua.
                        </p>
                    </div>
                </div>

                <!-- STEWARD Card -->
                <div class="department-card" onclick="window.location.href='deck_categories.php?dept=STEWARD'">
                    <img src="../assets/image/deck.jpg" alt="Steward Department" class="card-image">
                    <div class="card-content">
                        <h2 class="card-title">STEWARD</h2>
                        <p class="card-description">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
                            sed do eiusmod tempor incididunt ut labore et dolore 
                            magna aliqua.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
