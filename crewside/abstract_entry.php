<?php
session_start();

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Set standalone ABSTRACT exam context
$_SESSION['exam_data'] = [
    'department' => 'ABSTRACT',
    'category' => 'ABSTRACT',
    'vessel_type' => 'GENERAL',
    'crew_no' => $_SESSION['crew_no'] ?? null
];

// Clear previous exam state so abstract questions are reloaded
unset($_SESSION['exam_questions']);
unset($_SESSION['exam_time_limit']);
unset($_SESSION['exam_category_id']);
unset($_SESSION['exam_passing_score']);
unset($_SESSION['exam_start_time']);

// Redirect to introduction page
header('Location: examination.php');
exit();
