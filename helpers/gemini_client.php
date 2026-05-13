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

    public function getLastError()
    {
        return $_SESSION['gemini_last_error'] ?? null;
    }

    private function setLastError($message, $details = null)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['gemini_last_error'] = [
                'message' => $message,
                'details' => $details,
                'time' => date('Y-m-d H:i:s')
            ];
        }
        error_log('Gemini error: ' . $message . ($details ? ' | ' . (is_string($details) ? $details : json_encode($details)) : ''));
    }

    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    public function generateRecommendations(array $strengths, array $improvements)
    {
        if (!$this->isConfigured()) {
            $this->setLastError('GEMINI_API_KEY is missing or empty.');
            return null;
        }

        if (!function_exists('curl_init')) {
            $this->setLastError('PHP cURL extension is not enabled.');
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
            $this->setLastError('Gemini cURL error', $curlError);
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiError = $decoded['error']['message'] ?? $response;
            $apiStatus = $decoded['error']['status'] ?? null;
            $this->setLastError('Gemini HTTP error ' . $httpCode, [
                'status' => $apiStatus,
                'message' => $apiError,
                'raw_response' => $decoded ?: $response
            ]);
            return null;
        }

        if (!is_array($decoded)) {
            $this->setLastError('Gemini invalid JSON response', $response);
            return null;
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            $this->setLastError('Gemini empty text response', $decoded);
            return null;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['gemini_last_error']);
        }

        return trim($text);
    }
}
