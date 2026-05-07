<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$original_display_errors = ini_get('display_errors');
ini_set('display_errors', '0');

try {
    require_once '../config/database.php';
    $db = Database::getInstance();
    ini_set('display_errors', $original_display_errors);
    
    function identifyCrewFromFilename($filename, $db) {
        $nameWithoutExt = strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $allCrew = $db->fetchAll("SELECT id, crew_no, first_name, last_name FROM crew_master");
        
        foreach ($allCrew as $crew) {
            $crew_no    = strtolower($crew['crew_no']);
            $first_name = strtolower($crew['first_name']);
            $last_name  = strtolower($crew['last_name']);
            preg_match('/(\d+)$/', $crew_no, $matches);
            $crew_number = $matches[1] ?? '';
            
            if (
                (!empty($crew_number) && strpos($nameWithoutExt, $crew_number) !== false) ||
                strpos($nameWithoutExt, $crew_no)    !== false ||
                strpos($nameWithoutExt, $first_name) !== false ||
                strpos($nameWithoutExt, $last_name)  !== false
            ) {
                return [
                    'crew_id'   => $crew['id'],
                    'crew_no'   => $crew['crew_no'],
                    'crew_name' => $crew['first_name'] . ' ' . $crew['last_name']
                ];
            }
        }
        return null;
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
            // assume month-day-year
            $month = (int)$m[1];
            $day   = (int)$m[2];
            $year  = (int)$m[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/', $rawDate, $m)) {
            $day = (int)$m[1];
            $month = strtotime($m[2] . ' 1 2000') ? (int)date('m', strtotime($m[2] . ' 1 2000')) : 0;
            $year = (int)$m[3];
            if ($month > 0 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        if (preg_match('/^([a-zA-Z]+)\s+(\d{1,2}),?\s+(\d{4})$/', $rawDate, $m)) {
            $month = strtotime($m[1] . ' 1 2000') ? (int)date('m', strtotime($m[1] . ' 1 2000')) : 0;
            $day   = (int)$m[2];
            $year  = (int)$m[3];
            if ($month > 0 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    function findDateNearExpiryKeywords($text) {
        if (!$text) return null;
        $text = strtolower((string)$text);

        if (preg_match('/(?:expiration|expiry|expire)\s*(?:date)?\s*[:\-]?\s*(\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/i', $text, $m)) {
            return normalizeDateToYmd(str_replace('/', '-', $m[1]));
        }

        if (preg_match('/(?:expiration|expiry|expire)\s*(?:date)?\s*[:\-]?\s*(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})/i', $text, $m)) {
            return normalizeDateToYmd($m[1]);
        }

        if (preg_match('/(?:expiration|expiry|expire)\s*(?:date)?\s*[:\-]?\s*([a-zA-Z]+\s+\d{1,2},?\s+\d{4})/i', $text, $m)) {
            return normalizeDateToYmd($m[1]);
        }

        if (preg_match('/(?:expiration|expiry|expire)\s*(?:date)?\s*[:\-]?\s*(\d{1,2}\s+[a-zA-Z]+\s+\d{4})/i', $text, $m)) {
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

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) return null;

        $plain = strip_tags($xml);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return findDateNearExpiryKeywords($plain);
    }

    function detectExpiryFromPdf($filePath) {
        $content = @file_get_contents($filePath);
        if (!$content) return null;

        // basic text extraction from PDF streams (best-effort only)
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
        if ($date) {
            return ['date' => $date, 'source' => 'filename'];
        }

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

    function detectExpiryViaPythonOcr($tmpFilePath, $originalFilename, $mimeType) {
        $pythonExe = 'C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python314\\python.exe';
        $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'ocr_detect_expiry.py';

        if (!file_exists($pythonExe) || !file_exists($scriptPath) || !function_exists('shell_exec')) {
            return null;
        }

        $cmd = '"' . $pythonExe . '" "' . $scriptPath . '" "' . $tmpFilePath . '" "' . ($originalFilename ?? '') . '" "' . ($mimeType ?? '') . '"';
        $output = shell_exec($cmd);
        if (!$output) return null;

        $decoded = json_decode($output, true);
        if (!is_array($decoded) || ($decoded['success'] ?? false) !== true) {
            return null;
        }

        $date = $decoded['expiration_date'] ?? null;
        if (!$date) return null;

        return [
            'date' => $date,
            'source' => 'ocr_' . ($decoded['expiry_source'] ?? 'python')
        ];
    }
    
    $crew_no     = $_POST['crew_no'] ?? '';
    $crew_id     = null;
    $uploaded_by = $_SESSION['user_id'] ?? 1;
    
    if (!empty($crew_no)) {
        $crew = $db->fetchOne("SELECT id FROM crew_master WHERE crew_no = ?", [$crew_no]);
        if ($crew) $crew_id = $crew['id'];
    }
    
    $uploadedFiles = [];
    $errors        = [];
    $allowedTypes  = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxFileSize   = 10 * 1024 * 1024; // 10MB
    $categories    = ['medical', 'contract', 'file201', 'nbi', 'yellowfever'];
    
    foreach ($categories as $category) {
        if (isset($_FILES[$category]) && !empty($_FILES[$category]['name'][0])) {
            $files       = $_FILES[$category];
            $fileCount   = count($files['name']);
            $expiryDates = $_POST[$category . '_expiry'] ?? [];
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = "Error uploading file: " . $files['name'][$i];
                    continue;
                }
                
                $fileName    = $files['name'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileSize    = $files['size'][$i];
                $fileType    = $files['type'][$i];
                
                $currentCrewId = $crew_id;
                $currentCrewNo = $crew_no;
                
                if (empty($currentCrewId)) {
                    $identifiedCrew = identifyCrewFromFilename($fileName, $db);
                    if ($identifiedCrew) {
                        $currentCrewId = $identifiedCrew['crew_id'];
                        $currentCrewNo = $identifiedCrew['crew_no'];
                    } else {
                        $errors[] = "File $fileName: Could not identify crew member from filename.";
                        continue;
                    }
                }
                
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "File $fileName: Invalid file type. Only PDF and DOC/DOCX allowed.";
                    continue;
                }
                if ($fileSize > $maxFileSize) {
                    $errors[] = "File $fileName: File size exceeds 10MB limit.";
                    continue;
                }
                
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $fileNameToken = $category;
                
                $categoryFolder = match($category) {
                    'medical'      => 'medical_certificates',
                    'contract'     => 'contract_files',
                    'file201'      => 'file_201',
                    'nbi'          => 'nbi',
                    'yellowfever'  => 'yellow_fever',
                    default        => 'other'
                };
                
                $expirationDate = $expiryDates[$i] ?? null;
                $docTypes = $_POST[$category . '_doc_type'] ?? [];
                $docType = $docTypes[$i] ?? null;

                $expirySource = null;
                $detected = null;

                if (!empty($expirationDate)) {
                    $normalizedManualDate = normalizeDateToYmd($expirationDate);
                    $expirationDate = $normalizedManualDate ?: $expirationDate;
                    $expirySource = 'manual';
                } else {
                    $ocrDetected = detectExpiryViaPythonOcr($fileTmpName, $fileName, $fileType);
                    if (!empty($ocrDetected['date'])) {
                        $expirationDate = $ocrDetected['date'];
                        $detected = ['date' => $expirationDate, 'source' => $ocrDetected['source']];
                        $expirySource = $ocrDetected['source'];
                    } else {
                        $detected = detectExpiryDate($fileName, $fileTmpName, $fileType);
                        if (!empty($detected['date'])) {
                            $expirationDate = $detected['date'];
                            $expirySource = 'auto_' . $detected['source'];
                        }
                    }
                }

                if ($category === 'yellowfever' && empty($expirationDate)) {
                    $errors[] = "File $fileName: Yellow Fever requires expiration date (manual or detectable from filename/content).";
                    continue;
                }

                if ($category === 'nbi' && empty($expirationDate)) {
                    $errors[] = "File $fileName: NBI requires expiration date (manual or detectable from filename/content).";
                    continue;
                }

                if ($category === 'file201') {
                    $nameForDetection = strtolower(pathinfo($fileName, PATHINFO_FILENAME));

                    if (strpos($nameForDetection, 'sea services') !== false || strpos($nameForDetection, 'seaservices') !== false) {
                        $docType = 'sea services';
                    } elseif (strpos($nameForDetection, 'passport') !== false) {
                        $docType = 'Passport';
                    } elseif (strpos($nameForDetection, 'nbi') !== false) {
                        $docType = 'NBI';
                    } elseif (strpos($nameForDetection, 'sirb') !== false) {
                        $docType = 'SIRB';
                    } elseif (strpos($nameForDetection, 'seaman book') !== false || strpos($nameForDetection, 'seamanbook') !== false) {
                        $docType = 'Seaman book';
                    } elseif (strpos($nameForDetection, 'd-coc') !== false || strpos($nameForDetection, 'dcoc') !== false) {
                        $docType = 'D-COC';
                    } elseif (strpos($nameForDetection, 'sid') !== false) {
                        $docType = 'SID';
                    } elseif (strpos($nameForDetection, 'marina') !== false) {
                        $docType = 'Marina License ID';
                    } elseif (strpos($nameForDetection, 'philhealth') !== false) {
                        $docType = 'Philhealth';
                    } elseif (strpos($nameForDetection, 'sss') !== false) {
                        $docType = 'SSS';
                    } elseif (strpos($nameForDetection, 'pagibig') !== false) {
                        $docType = 'pagibig';
                    } elseif (strpos($nameForDetection, 'tin') !== false) {
                        $docType = 'tin id';
                    } elseif (strpos($nameForDetection, 'bank') !== false) {
                        $docType = 'bank details';
                    } else {
                        $docType = 'Certificates';
                    }

                }

                $dbCategory = match($category) {
                        'medical'      => 'medical_certificate',
                        'contract'     => 'contract_file',
                        'file201'      => 'file_201',
                        'nbi'          => 'nbi',
                        'yellowfever'  => 'yellow_fever',
                        default        => 'medical_certificate'
                    };

                    // For 201 uploads, document_category should reflect detected document type
                    if ($category === 'file201' || $category === 'file_201') {
                        $normalizedDocType = strtolower(trim((string)$docType));

                        if ($normalizedDocType === 'sea services' || $normalizedDocType === 'seaservices') {
                            $dbCategory = 'sea_services';
                        } elseif ($normalizedDocType === 'passport') {
                            $dbCategory = 'passport';
                        } elseif ($normalizedDocType === 'nbi') {
                            $dbCategory = 'nbi';
                        } elseif ($normalizedDocType === 'sirb') {
                            $dbCategory = 'sirb';
                        } elseif ($normalizedDocType === 'seaman book') {
                            $dbCategory = 'seaman_book';
                        } elseif ($normalizedDocType === 'd-coc') {
                            $dbCategory = 'd_coc';
                        } elseif ($normalizedDocType === 'sid') {
                            $dbCategory = 'sid';
                        } elseif ($normalizedDocType === 'marina license id') {
                            $dbCategory = 'marina_license_id';
                        } elseif ($normalizedDocType === 'philhealth') {
                            $dbCategory = 'philhealth';
                        } elseif ($normalizedDocType === 'sss') {
                            $dbCategory = 'sss';
                        } elseif ($normalizedDocType === 'pagibig') {
                            $dbCategory = 'pagibig';
                        } elseif ($normalizedDocType === 'tin id') {
                            $dbCategory = 'tin_id';
                        } elseif ($normalizedDocType === 'bank details') {
                            $dbCategory = 'bank_details';
                        } elseif ($normalizedDocType === 'certificates' || $normalizedDocType === 'certificate') {
                            $dbCategory = 'certificates';
                        } else {
                            $dbCategory = 'file_201';
                        }
                    }

                    if ($category === 'file201' || $category === 'file_201') {
                        $normalizedDocType = strtolower(trim((string)$docType));
                        if ($normalizedDocType === 'sea services' || $normalizedDocType === 'seaservices') {
                            $fileNameToken = 'sea_services';
                        } elseif ($normalizedDocType === 'passport') {
                            $fileNameToken = 'passport';
                        } elseif ($normalizedDocType === 'nbi') {
                            $fileNameToken = 'nbi';
                        } elseif ($normalizedDocType === 'sirb') {
                            $fileNameToken = 'sirb';
                        } elseif ($normalizedDocType === 'seaman book') {
                            $fileNameToken = 'seaman_book';
                        } elseif ($normalizedDocType === 'd-coc') {
                            $fileNameToken = 'd_coc';
                        } elseif ($normalizedDocType === 'sid') {
                            $fileNameToken = 'sid';
                        } elseif ($normalizedDocType === 'marina license id') {
                            $fileNameToken = 'marina_license_id';
                        } elseif ($normalizedDocType === 'philhealth') {
                            $fileNameToken = 'philhealth';
                        } elseif ($normalizedDocType === 'sss') {
                            $fileNameToken = 'sss';
                        } elseif ($normalizedDocType === 'pagibig') {
                            $fileNameToken = 'pagibig';
                        } elseif ($normalizedDocType === 'tin id') {
                            $fileNameToken = 'tin_id';
                        } elseif ($normalizedDocType === 'bank details') {
                            $fileNameToken = 'bank_details';
                        } elseif ($normalizedDocType === 'certificates' || $normalizedDocType === 'certificate') {
                            $fileNameToken = 'certificates';
                        } else {
                            $fileNameToken = 'file_201';
                        }
                    }

                $safeToken = preg_replace('/[^a-z0-9_]+/i', '_', (string)$fileNameToken);
                $safeToken = trim((string)$safeToken, '_');
                if ($safeToken === '') {
                    $safeToken = 'document';
                }

                // Keep generated filename descriptive like medical/contract style (crewno_category_timestamp_uid.ext)
                $uniqueFileName = $currentCrewNo . '_' . $safeToken . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadDir  = "../uploads/crew_documents/$categoryFolder/";
                $storedPath = "uploads/crew_documents/$categoryFolder/" . $uniqueFileName;

                if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

                if (!move_uploaded_file($fileTmpName, $uploadDir . $uniqueFileName)) {
                    $errors[] = "Failed to upload file: $fileName";
                    continue;
                }

                $storedFileName = $fileName;
                
                // Ensure category is never blank
                if (empty($dbCategory)) {
                    $dbCategory = 'file_201';
                }

                $db->execute(
                        "INSERT INTO crew_documents 
                         (crew_id, crew_no, document_category, file_name, file_path, file_size, file_type, expiration_date, uploaded_by, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                        [$currentCrewId, $currentCrewNo, $dbCategory, $storedFileName, $storedPath, $fileSize, $fileType, $expirationDate, $uploaded_by]
                    );

                    // Hard repair: update same uploaded file row even if category became blank unexpectedly
                    $db->execute(
                        "UPDATE crew_documents
                         SET document_category = ?
                         WHERE crew_no = ? 
                           AND file_name = ? 
                           AND file_path = ?
                           AND (document_category IS NULL OR document_category = '')",
                        [$dbCategory, $currentCrewNo, $storedFileName, $storedPath]
                    );

                    // Backfill existing old rows with blank category for this crew based on filename
                    $db->execute(
                        "UPDATE crew_documents
                         SET document_category = CASE
                             WHEN LOWER(file_name) LIKE '%sea services%' OR LOWER(file_name) LIKE '%seaservices%' THEN 'sea_services'
                             WHEN LOWER(file_name) LIKE '%passport%' THEN 'passport'
                             WHEN LOWER(file_name) LIKE '%nbi%' THEN 'nbi'
                             WHEN LOWER(file_name) LIKE '%sirb%' THEN 'sirb'
                             WHEN LOWER(file_name) LIKE '%medical%' THEN 'medical_certificate'
                             WHEN LOWER(file_name) LIKE '%contract%' THEN 'contract_file'
                             WHEN LOWER(file_name) LIKE '%embark%' THEN 'embarkation_file'
                             WHEN LOWER(file_name) LIKE '%seaman book%' OR LOWER(file_name) LIKE '%seamanbook%' THEN 'seaman_book'
                             WHEN LOWER(file_name) LIKE '%d-coc%' OR LOWER(file_name) LIKE '%dcoc%' THEN 'd_coc'
                             WHEN LOWER(file_name) LIKE '%sid%' THEN 'sid'
                             WHEN LOWER(file_name) LIKE '%marina%' THEN 'marina_license_id'
                             WHEN LOWER(file_name) LIKE '%philhealth%' THEN 'philhealth'
                             WHEN LOWER(file_name) LIKE '%sss%' THEN 'sss'
                             WHEN LOWER(file_name) LIKE '%pagibig%' THEN 'pagibig'
                             WHEN LOWER(file_name) LIKE '%tin%' THEN 'tin_id'
                             WHEN LOWER(file_name) LIKE '%bank%' THEN 'bank_details'
                             WHEN LOWER(file_name) LIKE '%certificate%' THEN 'certificates'
                             ELSE 'file_201'
                         END
                         WHERE crew_no = ?
                           AND (document_category IS NULL OR document_category = '')",
                        [$currentCrewNo]
                    );
                    
                $uploadedFiles[] = [
                    'category'             => $category,
                    'file_name'            => $storedFileName,
                    'file_path'            => $storedPath,
                    'crew_no'              => $currentCrewNo,
                    'expiration_date'      => $expirationDate,
                    'expiry_source'        => $expirySource ?: 'none',
                    'expiry_detected_from' => $detected['source'] ?? null,
                    'doc_type'             => $docType
                ];
            }
        }
    }
    
    ob_clean();
    
    if (count($uploadedFiles) > 0) {
        echo json_encode([
            'success'        => true,
            'message'        => count($uploadedFiles) . ' file(s) uploaded successfully',
            'uploaded_files' => $uploadedFiles,
            'errors'         => $errors
        ], JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No files were uploaded',
            'errors'  => $errors
        ], JSON_UNESCAPED_SLASHES);
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_SLASHES);
}

ob_end_flush();
?>
