<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get exam data from session
$exam_data = $_SESSION['exam_data'] ?? null;
if (!$exam_data) {
    header('Location: index.php');
    exit();
}

// Build exam title components
$exam_section = strtoupper($exam_data['department']); // e.g., "DECK"
$exam_name = strtoupper($exam_data['category'] . ' - ' . $exam_data['vessel_type']); // e.g., "MANAGEMENT - DRY CARGO"

$force_reload_questions = isset($_GET['refresh']) && $_GET['refresh'] == '1';

// Load questions from database - ONLY if not already loaded in session
if ($force_reload_questions || !isset($_SESSION['exam_questions']) || empty($_SESSION['exam_questions'])) {
    try {
        $db = Database::getInstance();

    // Get exam category ID - match exactly what's in database, or fallback to GENERAL
    $categoryQuery = "SELECT id, total_questions, time_limit, passing_score FROM exam_categories 
                      WHERE UPPER(department) = UPPER(?) 
                      AND UPPER(category) = UPPER(?) 
                      AND (UPPER(vessel_type) = UPPER(?) OR UPPER(vessel_type) = 'GENERAL')
                      AND status = 'active'
                      ORDER BY (UPPER(vessel_type) = UPPER(?)) DESC
                      LIMIT 1";
    
    $category = $db->fetchOne($categoryQuery, [
        $exam_data['department'],
        $exam_data['category'],
        $exam_data['vessel_type'],
        $exam_data['vessel_type']
    ]);

        $questions = [];
        $total_questions = 40; // LIMIT TO 40 QUESTIONS
        $time_limit = 30; // default
        $passing_score = 60; // default

        if ($category) {
            $categoryId = $category['id'];
            $time_limit = $category['time_limit'];
            $passing_score = $category['passing_score'];
            
            // First, get 40 random questions
            $randomQuestionsQuery = "SELECT id, question_text, image_filename, question_order
                                    FROM questions
                                    WHERE exam_category_id = ? AND status = 'active'
                                    ORDER BY RAND()
                                    LIMIT 40";
            
            $randomQuestions = $db->fetchAll($randomQuestionsQuery, [$categoryId]);
            
            if (empty($randomQuestions)) {
                throw new Exception("No questions found for this exam category");
            }
            
            // Get question IDs
            $questionIds = array_column($randomQuestions, 'id');
            $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';
            
            // Now get all options for these questions and preserve random order from $randomQuestions
            $questionsQuery = "SELECT q.id, q.question_text, q.image_filename, q.question_order,
                                      qo.id as option_id, qo.option_letter, qo.option_text
                               FROM questions q
                               LEFT JOIN question_options qo ON q.id = qo.question_id
                               WHERE q.id IN ($placeholders)
                               ORDER BY FIELD(q.id, $placeholders), qo.option_letter";
            
            $results = $db->fetchAll($questionsQuery, array_merge($questionIds, $questionIds));
            
            // Group options by question
            $questionsData = [];
            foreach ($results as $row) {
                $qId = $row['id'];
                if (!isset($questionsData[$qId])) {
                    $questionsData[$qId] = [
                        'id' => $qId,
                        'text' => $row['question_text'],
                        'image_filename' => $row['image_filename'] ?? null,
                        'order' => $row['question_order'],
                        'options' => [],
                        'status' => 'unanswered'
                    ];
                }
                if ($row['option_id']) {
                    $questionsData[$qId]['options'][] = [
                        'id' => $row['option_id'],
                        'letter' => $row['option_letter'],
                        'text' => $row['option_text']
                    ];
                }
            }
            
            $questions = array_values($questionsData);
            
            // Renumber questions 1-40
            foreach ($questions as $index => &$question) {
                $question['display_number'] = $index + 1;
            }
            
            $total_questions = count($questions);
        }

        // Store questions and exam data in session for exam processing
        $_SESSION['exam_questions'] = $questions;
        $_SESSION['exam_time_limit'] = $time_limit;
        $_SESSION['exam_category_id'] = $category['id'] ?? null;
        $_SESSION['exam_passing_score'] = $passing_score;
        $_SESSION['exam_start_time'] = time();

    } catch (Exception $e) {
        error_log("Error loading questions: " . $e->getMessage());
        $questions = [];
        $total_questions = 0;
        $time_limit = 30;
        $passing_score = 60;
    }
} else {
    // Use existing questions from session
    $questions = $_SESSION['exam_questions'];
    $total_questions = count($questions);
    $time_limit = $_SESSION['exam_time_limit'] ?? 30;
    $passing_score = $_SESSION['exam_passing_score'] ?? 60;
}

