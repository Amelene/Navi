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
                @page { size: A4 portrait; margin: 10mm; }
                body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #2c3e50; }
                .certificate { border: 3px solid #FF8A4C; border-radius: 12px; padding: 30px 36px; position: relative; }
                .corner { position: absolute; top: 0; right: 0; width: 0; height: 0; border-style: solid; border-width: 0 90px 90px 0; border-color: transparent #FF8A4C transparent transparent; }
                .corner:after { content: ""; position: absolute; top: 2px; right: -90px; width: 0; height: 0; border-style: solid; border-width: 0 88px 88px 0; border-color: transparent #126E82 transparent transparent; }
                .logo { margin-bottom: 18px; }
                .logo img { height: 36px; }
                .title { text-align: center; font-size: 48px; font-weight: 700; margin: 0; font-family: DejaVu Serif, serif; }
                .company { text-align: center; font-size: 14px; letter-spacing: 2px; font-weight: 700; margin-bottom: 18px; }
                .name { text-align: center; font-size: 34px; font-weight: 700; margin: 10px 0 2px; }
                .underline { width: 260px; height: 2px; background: #2c3e50; margin: 0 auto 14px; }
                .completion { text-align: center; font-size: 14px; margin-bottom: 18px; font-style: italic; }
                .row { margin: 6px 0; font-size: 14px; }
                .label { display: inline-block; width: 180px; font-weight: 700; }
                .divider { border-top: 1px solid #ddd; margin: 18px 0; }
                .scores { text-align: center; margin-bottom: 16px; }
                .score-main { font-size: 52px; font-weight: 700; color: '.$scoreColor.'; }
                .score-status { font-size: 44px; font-weight: 700; color: '.$scoreColor.'; margin-top: 6px; }
                .score-sub { font-size: 18px; font-weight: 700; color: #666; }
                .description { text-align: center; font-size: 15px; line-height: 1.5; margin: 18px 0 26px; }
                .signatures { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .signatures td { width: 50%; text-align: center; padding: 0 14px; }
                .line { border-top: 1px solid #2c3e50; margin-bottom: 6px; }
                .footer-text { text-align: center; font-size: 16px; font-weight: 700; color: #126E82; letter-spacing: 0.4px; }
            </style>
        </head>
        <body>
            <div class="certificate">
                <div class="corner"></div>
                <div class="logo">'.($logoDataUri ? '<img src="'.$logoDataUri.'" alt="NSC Logo">' : '').'</div>
                <h1 class="title">Certificate</h1>
                <div class="company">NAVI SHIPPING</div>
                <div class="name">'.$name.'</div>
                <div class="underline"></div>
                <div class="completion">'.$completionText.'</div>

                <div class="row"><span class="label">Department:</span> '.$department.'</div>
                <div class="row"><span class="label">Test performed:</span> '.$testDate.'</div>
                <div class="row"><span class="label">Category:</span> '.$category.'</div>
                <div class="row"><span class="label">Test time used:</span> '.$time_taken_formatted.'</div>

                <div class="divider"></div>

                <div class="scores">
                    <div class="score-main">'.$score.'</div>
                    <div class="score-sub">Overall Score</div>
                    <div class="score-status">'.$status.'</div>
                    <div class="score-sub">'.$passing.'</div>
                </div>

                <div class="description">'.$description.'</div>

                <table class="signatures">
                    <tr>
                        <td><div class="line"></div>Signature of Assessor</td>
                        <td><div class="line"></div>Signature of Candidate</td>
                    </tr>
                </table>

                <div class="footer-text">NAVIgating Excellence Towards Innovative Shipping</div>
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
