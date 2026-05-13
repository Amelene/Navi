
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/groq_client.php';

class ExamAnalysis
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAnalysis($attemptId)
    {
        $sql = "SELECT * FROM exam_attempt_analysis WHERE attempt_id = ?";
        return $this->db->fetchOne($sql, [$attemptId]);
    }

    public function getCachedRecommendations($attemptId)
    {
        $sql = "SELECT ai_recommendation FROM exam_attempts WHERE id = ? LIMIT 1";
        $row = $this->db->fetchOne($sql, [$attemptId]);

        if (!$row || !isset($row['ai_recommendation']) || trim((string)$row['ai_recommendation']) === '') {
            return [];
        }

        $decoded = json_decode($row['ai_recommendation'], true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $clean = trim($item);
                if ($clean !== '') {
                    $items[] = $clean;
                }
            }
        }

        return array_values(array_unique($items));
    }

    public function cacheRecommendations($attemptId, array $recommendations)
    {
        $sql = "UPDATE exam_attempts SET ai_recommendation = ? WHERE id = ?";
        $this->db->execute($sql, [json_encode(array_values($recommendations)), $attemptId]);
    }

    public function generateAnalysis($attemptId)
    {
        $strengths = $this->getStrengths($attemptId);
        $improvements = $this->getAreasForImprovement($attemptId);
        $recommendations = $this->generateRecommendations($strengths, $improvements, $attemptId);

        $this->saveAnalysis($attemptId, $strengths, $improvements, $recommendations);

        return [
            'strengths' => $strengths,
            'areas_for_improvement' => $improvements,
            'recommendations' => $recommendations,
        ];
    }

    public function saveAnalysis($attemptId, $strengths, $improvements, $recommendations)
    {
        $sql = "INSERT INTO exam_attempt_analysis (attempt_id, strengths, areas_for_improvement, recommendations)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                strengths = VALUES(strengths),
                areas_for_improvement = VALUES(areas_for_improvement),
                recommendations = VALUES(recommendations)";

        $this->db->execute($sql, [
            $attemptId,
            json_encode($strengths),
            json_encode($improvements),
            json_encode($recommendations)
        ]);
    }

    public function getFunctionScores($attemptId)
    {
        $sql = "SELECT 
                    COALESCE(NULLIF(TRIM(q.function), ''), 'General Maritime Knowledge') AS function_name,
                    COUNT(ea.id) AS total_questions,
                    SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS correct_answers
                FROM exam_answers ea
                JOIN questions q ON ea.question_id = q.id
                WHERE ea.exam_attempt_id = ?
                GROUP BY COALESCE(NULLIF(TRIM(q.function), ''), 'General Maritime Knowledge')
                ORDER BY total_questions DESC";

        $rows = $this->db->fetchAll($sql, [$attemptId]);
        $scores = [];

        foreach ($rows as $row) {
            $total = (int)($row['total_questions'] ?? 0);
            $correct = (int)($row['correct_answers'] ?? 0);
            if ($total <= 0) {
                continue;
            }

            $scores[] = [
                'function' => (string)$row['function_name'],
                'total_questions' => $total,
                'correct_answers' => $correct,
                'percentage' => round(($correct / $total) * 100, 2),
            ];
        }

        return $scores;
    }

    public function getStrengths($attemptId)
    {
        $functionScores = $this->getFunctionScores($attemptId);
        $strengths = [];

        foreach ($functionScores as $score) {
            // Require enough sample size or very high score
            if (($score['percentage'] >= 80 && $score['total_questions'] >= 2) || $score['percentage'] >= 90) {
                $strengths[] = $score['function'];
            }
        }

        // Fallback: if none found, pick best-performing function if score is decent
        if (empty($strengths) && !empty($functionScores)) {
            usort($functionScores, function ($a, $b) {
                return $b['percentage'] <=> $a['percentage'];
            });

            $top = $functionScores[0];
            if ($top['percentage'] >= 60) {
                $strengths[] = $top['function'];
            }
        }

        return array_values(array_unique($strengths));
    }

    public function getAreasForImprovement($attemptId)
    {
        $functionScores = $this->getFunctionScores($attemptId);
        $improvements = [];

        foreach ($functionScores as $score) {
            // Require enough sample size or very low score
            if (($score['percentage'] < 70 && $score['total_questions'] >= 2) || $score['percentage'] < 50) {
                $improvements[] = $score['function'];
            }
        }

        // Fallback: if none found, pick lowest-performing function
        if (empty($improvements) && !empty($functionScores)) {
            usort($functionScores, function ($a, $b) {
                return $a['percentage'] <=> $b['percentage'];
            });

            $improvements[] = $functionScores[0]['function'];
        }

        return array_values(array_unique($improvements));
    }

    public function generateRecommendations($strengths, $improvements, $attemptId = null)
    {
        try {
            if ($attemptId !== null) {
                $cached = $this->getCachedRecommendations($attemptId);
                if (!empty($cached)) {
                    return $cached;
                }
            }

            $groq = new GroqClient();
            $generatedText = $groq->generateRecommendations((array)$strengths, (array)$improvements);

            if (!is_string($generatedText) || trim($generatedText) === '') {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $errorInfo = $groq->getLastError();
                    if (!empty($errorInfo)) {
                        $_SESSION['groq_debug'] = $errorInfo;
                    }
                }
                return [];
            }

            $parsed = $this->parseRecommendationsText($generatedText);

            if ($attemptId !== null && !empty($parsed)) {
                $this->cacheRecommendations($attemptId, $parsed);
            }

            return $parsed;
        } catch (Throwable $e) {
            error_log('Groq recommendation generation failed: ' . $e->getMessage());
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['groq_debug'] = [
                    'message' => 'Groq recommendation generation failed',
                    'details' => $e->getMessage(),
                    'time' => date('Y-m-d H:i:s')
                ];
            }
            return [];
        }
    }

    private function parseRecommendationsText($text)
    {
        $lines = preg_split('/\R+/', (string)$text);
        $items = [];

        foreach ($lines as $line) {
            $clean = trim($line);
            if ($clean === '') {
                continue;
            }

            // Remove bullets or numbering prefixes like "1. ", "- ", "* "
            $clean = preg_replace('/^\s*(?:[-*•]+|\d+[\).\s-]+)\s*/', '', $clean);
            $clean = trim($clean);

            if ($clean !== '') {
                $items[] = $clean;
            }
        }

        // Ensure unique and capped output
        $items = array_values(array_unique($items));

        if (count($items) > 5) {
            $items = array_slice($items, 0, 5);
        }

        return $items;
    }
}
