<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if exam result exists
if (!isset($_SESSION['exam_result'])) {
    header('Location: index.php');
    exit();
}

$result = $_SESSION['exam_result'];
$total_questions = $result['total_questions'];

// Get current date
$date_issued = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon type=image/png href=../assets/image/logo.png>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - Navi Shipping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="examination.css">
    <style>
        .results-card {
            background: white;
            border-radius: 20px;
            padding: 50px 20px;
            text-align: center;
            max-width: 1000px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .results-title {
            color: #FF7E5F;
            font-size: 50px;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .results-subtitle {
            color: #126E82;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 0px;
        }
        
        .badge-container {
            margin: 10px 0;
        }
        
        .badge-image {
            width: 280px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        
        .release-message {
            color: #126E82;
            font-size: 20px;
            font-weight: 600;
            margin: 50px 0 30px 0;
        }
        
        .date-issued-container {
            position: absolute;
            bottom: 40px;
            right: 40px;
            text-align: right;
        }
        
        .date-label {
            font-weight: 700;
            color: #333;
            font-size: 18px;
        }
        
        .date-value {
            color: #666;
            font-size: 16px;
            margin-top: 5px;
        }

        .auto-home-note {
            margin-top: 14px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="exam-container">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section logo-abstract-section">
                <img src="../assets/image/examlogo.png" alt="Navi Shipping" class="exam-logo">
                <div class="divider"></div>
                
                <h3 class="exam-section-name">Abstract</h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 100%"></div>
                </div>
                <p class="progress-label">100% complete</p>
            </div>

            <div class="sidebar-section tracking-section">
                <h3 class="section-title">Tracking Progress</h3>
                <div class="tracking-stats">
                    <div class="stat-item">
                        <span class="stat-label">Remained questions</span>
                        <span class="stat-value">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total questions</span>
                        <span class="stat-value"><?php echo $total_questions; ?></span>
                    </div>
                </div>
            </div>

            <div class="sidebar-section questions-section">
                <div class="questions-list">
                    <div class="question-item completed">
                        <i class="fas fa-check-circle"></i>
                        <span class="question-text">Introduction</span>
                        <span class="question-number">1 / 1</span>
                    </div>
                    <?php for ($i = 1; $i <= $total_questions; $i++): ?>
                    <div class="question-item completed">
                        <i class="fas fa-check-circle"></i>
                        <span class="question-text">Question <?php echo $i; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="sidebar-section legend-section">
                <div class="legend-item">
                    <i class="fas fa-circle accomplished-icon"></i>
                    <span>Accomplished</span>
                </div>
                <div class="legend-item">
                    <i class="fas fa-circle review-icon"></i>
                    <span>Review</span>
                </div>
                <div class="legend-item">
                    <i class="far fa-circle unanswered-icon"></i>
                    <span>Unanswered</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="results-card">
                <h1 class="results-title">CONGRATULATIONS!</h1>
                <p class="results-subtitle">
                    You have successfully completed the NSE Examination
                </p>

                <div class="badge-container">
                    <img src="../assets/image/cco.png" alt="Badge" class="badge-image">
                </div>

                <p class="release-message">
                    Your results will be officially released soon
                </p>

                <div class="date-issued-container">
                    <div class="date-label">Date Issued</div>
                    <div class="date-value"><?php echo $date_issued; ?></div>
                </div>

                <p class="auto-home-note">Redirecting to home in <span id="autoHomeCountdown">10</span> seconds...</p>
            </div>
        </main>
    </div>

    <script>
        (function () {
            let seconds = 10;
            const el = document.getElementById('autoHomeCountdown');
            const timer = setInterval(function () {
                seconds--;
                if (el) el.textContent = String(seconds);
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = 'index.php';
                }
            }, 1000);
        })();
    </script>
</body>
</html>

