<?php
session_start();
require_once '../../config/database.php';

$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php'
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadLoaded = true;
        break;
    }
}

if (!$autoloadLoaded) {
    error_log('Certificate PDF: vendor autoload not found. Checked: ' . implode(' | ', $autoloadPaths));
}

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$download_mode = isset($_GET['download']) && $_GET['download'] == '1';
$download_file_mode = isset($_GET['download_file']) && $_GET['download_file'] == '1';
$pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';

try {
    $db = Database::getInstance();

    $examQuery = "SELECT 
                    ea.id,
                    ea.crew_id,
                    CONCAT(cm.first_name, ' ', cm.last_name) as crew_name,
                    ec.department,
                    ec.category,
                    ec.passing_score,
                    ea.score,
                    ea.time_taken,
                    ea.start_time,
                    CASE 
                        WHEN ea.score >= ec.passing_score THEN 'PASSED'
                        ELSE 'FAILED'
                    END as result_status
                   FROM exam_attempts ea
                   INNER JOIN crew_master cm ON ea.crew_id = cm.id
                   INNER JOIN exam_categories ec ON ea.exam_category_id = ec.id
                   WHERE ea.id = ?";

    $exam = $db->fetchOne($examQuery, [$attempt_id]);

    if (!$exam) {
        header('Location: ../tests.php');
        exit();
    }

    $minutes              = floor($exam['time_taken'] / 60);
    $seconds              = $exam['time_taken'] % 60;
    $time_taken_formatted = sprintf('%02d:%02d', $minutes, $seconds);

} catch (Exception $e) {
    error_log("Error fetching exam details for certificate: " . $e->getMessage());
    header('Location: ../tests.php');
    exit();
}
if ($pdf_mode) {
    error_log('Certificate PDF debug: autoloadLoaded=' . ($autoloadLoaded ? '1' : '0') . ', dompdfClass=' . (class_exists('Dompdf\Dompdf') ? '1' : '0') . ', include_path=' . get_include_path());
    if (class_exists('Dompdf\Dompdf')) {
        $logoPath = realpath('../../assets/image/logo.png');
        $logoDataUri = '';
        if ($logoPath && file_exists($logoPath)) {
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoDataUri = 'data:image/' . $logoType . ';base64,' . $logoData;
        }

        $name = htmlspecialchars($exam['crew_name']);
        $department = htmlspecialchars($exam['department']);
        $category = htmlspecialchars($exam['category']);
        $testDate = date('m-d-Y', strtotime($exam['start_time']));
        $score = number_format($exam['score'], 0) . '%';
        $status = htmlspecialchars($exam['result_status']);
        $passing = htmlspecialchars($exam['passing_score']) . '% passing score';

        $completionText = $exam['result_status'] === 'PASSED'
            ? 'has successfully completed NSC 1.0 Exam in'
            : 'has completed NSC 1.0 Exam in';

        $description = $exam['result_status'] === 'PASSED'
            ? 'This certificate confirms that the awardee has demonstrated the required level of competency.'
            : 'This certificate confirms completion of the exam. Further training is recommended.';

        $scoreColor = $exam['result_status'] === 'PASSED' ? '#27ae60' : '#e74c3c';

    $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { 
                    size: A4 portrait; 
                    margin: 0; 
                }
                html, body { 
                    margin: 0; 
                    padding: 0;
                    width: 210mm;
                    height: 297mm;
                    font-family: DejaVu Sans, Arial, sans-serif; 
                    color: #17345f;
                    background: #ffffff;
                }
                
                /* Outer white page wrapper and orange page frame */
                .certificate-container {
                    position: relative;
                    width: 210mm;
                    height: 297mm;
                    box-sizing: border-box;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    page-break-inside: avoid;
                    page-break-after: avoid;
                }

                .outer-border {
                    width: calc(210mm - 8mm);
                    height: calc(297mm - 8mm);
                    box-sizing: border-box;
                    border: 1.6mm solid #ff7a3d;
                    border-radius: 6mm;
                    margin: 4mm;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    background: #ffffff;
                    page-break-inside: avoid;
                    page-break-after: avoid;
                }

                .inner-border {
                    width: 100%;
                    height: 100%;
                    position: relative;
                    box-sizing: border-box;
                    padding: 8mm 8mm 6mm;
                    background-color: white;
                    overflow: hidden;
                    page-break-inside: avoid;
                    page-break-after: avoid;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }

                .corner-design {
                    position: absolute;
                    top: -1px;
                    right: -1px;
                    width: 40mm;
                    height: 40mm;
                    background-color: #0a7d98;
                    clip-path: polygon(100% 0, 0 0, 100% 100%);
                    z-index: 10;
                }
                
                /* Alternatibo kung hindi gumagana clip-path sa Dompdf version mo */
                .corner-legacy {
                    position: absolute;
                    top: 0;
                    right: 0;
                    width: 0;
                    height: 0;
                    border-style: solid;
                    border-width: 0 32mm 32mm 0;
                    border-color: transparent #0a7d98 transparent transparent;
                }

                .logo { margin-bottom: 5mm; }
                .logo img { height: 16mm; width: auto; }

                .content-center {
                    text-align: center;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    flex: 1;
                }

                .title {
                    font-size: 42px;
                    font-family: DejaVu Serif, serif;
                    font-weight: bold;
                    color: #17345f;
                    margin: 0;
                    letter-spacing: 1px;
                }

                .company {
                    font-size: 14px;
                    font-weight: bold;
                    letter-spacing: 2.5px;
                    margin: 1.5mm 0 5mm 0;
                    color: #17345f;
                }

                .name {
                    font-size: 30px;
                    font-weight: bold;
                    color: #17345f;
                    margin-bottom: 1mm;
                    text-transform: uppercase;
                }

                .underline {
                    width: 115mm;
                    height: 0.5mm;
                    background: #17345f;
                    margin: 0 auto 3.5mm auto;
                }

                .completion {
                    font-size: 12px;
                    font-style: italic;
                    margin-bottom: 7mm;
                }

                .details-table {
                    width: 72%;
                    margin: 0 auto 3mm auto;
                    font-size: 12px;
                    border-collapse: collapse;
                }

                .details-table td {
                    padding: 4px 0;
                    color: #17345f;
                }

                .label { font-weight: bold; width: 45%; text-align: left; }
                .value { font-weight: normal; width: 55%; text-align: left; }

                .divider {
                    border-top: 0.2mm solid #e0e0e0;
                    width: 90%;
                    margin: 6mm auto;
                }

                .score-section {
                    width: 90%;
                    margin: 0 auto 6mm auto;
                }

                .score-box {
                    display: inline-block;
                    width: 48%;
                    vertical-align: top;
                    text-align: center;
                }

                .score-main {
                    font-size: 34px;
                    font-weight: bold;
                    color: '.$scoreColor.';
                    margin: 0;
                }

                .score-sub {
                    font-size: 13px;
                    font-weight: bold;
                    color: #555;
                    margin-top: 2px;
                }

                .description {
                    font-size: 12px;
                    line-height: 1.35;
                    width: 92%;
                    margin: 0 auto 8mm auto;
                    color: #17345f;
                }

                .signatures {
                    width: 94%;
                    margin: 0 auto 2mm auto;
                }

                .sig-line {
                    border-top: 0.4mm solid #17345f;
                    width: 58mm;
                    margin: 0 auto 1mm auto;
                }

                .sig-label {
                    font-size: 13px;
                    font-weight: bold;
                }

                .footer-tagline {
                    position: relative;
                    margin-top: 4mm;
                    text-align: center;
                    font-weight: bold;
                    color: #0a7d98;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="certificate-container">
                <div class="outer-border">
                    <div class="inner-border">
                        <div class="corner-legacy"></div>

                    <div class="logo">
                        '.($logoDataUri ? '<img src="'.$logoDataUri.'">' : '').'
                    </div>

                    <div class="content-center">
                        <div class="title">Certificate</div>
                        <div class="company">NAVI SHIPPING</div>

                        <div class="name">'.$name.'</div>
                        <div class="underline"></div>

                        <div class="completion">'.$completionText.'</div>

                        <table class="details-table">
                            <tr><td class="label">Department:</td><td class="value">'.$department.'</td></tr>
                            <tr><td class="label">Test performed:</td><td class="value">'.$testDate.'</td></tr>
                            <tr><td class="label">Category:</td><td class="value">'.$category.'</td></tr>
                            <tr><td class="label">Test time used:</td><td class="value">'.$time_taken_formatted.'</td></tr>
                        </table>

                        <div class="divider"></div>

                        <div class="score-section">
                            <div class="score-box">
                                <div class="score-main">'.$score.'</div>
                                <div class="score-sub">Overall Score</div>
                            </div>
                            <div class="score-box" style="border-left: 0.2mm solid #ccc;">
                                <div class="score-main">'.$status.'</div>
                                <div class="score-sub">'.$passing.'</div>
                            </div>
                        </div>

                        <div class="description">'.$description.'</div>

                        <table class="signatures" width="100%">
                            <tr>
                                <td align="center">
                                    <div class="sig-line"></div>
                                    <div class="sig-label">Signature of Assessor</div>
                                </td>
                                <td align="center">
                                    <div class="sig-line"></div>
                                    <div class="sig-label">Signature of Candidate</div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="footer-tagline">
                        NAVIgating Excellence Towards Innovative Shipping
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream('certificate_' . $attempt_id . '.pdf', ['Attachment' => true]);
        exit();
    }

    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "PDF mode active but Dompdf class is missing.\n";
        echo "Checked autoload paths:\n";
        foreach ($autoloadPaths as $path) {
            echo "- " . $path . ' => ' . (file_exists($path) ? 'FOUND' : 'MISSING') . "\n";
        }
        echo "class_exists('Dompdf\\\\Dompdf'): " . (class_exists('Dompdf\Dompdf') ? 'YES' : 'NO') . "\n";
        exit();
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="certificate_' . $attempt_id . '.html"');
}

if ($download_file_mode) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="certificate_' . $attempt_id . '.html"');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSC Certificate</title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="certificate.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard <?php echo $download_mode ? 'download-mode' : ''; ?>">
        <?php if (!$download_mode): ?>
        <?php
        $GLOBALS['base_path'] = '../../';
        $GLOBALS['nav_path']  = '../';
        include '../../includes/sidebar.php';
        ?>
        <?php endif; ?>

        <main class="main">
            <div class="main__content">
                <div class="certificate-container">
                    <div class="certificate-card">
                        <!-- Certificate Content -->
                        <div class="certificate-content">
                            <!-- Decorative Corner -->
                            <div class="certificate-corner-top"></div>

                            <!-- Logo -->
                            <div class="certificate-logo">
                                <img src="../../assets/image/logo.png" alt="NSC Logo">
                            </div>

                            <!-- Title -->
                            <h1 class="certificate-title">Certificate</h1>
                            <div class="certificate-company">NAVI SHIPPING</div>

                            <!-- Examinee Name -->
                            <div class="certificate-name"><?php echo htmlspecialchars($exam['crew_name']); ?></div>
                            <div class="certificate-name-underline"></div>

                            <!-- Completion Text -->
                            <div class="certificate-completion-text">
                                <?php if ($exam['result_status'] === 'PASSED'): ?>
                                    <em>has successfully completed NSC 1.0 Exam in</em>
                                <?php else: ?>
                                    <em>has completed NSC 1.0 Exam in</em>
                                <?php endif; ?>
                            </div>

                            <!-- Details Grid -->
                            <div class="certificate-details">
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Department:</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-value"><?php echo htmlspecialchars($exam['department']); ?></span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Test performed:</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-value"><?php echo date('m-d-Y', strtotime($exam['start_time'])); ?></span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Category:</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-value"><?php echo htmlspecialchars($exam['category']); ?></span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Test time used:</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-value"><?php echo $time_taken_formatted; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="certificate-divider"></div>

                            <!-- Score Section -->
                            <div class="certificate-scores">
                                <div class="score-item">
                                    <div class="score-value <?php echo $exam['result_status'] === 'PASSED' ? 'success' : 'danger'; ?>">
                                        <?php echo number_format($exam['score'], 0); ?>%
                                    </div>
                                    <div class="score-label">Overall Score</div>
                                </div>
                                <div class="score-divider"></div>
                                <div class="score-item">
                                    <div class="score-value <?php echo $exam['result_status'] === 'PASSED' ? 'passed' : 'failed'; ?>">
                                        <?php echo $exam['result_status']; ?>
                                    </div>
                                    <div class="score-label"><?php echo $exam['passing_score']; ?>% passing score</div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="certificate-description">
                                <?php if ($exam['result_status'] === 'PASSED'): ?>
                                    This certificate confirms that the awardee has demonstrated the required level of competency.
                                <?php else: ?>
                                    This certificate confirms completion of the exam. Further training is recommended.
                                <?php endif; ?>
                            </div>

                            <!-- Signatures -->
                            <div class="certificate-signatures">
                                <div class="signature-item">
                                    <div class="signature-line"></div>
                                    <div class="signature-label">Signature of Assessor</div>
                                </div>
                                <div class="signature-item">
                                    <div class="signature-line"></div>
                                    <div class="signature-label">Signature of Candidate</div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="certificate-footer-text">
                                NAVIgating Excellence Towards Innovative Shipping
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="certificate-actions">
                            <button class="btn-cert-action btn-close-cert" onclick="window.location.href='test_results.php?id=<?php echo htmlspecialchars($attempt_id); ?>'">Close</button>
            <a class="btn-cert-action btn-download-cert" href="certificate.php?id=<?php echo htmlspecialchars($attempt_id); ?>&pdf=1">Download Certificate</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if (!$download_mode): ?>
    <?php include '../../includes/footer.php'; ?>
    <?php endif; ?>

    <script>
    </script>
</body>
</html>
