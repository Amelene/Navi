<?php

class GroqClient
{
    private $apiKey;
    private $model;
    private $timeoutSeconds;

    public function __construct($apiKey = null, $model = null, $timeoutSeconds = 20)
    {
        $this->apiKey = $apiKey ?: getenv('GROQ_API_KEY');
        $this->model = $model ?: getenv('GROQ_MODEL') ?: 'llama3-8b-8192';
        $this->timeoutSeconds = (int)$timeoutSeconds;
    }

    public function getLastError()
    {
        return $_SESSION['groq_last_error'] ?? null;
    }

    private function setLastError($message, $details = null)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['groq_last_error'] = [
                'message' => $message,
                'details' => $details,
                'time' => date('Y-m-d H:i:s')
            ];
        }

        error_log('Groq error: ' . $message . ($details ? ' | ' . (is_string($details) ? $details : json_encode($details)) : ''));
    }

    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    public function generateRecommendations(array $strengths, array $improvements)
    {
        if (!$this->isConfigured()) {
            $this->setLastError('GROQ_API_KEY is missing or empty.');
            return null;
        }

        if (!function_exists('curl_init')) {
            $this->setLastError('PHP cURL extension is not enabled.');
            return null;
        }

        $prompt = $this->buildPrompt($strengths, $improvements);

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a marine crew recommendation assistant. Return concise practical recommendations, one per line.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->apiKey}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            $this->setLastError('Groq cURL error', $curlError);
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $serviceError = is_array($decoded)
                ? ($decoded['error']['message'] ?? $decoded['message'] ?? 'Unknown service error')
                : $response;

            $this->setLastError('Groq HTTP error ' . $httpCode, [
                'message' => $serviceError,
                'raw_response' => $decoded ?: $response
            ]);
            return null;
        }

        if (!is_array($decoded)) {
            $this->setLastError('Groq invalid JSON response', $response);
            return null;
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            $this->setLastError('Groq empty recommendation content', $decoded);
            return null;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['groq_last_error']);
        }

        return trim($content);
    }

    private function buildPrompt(array $strengths, array $improvements)
    {
        $strengthsText = empty($strengths) ? 'None identified' : implode(', ', array_values($strengths));
        $improvementsText = empty($improvements) ? 'None identified' : implode(', ', array_values($improvements));

        return "Generate 3-5 crew/vessel assignment recommendations based on exam analysis.\n"
            . "Strengths: {$strengthsText}\n"
            . "Areas for improvement: {$improvementsText}\n"
            . "Output format: one recommendation per line, no numbering.";
    }
}
