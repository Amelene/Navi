<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/exam_analysis.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $db = Database::getInstance();

    $examQuery = "SELECT 
                    ea.id,
                    ea.crew_id,
                    cm.crew_no,
                    CONCAT(cm.first_name, ' ', cm.last_name) as crew_name,
                    p.position_name,
                    cm.nationality,
                    ec.department,
                    ec.category,
                    ec.vessel_type,
                    ec.passing_score,
                    ea.score,
                    ea.total_questions,
                    ea.correct_answers,
                    ea.start_time,
                    ea.end_time,
                    ea.time_taken,
                    ea.status,
                    CASE 
                        WHEN ea.score >= ec.passing_score THEN 'PASSED'
                        ELSE 'FAILED'
                    END as result_status
                   FROM exam_attempts ea
                   INNER JOIN crew_master cm ON ea.crew_id = cm.id
                   INNER JOIN exam_categories ec ON ea.exam_category_id = ec.id
                   LEFT JOIN positions p ON cm.position_id = p.id
                   WHERE ea.id = ?";

    $exam = $db->fetchOne($examQuery, [$attempt_id]);

    if (!$exam) {
        header('Location: ../tests.php');
        exit();
    }

    $minutes  = floor($exam['time_taken'] / 60);
    $seconds  = $exam['time_taken'] % 60;
    $duration = sprintf('%d mins', $minutes);

} catch (Exception $e) {
    error_log("Error fetching exam details: " . $e->getMessage());
    header('Location: ../tests.php');
    exit();
}

// Exam Analysis
$analysis             = new ExamAnalysis();
$strengths            = $analysis->getStrengths($attempt_id);
$areasForImprovement  = $analysis->getAreasForImprovement($attempt_id);
$functionScores       = $analysis->getFunctionScores($attempt_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSC Exam Results Details</title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="test_results.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard">
        <?php
        $GLOBALS['base_path'] = '../../';
        $GLOBALS['nav_path']  = '../';
        include '../../includes/sidebar.php';
        ?>

        <main class="main">
            <div class="main__content">
                <h2 class="page-title">NSC EXAM RESULTS</h2>

                <div class="card card--padded results-card">
                    <!-- Header -->
                    <div class="results-header">
                        <div class="exam-id">
                            <input class="exam-id-input" type="text" value="EXAM-<?php echo str_pad($exam['id'], 5, '0', STR_PAD_LEFT); ?>" readonly>
                        </div>
                        <div class="results-actions">
                            <button class="btn-action btn-edit">EDIT</button>
                            <button class="btn-action btn-delete">DELETE</button>
                            <button class="btn-close" onclick="window.location.href='../tests.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Status and Score -->
                    <div class="status-score-section">
                        <div class="status-badge">
                            <?php if ($exam['result_status'] === 'PASSED'): ?>
                                <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span class="status-text" style="color: #27ae60;">PASSED</span>
                            <?php else: ?>
                                <svg class="status-icon" style="color: #e74c3c;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                <span class="status-text" style="color: #e74c3c;">FAILED</span>
                            <?php endif; ?>
                            <span class="status-label">Result</span>
                        </div>
                        <div class="final-score">
                            <div class="score-value"><?php echo number_format($exam['score'], 0); ?>%</div>
                            <div class="score-label">Final Score</div>
                        </div>
                    </div>

                    <!-- Metrics Row -->
                    <div class="metrics-row">
                        <div class="metric-item">
                            <div class="metric-label">Total Questions</div>
                            <div class="metric-value"><?php echo $exam['total_questions']; ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Correct Answers</div>
                            <div class="metric-value"><?php echo $exam['correct_answers']; ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Time Spent</div>
                            <div class="metric-value"><?php echo $duration; ?></div>
                        </div>
                    </div>

                    <!-- Information Grid -->
                    <div class="info-grid">
                        <div class="info-section">
                            <h3 class="section-title">EXAMINEE INFORMATION</h3>
                            <div class="section-content">
                                <div class="info-item">
                                    <span class="info-label">Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($exam['crew_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Position</span>
                                    <span class="info-value"><?php echo htmlspecialchars($exam['position_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Nationality</span>
                                    <span class="info-value"><?php echo htmlspecialchars($exam['nationality'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="info-section">
                            <h3 class="section-title">EXAM INFORMATION</h3>
                            <div class="section-content">
                                <div class="info-item">
                                    <span class="info-label">Department</span>
                                    <span class="info-value"><?php echo htmlspecialchars($exam['department']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Category</span>
                                    <span class="info-value"><?php echo htmlspecialchars($exam['category']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Date</span>
                                    <span class="info-value"><?php echo date('m - d - Y', strtotime($exam['start_time'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Passing Score</span>
                                    <span class="info-value"><?php echo $exam['passing_score']; ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Functions Section -->
                    <?php if (!empty($functionScores)): ?>
                    <div class="functions-section">
                        <h3 class="section-title">PERFORMANCE BY FUNCTION</h3>
                        <div class="functions-content">
                    <?php foreach ($functionScores as $row):
                                $funcName   = $row['function'] ?? 'Unknown';
                                $funcPct    = ($row['total_questions'] > 0)
                                    ? ($row['correct_answers'] / $row['total_questions']) * 100
                                    : 0;
                            ?>
                            <div class="function-item">
                                <div class="function-label"><?php echo htmlspecialchars($funcName); ?></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, $funcPct); ?>%"></div>
                                    </div>
                                    <span class="progress-percentage"><?php echo number_format($funcPct, 0); ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Feedback Grid -->
                    <div class="feedback-grid">
                        <div class="feedback-section">
                            <h3 class="section-title">STRENGTHS</h3>
                            <div class="feedback-content">
                                <?php if (!empty($strengths)): ?>
                                    <?php foreach ($strengths as $area): ?>
                                        <div class="feedback-item success">
                                            <svg class="feedback-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            <span class="feedback-text"><?php echo htmlspecialchars($area); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="feedback-item"><span class="feedback-text">No specific strengths identified.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="feedback-section">
                            <h3 class="section-title">AREAS FOR IMPROVEMENT</h3>
                            <div class="feedback-content">
                                <?php if (!empty($areasForImprovement)): ?>
                                    <?php foreach ($areasForImprovement as $area): ?>
                                        <div class="feedback-item danger">
                                            <svg class="feedback-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                            <span class="feedback-text"><?php echo htmlspecialchars($area); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="feedback-item"><span class="feedback-text">No specific areas for improvement identified.</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <!-- Certificate Button -->
                    <div class="certificate-section">
                        <button class="btn-certificate" onclick="window.location.href='certificate.php?id=<?php echo $exam['id']; ?>'">VIEW CERTIFICATE</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
