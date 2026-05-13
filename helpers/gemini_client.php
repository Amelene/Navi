<?php

class GeminiClient
{
    private $apiKey;
    private $model;
    private $timeoutSeconds;

    public function __construct($apiKey = null, $model = null, $timeoutSeconds = 20)
    {
        $this->apiKey = $apiKey ?: getenv('GEMINI_API_KEY');
        $this->model = $model ?: getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash';
        $this->timeoutSeconds = (int)$timeoutSeconds;
    }

    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    public function generateRecommendations(array $strengths, array $improvements)
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $strengthText = empty($strengths) ? 'None identified' : implode(', ', $strengths);
        $improvementText = empty($improvements) ? 'None identified' : implode(', ', $improvements);

        $prompt = "You are assisting with NSC exam feedback for maritime crew.\n"
            . "Create practical, concise recommendations.\n"
            . "Return ONLY a numbered list with 3 to 5 items.\n"
            . "Each item must be one sentence and actionable.\n\n"
            . "Strengths: {$strengthText}\n"
            . "Areas for Improvement: {$improvementText}\n";

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model) . ':generateContent?key=' . rawurlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 400
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log('Gemini cURL error: ' . $curlError);
            return null;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('Gemini HTTP error ' . $httpCode . ': ' . $response);
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            error_log('Gemini invalid JSON response');
            return null;
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            error_log('Gemini empty text response');
            return null;
        }

        return trim($text);
    }
}
