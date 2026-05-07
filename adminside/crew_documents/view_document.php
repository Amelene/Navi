<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Unauthorized access');
}

require_once '../../config/database.php';

$docId = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;
$forceDownload = isset($_GET['download']) && $_GET['download'] == '1';
$asPdf = isset($_GET['as']) && $_GET['as'] === 'pdf';
if ($docId <= 0) {
    http_response_code(400);
    exit('Invalid document');
}

try {
    $db = Database::getInstance();
    $doc = $db->fetchOne(
        "SELECT id, file_name, file_path, file_type, status FROM crew_documents WHERE id = ? LIMIT 1",
        [$docId]
    );

    $status = strtolower(trim((string)($doc['status'] ?? '')));
    if (
        !$doc ||
        !in_array($status, ['active', 'archived'], true)
    ) {
        http_response_code(404);
        exit('Document not found');
    }

    $relativePath = ltrim((string)$doc['file_path'], '/\\');
    $basePath = realpath(__DIR__ . '/../../');
    $absolutePath = realpath($basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

    if ($absolutePath === false || strpos($absolutePath, $basePath) !== 0 || !is_file($absolutePath)) {
        http_response_code(404);
        exit('File not found');
    }

    $fileName = (string)($doc['file_name'] ?? basename($absolutePath));
    $mimeType = (string)($doc['file_type'] ?? '');

    if ($asPdf) {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['doc', 'docx'], true)) {
            $targetDir = dirname($absolutePath);
            $pdfName = pathinfo($absolutePath, PATHINFO_FILENAME) . '.pdf';
            $pdfAbsolutePath = $targetDir . DIRECTORY_SEPARATOR . $pdfName;

            if (!is_file($pdfAbsolutePath) || filemtime($pdfAbsolutePath) < filemtime($absolutePath)) {
                $sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
                if (is_file($sofficePath)) {
                    $cmd = '"' . $sofficePath . '" --headless --convert-to pdf --outdir '
                        . escapeshellarg($targetDir) . ' ' . escapeshellarg($absolutePath) . ' 2>&1';
                    @exec($cmd, $out, $code);
                }
            }

            if (is_file($pdfAbsolutePath)) {
                $absolutePath = $pdfAbsolutePath;
                $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
                $mimeType = 'application/pdf';
            } else {
                http_response_code(404);
                exit('Converted PDF not found');
            }
        } elseif ($ext === 'pdf') {
            $mimeType = 'application/pdf';
        } else {
            http_response_code(400);
            exit('PDF preview not supported for this file');
        }
    }

    if ($mimeType === '' || $asPdf) {
        $detected = function_exists('mime_content_type') ? mime_content_type($absolutePath) : '';
        $mimeType = $detected ?: 'application/octet-stream';
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($absolutePath));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $fileName) . '"');
    if ($forceDownload) {
        header('X-Force-Download: 1');
    }
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');

    readfile($absolutePath);
    exit();
} catch (Exception $e) {
    error_log('view_document error: ' . $e->getMessage());
    http_response_code(500);
    exit('Server error');
}
