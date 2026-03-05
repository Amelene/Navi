<?php
// Get exam data from session
$exam_data = $_SESSION['exam_data'] ?? null;

if ($exam_data) {
    $exam_section = strtoupper($exam_data['department']);
    $exam_name = strtoupper($exam_data['category'] . ' - ' . $exam_data['vessel_type']);
} else {
    $exam_section = 'DECK';
    $exam_name = 'MANAGEMENT - DRY CARGO';
}

// Get questions from session (set in examination.php)
$questions = $_SESSION['exam_questions'] ?? [];
$total_questions = count($questions);

// Get current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

// Calculate progress
$answered_count = 0;
if (isset($_SESSION['exam_answers'])) {
    $answered_count = count($_SESSION['exam_answers']);
}

$progress_percentage = $total_questions > 0 ? round(($answered_count / $total_questions) * 100) : 0;
$remaining_questions = $total_questions - $answered_count;
?>

<aside class="sidebar">
    <!-- Logo + Department Section (Combined) -->
    <div class="sidebar-section logo-abstract-section">
        <img src="../assets/image/examlogo.png" alt="Navi Shipping" class="exam-logo">
        <div class="divider"></div>
        
        <h3 class="exam-section-name"><?php echo htmlspecialchars($exam_section); ?></h3>
        <p class="exam-name"><?php echo htmlspecialchars($exam_name); ?></p>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
        </div>
        <p class="progress-label"><?php echo $progress_percentage; ?>% complete</p>
    </div>

    <!-- Tracking Progress Section -->
    <div class="sidebar-section tracking-section">
        <h3 class="section-title">Tracking Progress</h3>
        <div class="tracking-stats">
            <div class="stat-item">
                <span class="stat-label">Remaining questions</span>
                <span class="stat-value"><?php echo $remaining_questions; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total questions</span>
                <span class="stat-value"><?php echo $total_questions; ?></span>
            </div>
        </div>
    </div>

    <!-- Questions List Section -->
    <div class="sidebar-section questions-section">
        <div class="questions-list">
            <!-- Introduction -->
            <div class="question-item <?php echo ($current_step > 0) ? 'completed' : ''; ?>" 
                 onclick="window.location.href='examination.php'">
                <i class="fas fa-check-circle accomplished-icon"></i>
                <span class="question-text">Introduction</span>
            </div>
            
            <!-- Questions -->
            <?php for ($i = 1; $i <= $total_questions; $i++): 
                $is_current = ($current_step == $i);
                $item_class = $is_current ? 'active' : '';
            ?>
            <div class="question-item <?php echo $item_class; ?>" 
                 data-question-index="<?php echo $i; ?>"
                 onclick="window.location.href='examination.php?step=<?php echo $i; ?>'">
                <i class="far fa-circle unanswered-icon"></i>
                <span class="question-text">Question <?php echo $i; ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Legend Section -->
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
