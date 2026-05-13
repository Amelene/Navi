
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/gemini_client.php';

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
                    q.function,
                    COUNT(ea.id) AS total_questions,
                    SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) AS correct_answers
                FROM exam_answers ea
                JOIN questions q ON ea.question_id = q.id
                WHERE ea.exam_attempt_id = ?
                GROUP BY q.function";

        return $this->db->fetchAll($sql, [$attemptId]);
    }

    public function getStrengths($attemptId)
    {
        $functionScores = $this->getFunctionScores($attemptId);
        $strengths = [];

        foreach ($functionScores as $score) {
            $percentage = ($score['correct_answers'] / $score['total_questions']) * 100;
            if ($percentage >= 80) {
                $strengths[] = $score['function'];
            }
        }

        return $strengths;
    }

    public function getAreasForImprovement($attemptId)
    {
        $functionScores = $this->getFunctionScores($attemptId);
        $improvements = [];

        foreach ($functionScores as $score) {
            $percentage = ($score['correct_answers'] / $score['total_questions']) * 100;
            if ($percentage < 70) {
                $improvements[] = $score['function'];
            }
        }

        return $improvements;
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

            $gemini = new GeminiClient();
            $generatedText = $gemini->generateRecommendations((array)$strengths, (array)$improvements);

            if (!is_string($generatedText) || trim($generatedText) === '') {
                return [];
            }

            $parsed = $this->parseRecommendationsText($generatedText);

            if ($attemptId !== null && !empty($parsed)) {
                $this->cacheRecommendations($attemptId, $parsed);
            }

            return $parsed;
        } catch (Throwable $e) {
            error_log('Gemini recommendation generation failed: ' . $e->getMessage());
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
