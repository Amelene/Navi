<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/exam_analysis.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Forbidden');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempt_id            = $_POST['attempt_id']            ?? 0;
    $manual_recommendation = $_POST['manual_recommendation'] ?? '';

    if ($attempt_id > 0) {
        $analysis = new ExamAnalysis();

        $strengths             = $analysis->getStrengths($attempt_id);
        $areas_for_improvement = $analysis->getAreasForImprovement($attempt_id);
        $recommendations       = $analysis->generateRecommendations($strengths, $areas_for_improvement);

        $analysis->saveAnalysis($attempt_id, $strengths, $areas_for_improvement, $recommendations, $manual_recommendation);

        header('Location: test_results.php?id=' . $attempt_id);
        exit();
    }
}

header('Location: ../tests.php');
exit();
