
<?php
require_once __DIR__ . '/../config/database.php';

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

    public function generateAnalysis($attemptId)
    {
        $strengths = $this->getStrengths($attemptId);
        $improvements = $this->getAreasForImprovement($attemptId);
        $recommendations = $this->generateRecommendations($strengths, $improvements);

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

    public function generateRecommendations($strengths, $improvements)
    {
        $recommendations = [];

        if (empty($improvements)) {
            $recommendations[] = "Excellent work! Continue to maintain high standards in all areas.";
        } else {
            foreach ($improvements as $area) {
                $recommendations[] = "Focus on improving knowledge and skills in '{$area}'.";
            }
        }

        return $recommendations;
    }
}
