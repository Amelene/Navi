<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

// Get application ID from URL
$application_id = $_GET['id'] ?? '';

if (empty($application_id)) {
    header('Location: application.php');
    exit();
}

// Fetch application details
try {
    $db = Database::getInstance();
    
    $application = $db->fetchOne(
        "SELECT * FROM applications WHERE application_id = :application_id",
        [':application_id' => $application_id]
    );
    
    if (!$application) {
        $_SESSION['error'] = "Application not found.";
        header('Location: application.php');
        exit();
    }
    
    // Decode JSON fields
    $documents               = json_decode($application['documents'], true)               ?? [];
    $training_certificates   = json_decode($application['training_certificates'], true)   ?? [];
    $additional_certificates = json_decode($application['additional_certificates'], true) ?? [];
    $sea_service_record      = json_decode($application['sea_service_record'], true)      ?? [];
    $certificate_checklist   = json_decode($application['certificate_checklist'], true)   ?? [];
    
} catch (Exception $e) {
    error_log("Error fetching application details: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching application details.";
    header('Location: application.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - <?php echo htmlspecialchars($application['application_id']); ?></title>
    <link rel="stylesheet" href="../assets/css/application_details.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="application-container">
        <!-- Header -->
        <div class="application-header">
            <div class="header-logo">
                <img src="../assets/image/logo.png" alt="Navi Shipping Logo">
            </div>
            <div class="header-right">
                <div class="header-info">
                    <p>📍 18 Leo St, Vermella Homes I, Almanza Uno, Las Pinas City,<br>
                    National Capital Region 1750, Philippines</p>
                    <p>📞 +63 9172539709 / +63 2 88843101</p>
                    <p>✉️ operations@navishipping.com.ph</p>
                    <p>🌐 www.navishipping.com</p>
                </div>
                <div class="header-form-number">
                    <p>FORM: F - 021</p>
                    <p>Rev. 2025-06-003</p>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="form-card">
            <div class="form-title-banner">
                <h1>APPLICATION FORM</h1>
            </div>
            
            <div class="form-content">
                <!-- Position Section -->
                <div class="position-section">
                    <div class="info-layout-horizontal">
                        <div class="info-item">
                            <label>Position Applied:</label>
                            <div class="info-value"><?php echo htmlspecialchars($application['position_applied'] ?: 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <label>SRN No.:</label>
                            <div class="info-value"><?php echo htmlspecialchars($application['srn_no'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="personal-info-section">
                    <div class="section-title">PERSONAL INFORMATION</div>
                    <div class="info-layout">
                        <div class="info-columns">
                            <div class="info-column">
                                <div class="info-item"><label>Name:</label><div class="info-value"><?php echo htmlspecialchars($application['name']); ?></div></div>
                                <div class="info-item"><label>Cellphone No.:</label><div class="info-value"><?php echo htmlspecialchars($application['cellphone_no'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Birth Date:</label><div class="info-value"><?php echo htmlspecialchars($application['birth_date'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Birth Place:</label><div class="info-value"><?php echo htmlspecialchars($application['birth_place'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Home Address:</label><div class="info-value"><?php echo htmlspecialchars($application['home_address'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Civil Status:</label><div class="info-value"><?php echo htmlspecialchars($application['civil_status'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>SSS No.:</label><div class="info-value"><?php echo htmlspecialchars($application['sss_no'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>TIN No.:</label><div class="info-value"><?php echo htmlspecialchars($application['tin_no'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>UMID no.:</label><div class="info-value"><?php echo htmlspecialchars($application['umid_no'] ?: 'N/A'); ?></div></div>
                            </div>
                            <div class="info-column">
                                <div class="info-item"><label>Age:</label><div class="info-value"><?php echo htmlspecialchars($application['age'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Nationality:</label><div class="info-value"><?php echo htmlspecialchars($application['nationality'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Height:</label><div class="info-value"><?php echo htmlspecialchars($application['height'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Weight:</label><div class="info-value"><?php echo htmlspecialchars($application['weight'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Email Address:</label><div class="info-value"><?php echo htmlspecialchars($application['email_address'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>Religion:</label><div class="info-value"><?php echo htmlspecialchars($application['religion'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>PAG-IBIG No.:</label><div class="info-value"><?php echo htmlspecialchars($application['pag_ibig_no'] ?: 'N/A'); ?></div></div>
                                <div class="info-item"><label>PhilHealth No.:</label><div class="info-value"><?php echo htmlspecialchars($application['philhealth_no'] ?: 'N/A'); ?></div></div>
                            </div>
                        </div>
                        <div class="photo-box-wrapper">
                            <div class="photo-box"></div>
                        </div>
                    </div>
                </div>

                <!-- Education Section -->
                <div class="education-section">
                    <div class="section-title">HIGHEST EDUCATIONAL ATTAINMENT</div>
                    <div class="info-layout-simple">
                        <div class="info-item"><label>School:</label><div class="info-value"><?php echo htmlspecialchars($application['school'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Address:</label><div class="info-value"><?php echo htmlspecialchars($application['school_address'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Course:</label><div class="info-value"><?php echo htmlspecialchars($application['course'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Year Graduate:</label><div class="info-value"><?php echo htmlspecialchars($application['year_graduate'] ?: 'N/A'); ?></div></div>
                    </div>
                </div>

                <!-- Emergency Contact Section -->
                <div class="emergency-section">
                    <div class="section-title">CONTACT PERSON IN CASE OF EMERGENCY</div>
                    <div class="info-layout-simple">
                        <div class="info-item"><label>Name:</label><div class="info-value"><?php echo htmlspecialchars($application['emergency_name'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Relationship:</label><div class="info-value"><?php echo htmlspecialchars($application['relationship'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Address:</label><div class="info-value"><?php echo htmlspecialchars($application['emergency_address'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Mobile No.:</label><div class="info-value"><?php echo htmlspecialchars($application['mobile_no'] ?: 'N/A'); ?></div></div>
                    </div>
                </div>

                <!-- Document Section -->
                <div class="document-section">
                    <div class="section-title">DOCUMENT TYPE</div>
                    <div class="document-table-wrapper">
                        <table class="doc-table">
                            <thead>
                                <tr><th>Document Type</th><th>Number</th><th>Issued By</th><th>Issued Date</th><th>Expiry Date</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $docTypes = [
                                    'seaman_book' => "Seaman's Book",
                                    'mariner_id'  => 'Mariner ID',
                                    'sid'         => 'SID',
                                    'dcoc'        => 'DCOC',
                                    'nbi'         => 'NBI',
                                ];
                                foreach ($docTypes as $key => $label): ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><?php echo htmlspecialchars($documents[$key]['number']      ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($documents[$key]['issued_by']   ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($documents[$key]['issued_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($documents[$key]['expiry_date'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Training & Certificates Section -->
                <div class="training-section">
                    <div class="section-title">TRAINING & CERTIFICATES</div>
                    <div class="document-table-wrapper">
                        <table class="doc-table">
                            <thead>
                                <tr><th>Certificate Type</th><th>Number</th><th>Issued By</th><th>Issued Date</th><th>Expiry Date</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $trainCerts = [
                                    'basic_training' => 'Basic Training',
                                    'bt_cop'         => 'BT Cop',
                                    'pscrb'          => 'PSCRB',
                                    'pscrb_cop'      => 'PSCRB Cop',
                                    'sdsd'           => 'SDSD',
                                    'sdsd_cop'       => 'SDSD Cop',
                                    'aff'            => 'AFF',
                                    'aff_cop'        => 'AFF Cop',
                                    'meca_mefa'      => 'MECA/MEFA',
                                ];
                                foreach ($trainCerts as $key => $label): ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><?php echo htmlspecialchars($training_certificates[$key]['number']      ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($training_certificates[$key]['issued_by']   ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($training_certificates[$key]['issued_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($training_certificates[$key]['expiry_date'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Additional Certificates Section -->
                <div class="additional-certificates-section">
                    <div class="section-title">ADDITIONAL CERTIFICATES</div>
                    <div class="document-table-wrapper">
                        <table class="doc-table">
                            <thead>
                                <tr><th>Certificate Type</th><th>Number</th><th>Issued By</th><th>Issued Date</th><th>Expiry Date</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $addCerts = [
                                    'mec_mefa_cop'         => 'MEC/MEFA Cop',
                                    'marpol'               => 'Marpol',
                                    'sso'                  => 'SSO',
                                    'watch_keeping'        => 'Watch Keeping',
                                    'gmdss'                => 'GMDSS',
                                    'safe_nav'             => 'Safe Navigation',
                                    'maritime_law'         => 'Maritime Law',
                                    'nc'                   => 'NC',
                                    'dp'                   => 'DP',
                                    'padams'               => 'PADAMS',
                                    'hazmat'               => 'HAZMAT',
                                    'ecdis'                => 'ECDIS',
                                    'btoc'                 => 'BTOC',
                                    'cargo_handling'       => 'Cargo Handling',
                                    'fast_rescue'          => 'Fast Rescue',
                                    'risk_assessment'      => 'Risk Assessment',
                                    'ship_simulator'       => 'Ship Simulator',
                                    'emergency_awareness'  => 'Emergency Awareness',
                                    'ccm'                  => 'CCM',
                                ];
                                foreach ($addCerts as $key => $label): ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><?php echo htmlspecialchars($additional_certificates[$key]['number']      ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($additional_certificates[$key]['issued_by']   ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($additional_certificates[$key]['issued_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($additional_certificates[$key]['expiry_date'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sea Service Record Section -->
                <div class="sea-service-section">
                    <div class="section-title">SEA SERVICE RECORD</div>
                    <div class="document-table-wrapper">
                        <table class="sea-service-table">
                            <thead>
                                <tr><th>Position</th><th>Vessel Name</th><th>Company</th><th>Type</th><th>GRT</th><th>From</th><th>To</th><th>Months</th><th>Reason for Leaving</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sea_service_record) && is_array($sea_service_record)):
                                    foreach ($sea_service_record as $record):
                                        if (!empty($record['position']) || !empty($record['vessel'])): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['vessel']   ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['company']  ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['type']     ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['grt']      ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['from']     ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['to']       ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['months']   ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['reason']   ?? 'N/A'); ?></td>
                                </tr>
                                <?php endif; endforeach;
                                else: ?>
                                <tr><td colspan="9" style="text-align:center;">No sea service record available</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Certificate Requirements Checklist Section -->
                <div class="checklist-section">
                    <div class="section-title">CERTIFICATE REQUIREMENTS CHECKLIST</div>
                    <div class="checklist-container">
                        <!-- Ratings Column -->
                        <div class="checklist-column">
                            <h3 class="section-title">LIST OF CERTIFICATE REQUIREMENTS</h3>
                            <div class="checklist-items">
                                <?php
                                $ratings_certs = [
                                    'cert_bt'          => 'BT (Basic Training)',
                                    'cert_ratings'     => 'Ratings Forming Part of a Navigational Watch',
                                    'cert_nav_watch_24'=> 'Ratings Forming Part of a Navigational Watch (II/4)',
                                    'cert_eng_watch_34'=> 'Ratings Forming Part of an Engineering Watch (III/4)',
                                    'cert_nav_watch_25'=> 'Ratings Forming Part of a Navigational Watch (II/5)',
                                    'cert_eng_watch_35'=> 'Ratings Forming Part of an Engineering Watch (III/5)',
                                    'cert_sdsd'        => 'SDSD (Ship Security Duties)',
                                    'cert_atot'        => 'ATOT (Advanced Training for Oil Tanker)',
                                    'cert_atot_cargo'  => 'ATOT (Advanced Training for Oil Tanker Cargo Operations)',
                                    'cert_mefa'        => 'MEFA (Medical First Aid)',
                                    'cert_btoc'        => 'BTOC (Basic Training for Oil and Chemical Tanker Cargo Operations)',
                                    'cert_aff'         => 'AFF (Advanced Fire Fighting)',
                                    'cert_marpol'      => 'Consolidated Marpol 1 to 6',
                                    'cert_pscrb'       => 'P.S.C.R.B (Proficiency in Survival Craft and Rescue Boat)',
                                    'cert_ism'         => 'ISM Code',
                                    'cert_rac'         => 'RAC (Radar Simulator Course)',
                                ];
                                foreach ($ratings_certs as $key => $label):
                                    $checked = isset($certificate_checklist['ratings'][$key]) && $certificate_checklist['ratings'][$key] == 1;
                                ?>
                                <div class="checklist-item">
                                    <label><input type="checkbox" <?php echo $checked ? 'checked' : ''; ?> disabled> <?php echo $label; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Officers Column -->
                        <div class="checklist-column">
                            <h3 class="section-title">LIST OF TRAININGS/CERTIFICATES FOR OFFICERS</h3>
                            <div class="checklist-items">
                                <?php
                                $officers_certs = [
                                    'officer_bt'     => 'BT (Basic Training)',
                                    'officer_bsc'    => 'BSC (Basic Safety Course)',
                                    'officer_brm'    => 'BRM (Bridge Resource Management)',
                                    'officer_erm'    => 'ERM (Engine Room Resource Management)',
                                    'officer_radio'  => 'Radio Operator',
                                    'officer_ssbt'   => 'SSBT (Ship Security and Bridge Team)',
                                    'officer_atot'   => 'ATOT (Advanced Training for Oil Tanker)',
                                    'officer_radar'  => 'Radar Navigation',
                                    'officer_mefa'   => 'MEFA (Medical First Aid)',
                                    'officer_aff'    => 'AFF (Advanced Fire Fighting)',
                                    'officer_sncr'   => 'SNCR (Safe Navigation and Collision Regulations)',
                                    'officer_pscrb'  => 'P.S.C.R.B (Proficiency in Survival Craft and Rescue Boat)',
                                    'officer_meca'   => 'MECA (Medical Care)',
                                    'officer_sso'    => 'SSO (Ship Security Officer)',
                                    'officer_marpol' => 'Consolidated Marpol 1 to 6',
                                    'officer_mlc'    => 'Management Level Certificate',
                                    'officer_btoc'   => 'BTOC (For tankers only)',
                                ];
                                foreach ($officers_certs as $key => $label):
                                    $checked = isset($certificate_checklist['officers'][$key]) && $certificate_checklist['officers'][$key] == 1;
                                ?>
                                <div class="checklist-item">
                                    <label><input type="checkbox" <?php echo $checked ? 'checked' : ''; ?> disabled> <?php echo $label; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="additional-info-section">
                    <div class="section-title">ADDITIONAL INFORMATION</div>
                    <div class="info-layout-simple">
                        <div class="info-item"><label>Estimated Date to Embark:</label><div class="info-value"><?php echo htmlspecialchars($application['embark_date'] ?: 'N/A'); ?></div></div>
                        <div class="info-item"><label>Expected Salary:</label><div class="info-value"><?php echo htmlspecialchars($application['expected_salary'] ?: 'N/A'); ?></div></div>
                    </div>
                </div>
                
                <!-- Navigation Buttons -->
                <div class="form-actions">
                    <a href="application.php" class="btn btn-back">← BACK TO LIST</a>
                    <button onclick="window.print()" class="btn btn-print">🖨️ PRINT</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
