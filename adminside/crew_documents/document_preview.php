<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Unauthorized access');
}

require_once '../../config/database.php';

$docId = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;
$crewNo = isset($_GET['crew_no']) ? trim((string)$_GET['crew_no']) : '';
if ($docId <= 0) {
    http_response_code(400);
    exit('Invalid document');
}

try {
    $db = Database::getInstance();
    $doc = $db->fetchOne(
        "SELECT id, crew_no, file_name, file_path, file_type, status FROM crew_documents WHERE id = ? LIMIT 1",
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

    $fileName = (string)($doc['file_name'] ?? 'document');
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $inlineUrl = 'view_document.php?doc_id=' . urlencode((string)$docId);
    $downloadOriginalUrl = 'view_document.php?doc_id=' . urlencode((string)$docId) . '&download=1';

    if ($crewNo === '') {
        $crewNoFromDoc = trim((string)($doc['crew_no'] ?? ''));
        if ($crewNoFromDoc !== '') {
            $crewNo = $crewNoFromDoc;
        }
    }

    if ($crewNo === '' && !empty($_SESSION['last_crew_no'])) {
        $crewNo = trim((string)$_SESSION['last_crew_no']);
    }

    $backToDocumentsUrl = 'crew_documents.php';
    if ($crewNo !== '') {
        $backToDocumentsUrl .= '?id=' . urlencode($crewNo);
    }

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
    $downloadPdfUrl = '';
    if ($isPdf) {
        $downloadPdfUrl = 'view_document.php?doc_id=' . urlencode((string)$docId) . '&download=1';
    } elseif ($isOfficeDoc && $convertedPdfUrl !== '') {
        $downloadPdfUrl = 'view_document.php?doc_id=' . urlencode((string)$docId) . '&as=pdf&download=1';
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
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <style>
        .main__content {
            min-height: calc(100vh - 120px);
        }
        .preview-card {
            border-radius: 12px;
            border: 1px solid #ffffff56;
            background: #ffffff3b;
            overflow: hidden;
            min-height: calc(100vh - 180px);
            display: flex;
            flex-direction: column;
        }
        .topbar {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            padding: 12px 16px;
            background: #ffffff62;
            border-bottom: 1px solid #d1d5db;
        }
        .btn-download {
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: #126E82;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .btn-download.secondary {
            background: #FF8A4C;
        }
        .btn-close {
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: #6b7280;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .viewer-wrap {
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
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
            background: #ffffff4a;
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
    <div class="dashboard">
        <?php
        $GLOBALS['base_path'] = '../../';
        $GLOBALS['nav_path']  = '../';
        include '../../includes/sidebar.php';
        ?>

        <main class="main">
            <div class="main__content">
                <div class="preview-card">
                    <div class="topbar">
                        <a class="btn-download" href="<?php echo htmlspecialchars($downloadOriginalUrl); ?>">Download Original</a>
                        <?php if (!empty($downloadPdfUrl)): ?>
                            <a class="btn-download secondary" href="<?php echo htmlspecialchars($downloadPdfUrl); ?>">Download as PDF</a>
                        <?php endif; ?>
                                                <a class="btn-close" href="<?php echo htmlspecialchars($backToDocumentsUrl); ?>">Close</a>

                    </div>

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
                </div>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
