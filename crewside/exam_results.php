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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - Navi Shipping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="examination.css">
    <style>
        .results-card {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .results-title {
            color: #FF7E5F;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .results-subtitle {
            color: #126E82;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 50px;
        }
        
        .badge-container {
            margin: 40px 0;
        }
        
        .badge-image {
            width: 280px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        
        .release-message {
            color: #126E82;
            font-size: 1.4rem;
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
            font-size: 1.1rem;
        }
        
        .date-value {
            color: #666;
            font-size: 1.1rem;
            margin-top: 5px;
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
                    <svg class="badge-image" viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg">
                        <!-- Badge Circle -->
                        <circle cx="100" cy="100" r="80" fill="#7DD3E8"/>
                        
                        <!-- Star -->
                        <path d="M100 40 L110 70 L142 75 L121 96 L126 128 L100 113 L74 128 L79 96 L58 75 L90 70 Z" fill="#FFE66D"/>
                        
                        <!-- Ribbons -->
                        <path d="M60 160 L40 240 L70 220 L80 180 Z" fill="#FF8A4C"/>
                        <path d="M140 160 L160 240 L130 220 L120 180 Z" fill="#FF8A4C"/>
                    </svg>
                </div>

                <p class="release-message">
                    Your results will be officially released soon
                </p>

                <div class="date-issued-container">
                    <div class="date-label">Date Issued</div>
                    <div class="date-value"><?php echo $date_issued; ?></div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