// Get current question index from URL or default to intro
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
$show_intro = ($current_step === 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam_section . ' ' . $exam_name); ?> - Examination</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="examination.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="exam-container">
        <!-- Left Sidebar -->
        <?php include 'includes/exam_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-card">
                <?php if ($show_intro): ?>
                    <!-- INTRODUCTION PAGE -->
                    <h1 class="content-title">INTRODUCTION</h1>
                    
                    <div class="intro-content">
                        <h2 class="intro-heading">Lorem ipsum dolor sit amet,</h2>
                        
                        <p class="intro-text">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod 
                            tempor incididunt ut labore et dolore magna aliqua.
                        </p>

                        <ul class="instructions-list">
                            <li>You will have <?php echo $time_limit; ?> minutes to complete the exam.</li>
                            <li>Once you click Start, the timer will begin.</li>
                            <li>Do not refresh, close, or leave the page during the exam.</li>
                            <li>Ensure you have a stable internet connection before starting.</li>
                            <li>Use only one device while taking the test.</li>
                            <li>Answer all questions honestly and to the best of your knowledge.</li>
                            <li>Click Submit before the timer ends.</li>
                        </ul>

                        <div class="start-button-container">
                            <button class="start-btn" onclick="startExam()">START</button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- QUESTION PAGE -->
                    <?php 
                    $currentQuestion = null;
                    if ($current_step > 0 && $current_step <= count($questions)) {
                        $currentQuestion = $questions[$current_step - 1];
                    }
                    
                    if ($currentQuestion): 
                    ?>
                        <div class="question-header">
                            <h1 class="question-title">QUESTION #<?php echo $currentQuestion['display_number'] ?? $current_step; ?></h1>
                            <div class="timer" id="timer-display">
                                <?php 
                                $minutes = floor($time_limit);
                                $seconds = 0;
                                echo sprintf('%02d:%02d', $minutes, $seconds);
                                ?>
                            </div>
                        </div>
                        
                        <div class="question-content">
                            <?php
                            $questionText = $currentQuestion['text'] ?? '';
                            $questionImage = null;

                            // Preferred: DB image filename mapped to crewside/abstract_question
                            if (!empty($currentQuestion['image_filename'])) {
                                $candidatePath = __DIR__ . '/abstract_question/' . $currentQuestion['image_filename'];
                                if (file_exists($candidatePath)) {
                                    $questionImage = 'abstract_question/' . $currentQuestion['image_filename'];
                                }
                            }

                            // Fallback 1: filename match q_{question_id}.png (Option 2 style)
                            if ($questionImage === null) {
                                $autoName = 'q_' . ($currentQuestion['id'] ?? '') . '.png';
                                $autoPath = __DIR__ . '/abstract_question/' . $autoName;
                                if (file_exists($autoPath)) {
                                    $questionImage = 'abstract_question/' . $autoName;
                                }
                            }

                            // Fallback 2 (backward compatibility): question_text is image path
                            $isImageQuestionText = preg_match('/\.(png|jpe?g|webp|gif)$/i', trim($questionText));
                            if ($questionImage === null && $isImageQuestionText) {
                                $questionImage = $questionText;
                            }
                            ?>
                            <div class="question-layout <?php echo ($questionImage !== null) ? 'has-image' : 'no-image'; ?>">
                                <?php if ($questionImage !== null): ?>
                                    <div class="question-media">
                                        <div class="question-image-wrapper">
                                            <img src="<?php echo htmlspecialchars($questionImage); ?>"
                                                 alt="Question Image"
                                                 class="question-image">
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="question-main">
                                    <?php if (!$isImageQuestionText): ?>
                                        <p class="question-text">
                                            <?php echo htmlspecialchars($questionText); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="options-container">
                                        <?php foreach ($currentQuestion['options'] as $option): ?>
                                            <div class="option-card" 
                                                 data-question-id="<?php echo $currentQuestion['id']; ?>"
                                                 data-option-id="<?php echo $option['id']; ?>"
                                                 data-option-letter="<?php echo $option['letter']; ?>"
                                                 onclick="selectAnswer(<?php echo $currentQuestion['id']; ?>, <?php echo $option['id']; ?>, '<?php echo $option['letter']; ?>')">
                                                <div class="option-radio"></div>
                                                <div class="option-content">
                                                    <span class="option-letter"><?php echo $option['letter']; ?>.</span>
                                                    <span class="option-text"><?php echo htmlspecialchars($option['text']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="navigation-buttons">
                                        <button class="btn-nav btn-review" onclick="reviewAnswers()">REVIEW</button>
                                        <button class="btn-nav btn-back" onclick="navigateQuestion('back')" 
                                                <?php echo ($current_step <= 1) ? 'disabled' : ''; ?>>
                                            ← BACK
                                        </button>
                                        <?php if ($current_step < $total_questions): ?>
                                            <button class="btn-nav btn-next" onclick="navigateQuestion('next')">
                                                NEXT →
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-nav btn-submit" onclick="submitExam()">
                                                SUBMIT
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="error-message">
                            <h2>Question not found</h2>
                            <p>Please go back to the introduction.</p>
                            <button class="start-btn" onclick="window.location.href='examination.php'">
                                Back to Introduction
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Custom Modal -->
    <div id="custom-modal-backdrop" class="custom-modal-backdrop hidden" aria-hidden="true">
        <div class="custom-modal" role="dialog" aria-modal="true" aria-labelledby="custom-modal-title" aria-describedby="custom-modal-message">
            <h3 id="custom-modal-title" class="custom-modal-title">Notice</h3>
            <p id="custom-modal-message" class="custom-modal-message"></p>
            <div id="custom-modal-actions" class="custom-modal-actions"></div>
        </div>
    </div>

    <script>
        // Timer variables
        let timeRemaining;
        let timerInterval;
        let examStarted = <?php echo $show_intro ? 'false' : 'true'; ?>;
        let isNavigating = false; // Flag to prevent beforeunload prompt during navigation

        function getModalElements() {
            return {
                backdrop: document.getElementById('custom-modal-backdrop'),
                title: document.getElementById('custom-modal-title'),
                message: document.getElementById('custom-modal-message'),
                actions: document.getElementById('custom-modal-actions')
            };
        }

        function closeCustomModal() {
            const { backdrop, actions } = getModalElements();
            if (!backdrop || !actions) return;
            actions.innerHTML = '';
            backdrop.classList.add('hidden');
            backdrop.setAttribute('aria-hidden', 'true');
        }

        function showModal({ title = 'Notice', message = '', type = 'info' }) {
            const { backdrop, title: titleEl, message: messageEl, actions } = getModalElements();
            if (!backdrop || !titleEl || !messageEl || !actions) return;

            titleEl.textContent = title;
            messageEl.textContent = message;
            actions.innerHTML = '';

            const okBtn = document.createElement('button');
            okBtn.type = 'button';
            okBtn.className = 'modal-btn modal-btn-primary';
            okBtn.textContent = 'OK';
            okBtn.addEventListener('click', closeCustomModal);

            actions.appendChild(okBtn);

            backdrop.classList.remove('hidden');
            backdrop.setAttribute('aria-hidden', 'false');
        }

        function showConfirmModal({ title = 'Confirm Submission', message = 'Are you sure you want to submit your exam?' }) {
            return new Promise((resolve) => {
                const { backdrop, title: titleEl, message: messageEl, actions } = getModalElements();
                if (!backdrop || !titleEl || !messageEl || !actions) {
                    resolve(false);
                    return;
                }

                titleEl.textContent = title;
                messageEl.textContent = message;
                actions.innerHTML = '';

                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'modal-btn modal-btn-secondary';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.addEventListener('click', () => {
                    closeCustomModal();
                    resolve(false);
                });

                const confirmBtn = document.createElement('button');
                confirmBtn.type = 'button';
                confirmBtn.className = 'modal-btn modal-btn-primary';
                confirmBtn.textContent = 'Submit';
                confirmBtn.addEventListener('click', () => {
                    closeCustomModal();
                    resolve(true);
                });

                actions.appendChild(cancelBtn);
                actions.appendChild(confirmBtn);

                backdrop.classList.remove('hidden');
                backdrop.setAttribute('aria-hidden', 'false');
            });
        }

        function startExam() {
            // Clear any previous exam data from sessionStorage
            sessionStorage.removeItem('exam_answers');
            sessionStorage.removeItem('exam_review_marks');
            sessionStorage.removeItem('exam_end_time');

            // Set end time (current time + time limit)
            const endTime = Date.now() + (<?php echo $time_limit; ?> * 60 * 1000);
            sessionStorage.setItem('exam_end_time', endTime);
            
            // Redirect to first question and force fresh DB load
            isNavigating = true;
            window.location.href = 'examination.php?refresh=1&step=1';
        }

        function startTimer() {
            const endTime = parseInt(sessionStorage.getItem('exam_end_time'));
            
            if (!endTime) {
                // Fallback if somehow missing
                const newEndTime = Date.now() + (<?php echo $time_limit; ?> * 60 * 1000);
                sessionStorage.setItem('exam_end_time', newEndTime);
                timeRemaining = <?php echo $time_limit * 60; ?>;
            } else {
                // Calculate remaining time based on fixed end time
                timeRemaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
            }
            
            // Update display immediately
            updateTimerDisplay();
            
            timerInterval = setInterval(function() {
                // Recalculate every second to stay accurate
                timeRemaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
                
                // Update timer display
                updateTimerDisplay();
                
                // Check if time is up
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    sessionStorage.removeItem('exam_end_time');
                    submitExam(true); // Auto-submit when time is up
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const timerDisplay = document.getElementById('timer-display');
            if (timerDisplay) {
                timerDisplay.textContent = 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
            }
        }

function selectAnswer(questionId, optionId, optionLetter) {
    // Store answer in session storage
    const answers = JSON.parse(sessionStorage.getItem('exam_answers') || '{}');
    answers[questionId] = {
        optionId: optionId,
        optionLetter: optionLetter
    };
    sessionStorage.setItem('exam_answers', JSON.stringify(answers));

    // If question was marked for review before, remove review mark automatically
    const reviewMarks = JSON.parse(sessionStorage.getItem('exam_review_marks') || '{}');
    if (reviewMarks[questionId]) {
        delete reviewMarks[questionId];
        sessionStorage.setItem('exam_review_marks', JSON.stringify(reviewMarks));
    }

    // Update UI - remove selected class from all options for current question only
    const currentQuestionId = <?php echo $currentQuestion['id'] ?? 'null'; ?>;
    if (currentQuestionId == questionId) {
        const allOptions = document.querySelectorAll('.option-card');
        allOptions.forEach(opt => opt.classList.remove('selected'));

        // Add selected class to clicked option
        event.currentTarget.classList.add('selected');
    }

    // Update sidebar immediately
    updateSidebar();

    // Auto-next after selecting an answer (if not last question)
    const currentStep = <?php echo $current_step; ?>;
    const totalQuestions = <?php echo $total_questions; ?>;
    if (currentStep < totalQuestions) {
        setTimeout(() => navigateQuestion('next'), 150);
    }
}

        
        // Restore selected answer when page loads
        function restoreSelectedAnswer() {
            const answers = JSON.parse(sessionStorage.getItem('exam_answers') || '{}');
            const currentQuestionId = <?php echo $currentQuestion['id'] ?? 'null'; ?>;
            
            if (currentQuestionId && answers[currentQuestionId]) {
                const selectedOptionId = answers[currentQuestionId].optionId;
                
                // Find and mark the selected option
                const allOptions = document.querySelectorAll('.option-card');
                allOptions.forEach(opt => {
                    if (parseInt(opt.dataset.optionId) === selectedOptionId) {
                        opt.classList.add('selected');
                    }
                });
            }
        }
        
        function scrollActiveQuestionIntoView() {
            const currentStep = <?php echo $current_step; ?>;
            if (!currentStep || currentStep < 1) return;

            const listContainer = document.querySelector('.questions-list');
            const activeItem = document.querySelector('.questions-list .question-item.active');

            if (!listContainer || !activeItem) return;

            // Small guard for early steps
            if (currentStep <= 1) {
                return;
            }

            // Preferred behavior
            if (typeof activeItem.scrollIntoView === 'function') {
                activeItem.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'center'
                });
                return;
            }

            // Fallback manual scroll calculation
            const itemTop = activeItem.offsetTop;
            const itemBottom = itemTop + activeItem.offsetHeight;
            const viewTop = listContainer.scrollTop;
            const viewBottom = viewTop + listContainer.clientHeight;

            if (itemTop < viewTop) {
                listContainer.scrollTop = itemTop - 12;
            } else if (itemBottom > viewBottom) {
                listContainer.scrollTop = itemBottom - listContainer.clientHeight + 12;
            }
        }

        // Update sidebar to show answered questions
        function updateSidebar() {
            const answers = JSON.parse(sessionStorage.getItem('exam_answers') || '{}');
            const reviewMarks = JSON.parse(sessionStorage.getItem('exam_review_marks') || '{}');
            const questionItems = document.querySelectorAll('.question-item');
            
            // Get all question IDs from PHP
            const questionIds = <?php echo json_encode(array_column($questions, 'id')); ?>;
            
            // Only count answers that belong to the current set of questions
            let answeredCount = 0;
            questionIds.forEach(id => {
                if (answers[id]) answeredCount++;
            });

            questionItems.forEach((item, index) => {
                // Skip introduction (index 0)
                if (index === 0) return;
                
                const questionId = questionIds[index - 1];
                const icon = item.querySelector('i');
                const isAnswered = !!answers[questionId];
                const isReviewMarked = !!reviewMarks[questionId];

                item.classList.remove('completed', 'unanswered', 'for-review');

                if (isReviewMarked) {
                    item.classList.add('for-review');
                    icon.className = 'fas fa-circle review-icon';
                } else if (isAnswered) {
                    // Question is answered - show green checkmark
                    item.classList.add('completed');
                    icon.className = 'fas fa-check-circle accomplished-icon';
                } else {
                    // Question is unanswered - show blue outline circle
                    item.classList.add('unanswered');
                    icon.className = 'far fa-circle unanswered-icon';
                }
            });
            
            // Update progress
            const totalQuestions = questionIds.length;
            const progressPercentage = totalQuestions > 0 ? Math.round((answeredCount / totalQuestions) * 100) : 0;
            
            const progressFill = document.querySelector('.progress-fill');
            const progressLabel = document.querySelector('.progress-label');
            
            // Update stats - be specific with selectors
            const remainingQuestionsEl = document.querySelector('.stat-item:nth-child(1) .stat-value');
            const totalQuestionsEl = document.querySelector('.stat-item:nth-child(2) .stat-value');
            
            if (progressFill) progressFill.style.width = progressPercentage + '%';
            if (progressLabel) progressLabel.textContent = progressPercentage + '% complete';
            if (remainingQuestionsEl) remainingQuestionsEl.textContent = totalQuestions - answeredCount;
            if (totalQuestionsEl) totalQuestionsEl.textContent = totalQuestions;

            // Keep current question visible in sidebar list
            scrollActiveQuestionIntoView();
        }

        function reviewAnswers() {
            const currentQuestionId = <?php echo $currentQuestion['id'] ?? 'null'; ?>;
            if (!currentQuestionId) return;

            const reviewMarks = JSON.parse(sessionStorage.getItem('exam_review_marks') || '{}');

            if (reviewMarks[currentQuestionId]) {
                delete reviewMarks[currentQuestionId];
            } else {
                reviewMarks[currentQuestionId] = true;
            }

            sessionStorage.setItem('exam_review_marks', JSON.stringify(reviewMarks));
            updateSidebar();

            // Auto-next after marking review (if not last question)
            const currentStep = <?php echo $current_step; ?>;
            const totalQuestions = <?php echo $total_questions; ?>;
            if (currentStep < totalQuestions) {
                setTimeout(() => navigateQuestion('next'), 150);
            }
        }

        function navigateToExamIntro() {
            isNavigating = true;
            window.location.href = 'examination.php';
        }

        function navigateToExamStep(step) {
            const safeStep = parseInt(step, 10);
            if (!safeStep || safeStep < 1) return;

            isNavigating = true;
            window.location.href = 'examination.php?step=' + safeStep;
        }

        function navigateQuestion(direction) {
            const currentStep = <?php echo $current_step; ?>;
            const totalQuestions = <?php echo $total_questions; ?>;
            
            let nextStep = currentStep;
            if (direction === 'next' && currentStep < totalQuestions) {
                nextStep = currentStep + 1;
            } else if (direction === 'back' && currentStep > 1) {
                nextStep = currentStep - 1;
            }
            
            if (nextStep !== currentStep) {
                navigateToExamStep(nextStep);
            }
        }

        async function submitExam(autoSubmit = false) {
            const questionIds = <?php echo json_encode(array_column($questions, 'id')); ?>;
            const answers = JSON.parse(sessionStorage.getItem('exam_answers') || '{}');
            const reviewMarks = JSON.parse(sessionStorage.getItem('exam_review_marks') || '{}');

            // Manual submit lock: while timer is still running, allow submit ONLY if fully completed (all answered and no review marks)
            if (!autoSubmit && timeRemaining > 0) {
                const allAnswered = questionIds.every(qid => !!answers[qid]);
                const hasReviewMarks = questionIds.some(qid => !!reviewMarks[qid]);

                if (allAnswered && !hasReviewMarks) {
                    // allow manual submit even with remaining time
                } else {
                    let targetStep = null;

                // 1) First priority: questions marked for review
                for (let i = 0; i < questionIds.length; i++) {
                    const qid = questionIds[i];
                    if (reviewMarks[qid]) {
                        targetStep = i + 1;
                        break;
                    }
                }

                // 2) Second priority: unanswered questions
                if (!targetStep) {
                    for (let i = 0; i < questionIds.length; i++) {
                        const qid = questionIds[i];
                        if (!answers[qid]) {
                            targetStep = i + 1;
                            break;
                        }
                    }
                }

                    if (targetStep) {
                        showModal({
                            title: 'Submission Not Allowed Yet',
                            message: 'You cannot submit while time is still running. Click OK to go to the next review or unanswered question.'
                        });

                        const { actions } = getModalElements();
                        if (actions) {
                            const okBtn = actions.querySelector('.modal-btn-primary');
                            if (okBtn) {
                                okBtn.addEventListener('click', () => {
                                    navigateToExamStep(targetStep);
                                }, { once: true });
                            }
                        }
                        return;
                    }

                    showModal({
                        title: 'Submission Not Allowed Yet',
                        message: 'You cannot submit while there is still remaining time.'
                    });
                    return;
                }
            }

            const message = autoSubmit
                ? 'Time is up. Your exam will now be submitted automatically.'
                : 'Are you sure you want to submit your exam?';

            if (autoSubmit) {
                showModal({
                    title: 'Auto Submit',
                    message: message
                });
                const handleAutoSubmit = () => {
                    // Set flag to prevent beforeunload prompt
                    isNavigating = true;

                    // Create form to submit answers
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'process_exam.php';

                    // Add answers as hidden input
                    const answersInput = document.createElement('input');
                    answersInput.type = 'hidden';
                    answersInput.name = 'exam_answers';
                    answersInput.value = JSON.stringify(answers);
                    form.appendChild(answersInput);

                    // Add time taken
                    const timeTakenInput = document.createElement('input');
                    timeTakenInput.type = 'hidden';
                    timeTakenInput.name = 'time_taken';
                    timeTakenInput.value = <?php echo $time_limit * 60; ?> - timeRemaining;
                    form.appendChild(timeTakenInput);

                    document.body.appendChild(form);
                    form.submit();
                };

                const { actions } = getModalElements();
                if (actions) {
                    const okBtn = actions.querySelector('.modal-btn-primary');
                    if (okBtn) {
                        okBtn.addEventListener('click', handleAutoSubmit, { once: true });
                    }
                }
                return;
            }

            const shouldSubmit = await showConfirmModal({
                title: 'Submit Exam',
                message: message
            });

            if (shouldSubmit) {
                // Set flag to prevent beforeunload prompt
                isNavigating = true;

                // Create form to submit answers
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process_exam.php';

                // Add answers as hidden input
                const answersInput = document.createElement('input');
                answersInput.type = 'hidden';
                answersInput.name = 'exam_answers';
                answersInput.value = JSON.stringify(answers);
                form.appendChild(answersInput);

                // Add time taken
                const timeTakenInput = document.createElement('input');
                timeTakenInput.type = 'hidden';
                timeTakenInput.name = 'time_taken';
                timeTakenInput.value = <?php echo $time_limit * 60; ?> - timeRemaining;
                form.appendChild(timeTakenInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Start timer when exam begins
        if (examStarted) {
            window.addEventListener('load', function() {
                startTimer();
                restoreSelectedAnswer(); // Restore previously selected answer
                updateSidebar(); // Update sidebar to show answered questions
                scrollActiveQuestionIntoView(); // Immediate single-pass positioning to avoid jump effect
                
                // Add click listeners to all navigation elements to prevent beforeunload prompt
                document.querySelectorAll('.question-item, .btn-nav, .start-btn, .back-link a').forEach(el => {
                    el.addEventListener('click', function() {
                        isNavigating = true;
                    });
                });
            });
        }

        // Prevent page refresh during exam (but allow navigation within exam)
        if (examStarted) {
            window.addEventListener('beforeunload', function(e) {
                // Don't show prompt if we're navigating within the exam
                if (isNavigating) {
                    return undefined;
                }
                
                // Show prompt for actual page leave/refresh
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave? Your exam progress may be lost.';
                return e.returnValue;
            });
        }
    </script>
</body>
</html>
