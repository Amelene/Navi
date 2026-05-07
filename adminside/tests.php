<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get exam statistics and results
try {
    $db = Database::getInstance();
    
    // Check if exam_attempts table exists
    $tableExists = false;
    try {
        $db->fetchOne("SELECT 1 FROM exam_attempts LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    if (!$tableExists) {
        $totalExaminers = 0;
        $passed         = 0;
        $inProgress     = 0;
        $failed         = 0;
        $exams          = [];
        $showSetupMessage = true;
    } else {
        $showSetupMessage = false;
        
        $totalExaminers = $db->fetchOne("SELECT COUNT(*) as count FROM exam_attempts")['count'] ?? 0;
        $passed         = $db->fetchOne("SELECT COUNT(*) as count FROM exam_attempts WHERE score >= (SELECT passing_score FROM exam_categories WHERE id = exam_category_id LIMIT 1)")['count'] ?? 0;
        $inProgress     = $db->fetchOne("SELECT COUNT(*) as count FROM exam_attempts WHERE status = 'in_progress'")['count'] ?? 0;
        $failed         = $db->fetchOne("SELECT COUNT(*) as count FROM exam_attempts WHERE score < (SELECT passing_score FROM exam_categories WHERE id = exam_category_id LIMIT 1) AND status = 'completed'")['count'] ?? 0;
    
        $exams = $db->fetchAll(
            "SELECT 
                ea.id,
                ea.crew_id,
                CONCAT(cm.first_name, ' ', cm.last_name) as crew_name,
                ec.department,
                ec.category,
                ec.vessel_type,
                ec.passing_score,
                ea.score,
                ea.status,
                ea.start_time,
                ea.time_taken,
                CASE 
                    WHEN ea.score >= ec.passing_score THEN 'PASSED'
                    WHEN ea.score < ec.passing_score  THEN 'FAILED'
                    ELSE 'IN PROGRESS'
                END as result_status
             FROM exam_attempts ea
             INNER JOIN crew_master cm ON ea.crew_id = cm.id
             INNER JOIN exam_categories ec ON ea.exam_category_id = ec.id
             ORDER BY ea.start_time DESC"
        );
    }
    
} catch (Exception $e) {
    error_log("Error fetching exam data: " . $e->getMessage());
    $totalExaminers = $passed = $inProgress = $failed = 0;
    $exams = [];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NSC Exam Results</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard">
        <?php
        $GLOBALS['base_path'] = '../';
        $GLOBALS['nav_path']  = '';
        include '../includes/sidebar.php';
        ?>

        <main class="main">
            <div class="main__content">
                <h2 class="page-title">NSC EXAM RESULTS</h2>

                <div class="metrics">
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">TOTAL EXAMINER</div>
                            <div class="metric__number"><?php echo $totalExaminers; ?></div>
                        </div>
                        <div class="metric__icon icon-users">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">PASSED</div>
                            <div class="metric__number"><?php echo $passed; ?></div>
                        </div>
                        <div class="metric__icon icon-check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">IN PROGRESS</div>
                            <div class="metric__number"><?php echo $inProgress; ?></div>
                        </div>
                        <div class="metric__icon icon-clock">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">FAILED</div>
                            <div class="metric__number"><?php echo $failed; ?></div>
                        </div>
                        <div class="metric__icon icon-alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                    </div>
                </div>

                <div class="card card--padded">
                    <div class="card__header">
                        <div class="card__title">Test Records</div>
                        <div class="card__actions">
                            <a href="question_bank.php" class="btn warn add">Question Bank</a>
                        </div>
                    </div>

                    <div class="card__controls">
                        <div class="search-wrap">
                            <div class="search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <input class="input-search" type="search" placeholder="Search...">
                        </div>
                        <div class="crew-controls">
                            <button class="btn ghost">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Department
                            </button>
                            <button class="btn ghost">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                Category
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-wrap">
                            <table class="crew-table">
                                <thead>
                                    <tr>
                                        <th>TEST ID</th>
                                        <th>NAME</th>
                                        <th>DEPARTMENT</th>
                                        <th>CATEGORY</th>
                                        <th>STATUS</th>
                                        <th>DATE</th>
                                        <th>TIME SPENT</th>
                                        <th>RESULTS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($showSetupMessage) && $showSetupMessage): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 40px;">
                                                <h3 style="color: #dc3545; margin-bottom: 15px;">⚠️ Database Not Set Up</h3>
                                                <p style="margin-bottom: 20px;">The exam tables have not been created yet. Please run the setup script first.</p>
                                                <a href="../testfile/setup_exam_tables.php" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                                                    Run Setup Script
                                                </a>
                                            </td>
                                        </tr>
                                    <?php elseif (empty($exams)): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 40px;">No exam records found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($exams as $exam):
                                            $statusClass = $exam['result_status'] === 'PASSED' ? 'success' : ($exam['result_status'] === 'FAILED' ? 'danger' : 'warning');
                                            $minutes     = floor($exam['time_taken'] / 60);
                                            $seconds     = $exam['time_taken'] % 60;
                                            $timeFormatted = sprintf('%02d:%02d', $minutes, $seconds);
                                        ?>
                                        <tr>
                                            <td class="fw-bold">EXAM-<?php echo str_pad($exam['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td class="fw-bold"><?php echo strtoupper($exam['crew_name']); ?></td>
                                            <td class="fw-bold"><?php echo strtoupper($exam['department']); ?></td>
                                            <td class="fw-bold"><?php echo strtoupper($exam['category']); ?></td>
                                            <td class="crew-status <?php echo $statusClass; ?>"><?php echo $exam['result_status']; ?></td>
                                            <td class="fw-bold"><?php echo date('m - d - Y', strtotime($exam['start_time'])); ?></td>
                                            <td class="fw-bold"><?php echo $timeFormatted; ?></td>
                                            <td><a href="test_results/test_results.php?id=<?php echo $exam['id']; ?>" class="link-action">VIEW RESULTS</a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
