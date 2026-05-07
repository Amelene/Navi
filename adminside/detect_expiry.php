<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

function normalizeDateToYmd($rawDate) {
    if (!$rawDate) return null;
    $rawDate = trim((string)$rawDate);
    if ($rawDate === '') return null;

    $rawDate = str_replace(['.', '_'], ['-', '-'], $rawDate);

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        return $rawDate;
    }

    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $rawDate, $m)) {
        $month = (int)$m[1];
        $day   = (int)$m[2];
        $year  = (int)$m[3];
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    if (preg_match('/^([a-zA-Z]+)\s+(\d{1,2}),?\s+(\d{4})$/', $rawDate, $m)) {
        $monthMap = [
            'january'=>1,'jan'=>1,'february'=>2,'feb'=>2,'march'=>3,'mar'=>3,'april'=>4,'apr'=>4,
            'may'=>5,'june'=>6,'jun'=>6,'july'=>7,'jul'=>7,'august'=>8,'aug'=>8,'september'=>9,'sep'=>9,'sept'=>9,
            'october'=>10,'oct'=>10,'november'=>11,'nov'=>11,'december'=>12,'dec'=>12
        ];
        $mKey = strtolower(trim($m[1]));
        $month = $monthMap[$mKey] ?? 0;
        $day   = (int)$m[2];
        $year  = (int)$m[3];
        if ($month > 0 && checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/', $rawDate, $m)) {
        $monthMap = [
            'january'=>1,'jan'=>1,'february'=>2,'feb'=>2,'march'=>3,'mar'=>3,'april'=>4,'apr'=>4,
            'may'=>5,'june'=>6,'jun'=>6,'july'=>7,'jul'=>7,'august'=>8,'aug'=>8,'september'=>9,'sep'=>9,'sept'=>9,
            'october'=>10,'oct'=>10,'november'=>11,'nov'=>11,'december'=>12,'dec'=>12
        ];
        $day = (int)$m[1];
        $mKey = strtolower(trim($m[2]));
        $month = $monthMap[$mKey] ?? 0;
        $year = (int)$m[3];
        if ($month > 0 && checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    return null;
}

function findDateNearExpiryKeywords($text) {
    if (!$text) return null;
    $text = strtolower((string)$text);
    $text = preg_replace('/\s+/', ' ', $text);

    // 1) keyword then date
    if (preg_match('/(?:expiration|expiry|expire|valid until|validity)\s*(?:date)?\s*[:\-]?\s*(\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/i', $text, $m)) {
        return normalizeDateToYmd(str_replace('/', '-', $m[1]));
    }
    if (preg_match('/(?:expiration|expiry|expire|valid until|validity)\s*(?:date)?\s*[:\-]?\s*(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})/i', $text, $m)) {
        return normalizeDateToYmd($m[1]);
    }
    if (preg_match('/(?:expiration|expiry|expire|valid until|validity)\s*(?:date)?\s*[:\-]?\s*([a-zA-Z]+\s+\d{1,2},?\s+\d{4})/i', $text, $m)) {
        return normalizeDateToYmd($m[1]);
    }
    if (preg_match('/(?:expiration|expiry|expire|valid until|validity)\s*(?:date)?\s*[:\-]?\s*(\d{1,2}\s+[a-zA-Z]+\s+\d{4})/i', $text, $m)) {
        return normalizeDateToYmd($m[1]);
    }

    // 2) date then keyword
    if (preg_match('/(\d{4}[-\/]\d{1,2}[-\/]\d{1,2})\s*(?:-|to|until)?\s*(?:expiration|expiry|expire|valid until|validity)/i', $text, $m)) {
        return normalizeDateToYmd(str_replace('/', '-', $m[1]));
    }
    if (preg_match('/(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})\s*(?:-|to|until)?\s*(?:expiration|expiry|expire|valid until|validity)/i', $text, $m)) {
        return normalizeDateToYmd($m[1]);
    }
    if (preg_match('/([a-zA-Z]+\s+\d{1,2},?\s+\d{4})\s*(?:-|to|until)?\s*(?:expiration|expiry|expire|valid until|validity)/i', $text, $m)) {
        return normalizeDateToYmd($m[1]);
    }

    return null;
}

function detectExpiryFromFilename($filename) {
    $base = strtolower(pathinfo((string)$filename, PATHINFO_FILENAME));
    return findDateNearExpiryKeywords($base);
}

function detectExpiryFromDocx($filePath) {
    if (!class_exists('ZipArchive')) return null;
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return null;

    $allText = '';

    $targetParts = ['word/document.xml'];
    for ($i = 1; $i <= 10; $i++) {
        $targetParts[] = "word/header{$i}.xml";
        $targetParts[] = "word/footer{$i}.xml";
    }
    $targetParts[] = 'word/footnotes.xml';
    $targetParts[] = 'word/endnotes.xml';
    $targetParts[] = 'word/comments.xml';

    foreach ($targetParts as $part) {
        $xml = $zip->getFromName($part);
        if (!$xml) continue;

        $xml = str_replace(['</w:t>', '</w:p>', '</w:tr>', '</w:tc>', '</w:tbl>', '<w:tab/>', '<w:br/>'], [' ', ' ', ' ', ' ', ' ', ' ', ' '], $xml);
        $plain = strip_tags($xml);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain);

        $allText .= ' ' . $plain;
    }

    $zip->close();

    if (trim($allText) === '') return null;

    $detected = findDateNearExpiryKeywords($allText);
    if ($detected) return $detected;

    // fallback: if keyword exists anywhere, pick first valid date anywhere
    $lower = strtolower($allText);
    if (preg_match('/(expiration|expiry|expire|valid until|validity)/i', $lower)) {
        $patterns = [
            '/\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/',
            '/\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/',
            '/[a-zA-Z]+\s+\d{1,2},?\s+\d{4}/'
        ];
        foreach ($patterns as $p) {
            if (preg_match_all($p, $allText, $matches) && !empty($matches[0])) {
                foreach ($matches[0] as $raw) {
                    $ymd = normalizeDateToYmd($raw);
                    if ($ymd) return $ymd;
                }
            }
        }
    }

    return null;
}

function detectExpiryFromPdf($filePath) {
    $content = @file_get_contents($filePath);
    if (!$content) return null;

    $text = '';
    if (preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches)) {
        $text = implode(' ', $matches[1]);
    } else {
        $text = $content;
    }

    $text = preg_replace('/[^[:print:]\s]/u', ' ', $text);
    return findDateNearExpiryKeywords($text);
}

function detectExpiryDate($originalFilename, $tmpFilePath, $mimeType) {
    $date = detectExpiryFromFilename($originalFilename);
    if ($date) return ['date' => $date, 'source' => 'filename'];

    $mimeType = strtolower((string)$mimeType);

    if (strpos($mimeType, 'wordprocessingml') !== false || preg_match('/\.docx$/i', $originalFilename)) {
        $date = detectExpiryFromDocx($tmpFilePath);
        if ($date) return ['date' => $date, 'source' => 'docx_content'];
    }

    if (strpos($mimeType, 'pdf') !== false || preg_match('/\.pdf$/i', $originalFilename)) {
        $date = detectExpiryFromPdf($tmpFilePath);
        if ($date) return ['date' => $date, 'source' => 'pdf_content'];
    }

    return ['date' => null, 'source' => null];
}

try {
    if (!isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit();
    }

    $file = $_FILES['file'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error']);
        exit();
    }

    $pythonExe = 'C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';
    $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'ocr_detect_expiry.py';

    $ocrResult = null;
    if (file_exists($pythonExe) && file_exists($scriptPath)) {
        $cmd = '"' . $pythonExe . '" "' . $scriptPath . '" "' . $file['tmp_name'] . '" "' . ($file['name'] ?? '') . '" "' . ($file['type'] ?? '') . '"';
        $output = shell_exec($cmd);
        if ($output) {
            $decoded = json_decode($output, true);
            if (is_array($decoded) && ($decoded['success'] ?? false) === true) {
                $ocrResult = $decoded;
            }
        }
    }

    if ($ocrResult && !empty($ocrResult['expiration_date'])) {
        echo json_encode([
            'success' => true,
            'expiration_date' => $ocrResult['expiration_date'],
            'expiry_source' => 'ocr_' . ($ocrResult['expiry_source'] ?? 'python'),
            'debug' => [
                'python_exists' => file_exists($pythonExe),
                'script_exists' => file_exists($scriptPath),
                'shell_exec_enabled' => function_exists('shell_exec'),
                'cmd' => $cmd ?? null,
                'ocr_raw' => $output ?? null
            ]
        ]);
        exit();
    }

    $result = detectExpiryDate($file['name'] ?? '', $file['tmp_name'] ?? '', $file['type'] ?? '');

    echo json_encode([
        'success' => true,
        'expiration_date' => $result['date'],
        'expiry_source' => $result['source'] ? ('auto_' . $result['source']) : 'none',
        'debug' => [
            'python_exists' => file_exists($pythonExe),
            'script_exists' => file_exists($scriptPath),
            'shell_exec_enabled' => function_exists('shell_exec'),
            'cmd' => $cmd ?? null,
            'ocr_raw' => $output ?? null,
            'php_fallback' => $result
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
