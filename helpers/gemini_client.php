<?php

class GeminiClient
{
    private $apiKey;
    private $model;
    private $timeoutSeconds;
    private $pythonAiUrl;

    public function __construct($apiKey = null, $model = null, $timeoutSeconds = 20)
    {
        $this->apiKey = $apiKey ?: getenv('GEMINI_API_KEY');
        $this->model = $model ?: getenv('GEMINI_MODEL') ?: 'gemini-flash-latest';
        $this->timeoutSeconds = (int)$timeoutSeconds;
        $this->pythonAiUrl = rtrim((string)(getenv('PYTHON_AI_URL') ?: 'http://127.0.0.1:5001'), '/');
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
        return !empty($this->pythonAiUrl);
    }

    public function generateRecommendations(array $strengths, array $improvements)
    {
        if (!$this->isConfigured()) {
            $this->setLastError('PYTHON_AI_URL is missing or empty.');
            return null;
        }

        if (!function_exists('curl_init')) {
            $this->setLastError('PHP cURL extension is not enabled.');
            return null;
        }

        $url = $this->pythonAiUrl . '/recommendations';
        $payload = [
            'provider' => 'gemini',
            'strengths' => array_values($strengths),
            'improvements' => array_values($improvements),
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
            $this->setLastError('Python AI service cURL error', $curlError);
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $serviceError = is_array($decoded)
                ? ($decoded['error']['message'] ?? $decoded['message'] ?? 'Unknown service error')
                : $response;

            $this->setLastError('Python AI service HTTP error ' . $httpCode, [
                'message' => $serviceError,
                'raw_response' => $decoded ?: $response
            ]);
            return null;
        }

        if (!is_array($decoded)) {
            $this->setLastError('Python AI service invalid JSON response', $response);
            return null;
        }

        if (!($decoded['ok'] ?? false)) {
            $this->setLastError(
                'Python AI service returned failure',
                $decoded['error'] ?? $decoded
            );
            return null;
        }

        $items = $decoded['recommendations'] ?? [];
        if (!is_array($items)) {
            $this->setLastError('Python AI service returned invalid recommendations payload', $decoded);
            return null;
        }

        $lines = [];
        foreach ($items as $item) {
            if (is_string($item) && trim($item) !== '') {
                $lines[] = trim($item);
            }
        }

        if (empty($lines)) {
            $this->setLastError('Python AI service returned empty recommendations', $decoded);
            return null;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['gemini_last_error']);
        }

        return implode("\n", $lines);
    }
}
