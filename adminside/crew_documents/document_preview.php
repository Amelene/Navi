<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Unauthorized access');
}

require_once '../../config/database.php';

$docId = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;
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

    if (!$doc || ($doc['status'] ?? '') !== 'active') {
        http_response_code(404);
        exit('Document not found');
    }

    $fileName = (string)($doc['file_name'] ?? 'document');
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $inlineUrl = 'view_document.php?doc_id=' . urlencode((string)$docId);

    // Build absolute URL for Office Online viewer
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['REQUEST_URI'] ?? ''), '/\\');
    $absoluteInlineUrl = $scheme . '://' . $host . $dir . '/' . $inlineUrl;

    $isPdf = ($ext === 'pdf');
    $isOfficeDoc = in_array($ext, ['doc', 'docx'], true);
    $isOfficeEmbedDoc = in_array($ext, ['xls', 'xlsx', 'ppt', 'pptx'], true);

    $officeEmbedUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($absoluteInlineUrl);

    $convertedPdfUrl = '';
    $conversionMessage = '';

    if ($isOfficeDoc) {
        $relativePath = ltrim((string)$doc['file_path'], '/\\');
        $basePath = realpath(__DIR__ . '/../../');
        $absolutePath = realpath($basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        if ($absolutePath && is_file($absolutePath)) {
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
                $convertedPdfUrl = 'view_document.php?doc_id=' . urlencode((string)$docId) . '&as=pdf';
            } else {
                $conversionMessage = 'Local PDF conversion not available for this file yet.';
            }
        } else {
            $conversionMessage = 'Source file not found for conversion.';
        }
    }
} catch (Exception $e) {
    error_log('document_preview error: ' . $e->getMessage());
    http_response_code(500);
    exit('Server error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Preview - <?php echo htmlspecialchars($fileName); ?></title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
        }
        .viewer-wrap {
            height: 100%;
            width: 100%;
            background: #e5e7eb;
        }
        .viewer {
            height: 100%;
            width: 100%;
            border: 0;
            background: #fff;
        }
        .pdfjs-wrap {
            height: 100%;
            width: 100%;
            overflow: auto;
            background: #d1d5db;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 16px;
            box-sizing: border-box;
        }
        .pdfjs-canvas {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
            max-width: 100%;
            height: auto;
        }
        .fallback {
            padding: 24px;
        }
        .fallback p {
            margin: 0 0 12px;
        }
    </style>
</head>
<body>
    <div class="viewer-wrap">
        <?php if ($isPdf || ($isOfficeDoc && $convertedPdfUrl !== '')): ?>
            <?php $pdfRenderUrl = $isPdf ? $inlineUrl : $convertedPdfUrl; ?>
            <div id="pdfjsWrap" class="pdfjs-wrap">
                <canvas id="pdfCanvas" class="pdfjs-canvas"></canvas>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.6.82/pdf.min.mjs" type="module"></script>
            <script type="module">
                const url = <?php echo json_encode($pdfRenderUrl); ?>;
                const canvas = document.getElementById('pdfCanvas');
                const ctx = canvas.getContext('2d');

                import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.6.82/pdf.min.mjs';
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.6.82/pdf.worker.min.mjs';

                const render = async () => {
                    const loadingTask = pdfjsLib.getDocument({ url });
                    const pdf = await loadingTask.promise;
                    const page = await pdf.getPage(1);

                    const baseViewport = page.getViewport({ scale: 1 });
                    const containerWidth = Math.max(320, Math.min(window.innerWidth - 64, 1200));
                    const scale = containerWidth / baseViewport.width;
                    const viewport = page.getViewport({ scale });

                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    await page.render({ canvasContext: ctx, viewport }).promise;
                };

                render().catch((err) => {
                    console.error('PDF.js render error:', err);
                    const wrap = document.getElementById('pdfjsWrap');
                    if (wrap) {
                        wrap.innerHTML = '<div style="padding:24px;font-family:Arial,sans-serif;">Preview rendering failed. Please try again.</div>';
                    }
                });
            </script>
        <?php elseif ($isOfficeDoc): ?>
            <div class="fallback">
                <p><?php echo htmlspecialchars($conversionMessage ?: 'DOC/DOCX preview is preparing.'); ?></p>
                <p>Preview-only mode active.</p>
            </div>
        <?php elseif ($isOfficeEmbedDoc): ?>
            <iframe class="viewer" src="<?php echo htmlspecialchars($officeEmbedUrl); ?>"></iframe>
        <?php else: ?>
            <div class="fallback">
                <p>Preview is not available for this file type.</p>
                <p>Preview-only mode active.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
