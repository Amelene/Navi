<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if exam data exists
if (!isset($_SESSION['exam_data'])) {
    $_SESSION['error_message'] = "Exam data missing from session.";
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['exam_questions']) || empty($_SESSION['exam_questions'])) {
    $_SESSION['error_message'] = "Exam questions missing from session.";
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['crew_id'])) {
    $_SESSION['error_message'] = "Crew ID missing from session. Please log in again.";
    header('Location: index.php');
    exit();
}

// Get submitted answers
$submitted_answers = json_decode($_POST['exam_answers'] ?? '{}', true);
$time_taken = (int)($_POST['time_taken'] ?? 0);

// Get exam data from session
$exam_data = $_SESSION['exam_data'];
$questions = $_SESSION['exam_questions'];
$category_id = $_SESSION['exam_category_id'];
$passing_score = $_SESSION['exam_passing_score'];
$crew_id = $_SESSION['crew_id'];

try {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    // Get correct answers from database
    $question_ids = array_column($questions, 'id');
    $placeholders = str_repeat('?,', count($question_ids) - 1) . '?';
    
    $correctAnswersQuery = "SELECT q.id as question_id, qo.option_letter, qo.is_correct
                           FROM questions q
                           INNER JOIN question_options qo ON q.id = qo.question_id
                           WHERE q.id IN ($placeholders) AND qo.is_correct = 1";
    
    $correctAnswers = $db->fetchAll($correctAnswersQuery, $question_ids);
    
    // Create lookup array for correct answers
    $correctLookup = [];
    foreach ($correctAnswers as $answer) {
        $correctLookup[$answer['question_id']] = $answer['option_letter'];
    }
    
    // Grade the exam
    $total_questions = count($questions);
    $correct_count = 0;
    
    foreach ($questions as $question) {
        $question_id = $question['id'];
        $correct_answer = $correctLookup[$question_id] ?? null;
        
        // Get student's answer
        $student_answer = null;
        if (isset($submitted_answers[$question_id])) {
            $student_answer = $submitted_answers[$question_id]['optionLetter'] ?? null;
        }
        
        // Check if correct
        if ($student_answer && $correct_answer && $student_answer === $correct_answer) {
            $correct_count++;
        }
    }
    
    // Calculate score percentage
    $score_percentage = ($total_questions > 0) ? round(($correct_count / $total_questions) * 100, 2) : 0;
    
    // Determine pass/fail
    $status = 'completed';
    $passed = ($score_percentage >= $passing_score);
    
    // Create exam attempt record
    $start_time = date('Y-m-d H:i:s', $_SESSION['exam_start_time'] ?? time());
    $end_time = date('Y-m-d H:i:s');
    
    // Get next attempt number for this crew and category
    $attemptQuery = "SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt 
                    FROM exam_attempts 
                    WHERE crew_id = ? AND exam_category_id = ?";
    $attemptData = $db->fetchOne($attemptQuery, [$crew_id, $category_id]);
    $attempt_number = $attemptData['next_attempt'] ?? 1;

    $insertAttempt = "INSERT INTO exam_attempts 
                     (crew_id, exam_category_id, attempt_number, start_time, end_time, 
                      time_taken, score, total_questions, correct_answers, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $db->execute($insertAttempt, [
        $crew_id,
        $category_id,
        $attempt_number,
        $start_time,
        $end_time,
        $time_taken,
        $score_percentage,
        $total_questions,
        $correct_count,
        $status
    ]);
    
    $attempt_id = $db->lastInsertId();
    
    // Save individual answers
    foreach ($questions as $question) {
        $question_id = $question['id'];
        $correct_answer = $correctLookup[$question_id] ?? null;
        
        // Get student's answer
        $student_answer_letter = null;
        $selected_option_id = null;
        
        if (isset($submitted_answers[$question_id])) {
            $student_answer_letter = $submitted_answers[$question_id]['optionLetter'] ?? null;
            $selected_option_id = $submitted_answers[$question_id]['optionId'] ?? null;
        }
        
        // Check if correct
        $is_correct = ($student_answer_letter && $correct_answer && $student_answer_letter === $correct_answer) ? 1 : 0;
        
        $insertAnswer = "INSERT INTO exam_answers 
                        (exam_attempt_id, question_id, selected_option_id, is_correct) 
                        VALUES (?, ?, ?, ?)";
        
        $db->execute($insertAnswer, [
            $attempt_id,
            $question_id,
            $selected_option_id,
            $is_correct
        ]);
    }
    
    $db->commit();
    
    // Store results in session for results page
    $_SESSION['exam_result'] = [
        'attempt_id' => $attempt_id,
        'score' => $score_percentage,
        'correct_answers' => $correct_count,
        'total_questions' => $total_questions,
        'passing_score' => $passing_score,
        'passed' => $passed,
        'time_taken' => $time_taken,
        'department' => $exam_data['department'],
        'category' => $exam_data['category'],
        'vessel_type' => $exam_data['vessel_type']
    ];
    
    // Clear exam session data
    unset($_SESSION['exam_questions']);
    unset($_SESSION['exam_time_limit']);
    unset($_SESSION['exam_category_id']);
    unset($_SESSION['exam_passing_score']);
    unset($_SESSION['exam_start_time']);
    
    // Redirect to results page
    header('Location: exam_results.php');
    exit();
    
} catch (Exception $e) {
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Detailed error logging for diagnosis
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'crew_id' => $_SESSION['crew_id'] ?? 'MISSING',
        'category_id' => $_SESSION['exam_category_id'] ?? 'MISSING',
        'questions_count' => isset($_SESSION['exam_questions']) ? count($_SESSION['exam_questions']) : 'MISSING'
    ];
    error_log("EXAM_ERROR: " . json_encode($error_details));
    file_put_contents(__DIR__ . '/exam_debug.log', date('[Y-m-d H:i:s] ') . json_encode($error_details) . PHP_EOL, FILE_APPEND);
    
    $_SESSION['error_message'] = 'An error occurred: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>
