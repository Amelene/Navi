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
                @page { size: A4 portrait; margin: 8mm; }
                body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #17345f; }
                .page { width: 194mm; margin: 0 auto; }
                .certificate {
                    border: 1.1mm solid #ff7a3d;
                    border-radius: 4mm;
                    padding: 10mm 12mm 8mm 12mm;
                    position: relative;
                    box-sizing: border-box;
                    min-height: 248mm;
                }

                .corner {
                    position: absolute;
                    top: 0;
                    right: 0;
                    width: 0;
                    height: 0;
                    border-style: solid;
                    border-width: 0 24mm 24mm 0;
                    border-color: transparent #ff7a3d transparent transparent;
                }
                .corner:after {
                    content: "";
                    position: absolute;
                    top: 0.4mm;
                    right: -24mm;
                    width: 0;
                    height: 0;
                    border-style: solid;
                    border-width: 0 23.2mm 23.2mm 0;
                    border-color: transparent #0a7d98 transparent transparent;
                }

                .logo { margin: 0.8mm 0 5.5mm 0; }
                .logo img { height: 14.5mm; width: auto; }

                .title {
                    text-align: center;
                    font-size: 15.2mm;
                    line-height: 1.02;
                    margin: 0 0 2mm 0;
                    font-family: DejaVu Serif, serif;
                    font-weight: 700;
                    color: #000000;
                }

                .company {
                    text-align: center;
                    font-size: 4.4mm;
                    letter-spacing: 0.95mm;
                    font-weight: 700;
                    margin-bottom: 5.8mm;
                    color: #000000;
                }

                .name {
                    text-align: center;
                    font-size: 9.1mm;
                    line-height: 1.08;
                    font-weight: 700;
                    margin: 0;
                    color: #000000;
                }

                .underline {
                    width: 80mm;
                    height: 0.45mm;
                    background: #17345f;
                    margin: 1.5mm auto 3.8mm auto;
                }

                .completion {
                    text-align: center;
                    font-size: 4.3mm;
                    margin-bottom: 5.8mm;
                    font-style: italic;
                    color: #17345f;
                }

                .details-table {
                    width: 74%;
                    margin: 0 auto 5.6mm auto;
                    border-collapse: collapse;
                    color: #17345f;
                }

                .details-table td {
                    padding: 1.05mm 0;
                    font-size: 4.3mm;
                    line-height: 1.16;
                }

                .details-label {
                    width: 50%;
                    font-weight: 700;
                    text-align: left;
                    padding-right: 10mm;
                }

                .details-value {
                    width: 50%;
                    font-weight: 400;
                    text-align: left;
                }

                .divider {
                    border-top: 0.3mm solid #dedede;
                    margin: 5.8mm 0 5.4mm 0;
                }

                .scores {
                    width: 52%;
                    margin: 0 auto 5.3mm auto;
                    border-collapse: collapse;
                    text-align: center;
                }

                .scores td {
                    width: 50%;
                    vertical-align: top;
                    color: #17345f;
                }

                .scores .mid {
                    border-left: 0.35mm solid #e2e2e2;
                }

                .score-main {
                    font-size: 10.2mm;
                    line-height: 1;
                    font-weight: 700;
                    color: '.$scoreColor.';
                    margin: 0 0 1mm 0;
                }

                .score-status {
                    font-size: 10mm;
                    line-height: 1;
                    font-weight: 700;
                    color: '.$scoreColor.';
                    margin: 0 0 1mm 0;
                }

                .score-sub {
                    font-size: 4mm;
                    font-weight: 700;
                    color: #4a4a4a;
                    margin: 0;
                }

                .description {
                    width: 75%;
                    margin: 0 auto 10.5mm auto;
                    text-align: center;
                    font-size: 4.2mm;
                    line-height: 1.3;
                    color: #17345f;
                }

                .signatures {
                    width: 88%;
                    margin: 0 auto 5.6mm auto;
                    border-collapse: collapse;
                }

                .signatures td {
                    width: 50%;
                    text-align: center;
                    color: #17345f;
                    padding: 0 4mm;
                }

                .line {
                    border-top: 0.32mm solid #17345f;
                    margin-bottom: 1.6mm;
                    margin-top: 12mm;
                }

                .sign-label {
                    font-size: 4.2mm;
                    font-weight: 700;
                }

                .footer-text {
                    text-align: center;
                    font-size: 4.3mm;
                    font-weight: 700;
                    color: #007ca0;
                    letter-spacing: 0.2mm;
                }
            </style>
        </head>
        <body>
            <div class="page">
                <div class="certificate">
                    <div class="corner"></div>

                    <div class="logo">'.($logoDataUri ? '<img src="'.$logoDataUri.'" alt="NSC Logo">' : '').'</div>

                    <div class="title">Certificate</div>
                    <div class="company">NAVI SHIPPING</div>

                    <div class="name">'.$name.'</div>
                    <div class="underline"></div>

                    <div class="completion">'.$completionText.'</div>

                    <table class="details-table">
                        <tr><td class="details-label">Department:</td><td class="details-value">'.$department.'</td></tr>
                        <tr><td class="details-label">Test performed:</td><td class="details-value">'.$testDate.'</td></tr>
                        <tr><td class="details-label">Category:</td><td class="details-value">'.$category.'</td></tr>
                        <tr><td class="details-label">Test time used:</td><td class="details-value">'.$time_taken_formatted.'</td></tr>
                    </table>

                    <div class="divider"></div>

                    <table class="scores">
                        <tr>
                            <td>
                                <p class="score-main">'.$score.'</p>
                                <p class="score-sub">Overall Score</p>
                            </td>
                            <td class="mid">
                                <p class="score-status">'.$status.'</p>
                                <p class="score-sub">'.$passing.'</p>
                            </td>
                        </tr>
                    </table>

                    <div class="description">'.$description.'</div>

                    <table class="signatures">
                        <tr>
                            <td>
                                <div class="line"></div>
                                <div class="sign-label">Signature of Assessor</div>
                            </td>
                            <td>
                                <div class="line"></div>
                                <div class="sign-label">Signature of Candidate</div>
                            </td>
                        </tr>
                    </table>

                    <div class="footer-text">NAVIgating Excellence Towards Innovative Shipping</div>
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
