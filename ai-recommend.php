<?php

header('Content-Type: application/json');

$apiKey = getenv('GROQ_API_KEY') ?: 'PASTE_YOUR_GROQ_API_KEY';
$input = $_POST['message'] ?? 'Recommend a crew assignment';

if (!function_exists('curl_init')) {
    echo json_encode([
        'success' => false,
        'error' => 'PHP cURL extension is not enabled'
    ]);
    exit;
}

if (trim($apiKey) === '' || $apiKey === 'PASTE_YOUR_GROQ_API_KEY') {
    echo json_encode([
        'success' => false,
        'error' => 'Missing Groq API key. Set GROQ_API_KEY in environment or update ai-recommend.php'
    ]);
    exit;
}

$data = [
    'model' => 'llama3-8b-8192',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are a marine crew recommendation assistant.'
        ],
        [
            'role' => 'user',
            'content' => $input
        ]
    ]
];

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer {$apiKey}"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        'success' => false,
        'error' => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $errorMessage = $result['error']['message'] ?? ('Groq API HTTP error: ' . $httpCode);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ]);
    exit;
}

if (!is_array($result)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON response from Groq API'
    ]);
    exit;
}

$reply = $result['choices'][0]['message']['content'] ?? 'No response';

echo json_encode([
    'success' => true,
    'reply' => $reply
]);
