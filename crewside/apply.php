<?php
// Suppress warnings for cleaner display
error_reporting(E_ERROR | E_PARSE);
session_start();

// Include database connection
require_once '../config/database.php';

// This is a public form - no login 
// Initialize form data in session if not exists
if (!isset($_SESSION['application_data'])) {
    $_SESSION['application_data'] = [
        'page' => 1,
        'position_applied' => '',
        'srn_no' => '',
        // Personal Information
        'name' => '',
        'age' => '',
        'cellphone_no' => '',
        'nationality' => '',
        'birth_date' => '',
        'height' => '',
        'birth_place' => '',
        'weight' => '',
        'home_address' => '',
        'email_address' => '',
        'civil_status' => '',
        'religion' => '',
        'sss_no' => '',
        'pag_ibig_no' => '',
        'tin_no' => '',
        'philhealth_no' => '',
        'umid_no' => '',
        // Educational Attainment
        'school' => '',
        'school_address' => '',
        'course' => '',
        'year_graduate' => '',
        // Emergency Contact
        'emergency_name' => '',
        'relationship' => '',
        'emergency_address' => '',
        'mobile_no' => '',
        'seaman_book_no' => '',
        'seaman_book_issued_by' => '',
        'seaman_book_issued_date' => '',
        'seaman_book_expiry_date' => '',
        'sid' => '',
        'dcoc' => '',
        'nbi' => '',
        // Training & Certificates
        'basic_training' => '',
        'bt_cop' => '',
        'pscrb' => '',
        'pscrb_cop' => '',
        'sdsd' => '',
        'sdsd_cop' => '',
        'aff' => '',
        'aff_cop' => '',
        'meca_mefa' => ''
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'next') {
        // Save current page data
        foreach ($_POST as $key => $value) {
            if ($key !== 'action' && isset($_SESSION['application_data'][$key])) {
                $_SESSION['application_data'][$key] = $value;
            }
        }
        
        // Move to next page
        $_SESSION['application_data']['page']++;
        header('Location: apply.php');
        exit();
    } elseif ($action === 'back') {
        // Move to previous page
        $_SESSION['application_data']['page']--;
        header('Location: apply.php');
        exit();
    } elseif ($action === 'submit') {
        // Save final page data
        foreach ($_POST as $key => $value) {
            if ($key !== 'action' && isset($_SESSION['application_data'][$key])) {
                $_SESSION['application_data'][$key] = $value;
            }
        }
        
        // Save to database
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Generate unique application ID
            $stmt = $conn->query("SELECT COUNT(*) as count FROM applications");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] + 1;
            $application_id = 'A-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            
            // Prepare documents data as JSON
            $documents = [
                'seaman_book' => [
                    'number' => $_POST['seaman_book_no'] ?? '',
                    'issued_by' => $_POST['seaman_book_issued_by'] ?? '',
                    'issued_date' => $_POST['seaman_book_issued_date'] ?? '',
                    'expiry_date' => $_POST['seaman_book_expiry_date'] ?? ''
                ],
                'mariner_id' => [
                    'number' => $_POST['mariner_id_no'] ?? '',
                    'issued_by' => $_POST['mariner_id_issued_by'] ?? '',
                    'issued_date' => $_POST['mariner_id_issued_date'] ?? '',
                    'expiry_date' => $_POST['mariner_id_expiry_date'] ?? ''
                ],
                'sid' => [
                    'number' => $_SESSION['application_data']['sid'] ?? '',
                    'issued_by' => $_POST['sid_issued_by'] ?? '',
                    'issued_date' => $_POST['sid_issued_date'] ?? '',
                    'expiry_date' => $_POST['sid_expiry_date'] ?? ''
                ],
                'dcoc' => [
                    'number' => $_SESSION['application_data']['dcoc'] ?? '',
                    'issued_by' => $_POST['dcoc_issued_by'] ?? '',
                    'issued_date' => $_POST['dcoc_issued_date'] ?? '',
                    'expiry_date' => $_POST['dcoc_expiry_date'] ?? ''
                ],
                'nbi' => [
                    'number' => $_SESSION['application_data']['nbi'] ?? '',
                    'issued_by' => $_POST['nbi_issued_by'] ?? '',
                    'issued_date' => $_POST['nbi_issued_date'] ?? '',
                    'expiry_date' => $_POST['nbi_expiry_date'] ?? ''
                ]
            ];
            
            // Prepare training certificates as JSON
            $training_certificates = [
                'basic_training' => [
                    'number' => $_SESSION['application_data']['basic_training'] ?? '',
                    'issued_by' => $_POST['basic_training_issued_by'] ?? '',
                    'issued_date' => $_POST['basic_training_issued_date'] ?? '',
                    'expiry_date' => $_POST['basic_training_expiry_date'] ?? ''
                ],
                'bt_cop' => [
                    'number' => $_SESSION['application_data']['bt_cop'] ?? '',
                    'issued_by' => $_POST['bt_cop_issued_by'] ?? '',
                    'issued_date' => $_POST['bt_cop_issued_date'] ?? '',
                    'expiry_date' => $_POST['bt_cop_expiry_date'] ?? ''
                ],
                'pscrb' => [
                    'number' => $_SESSION['application_data']['pscrb'] ?? '',
                    'issued_by' => $_POST['pscrb_issued_by'] ?? '',
                    'issued_date' => $_POST['pscrb_issued_date'] ?? '',
                    'expiry_date' => $_POST['pscrb_expiry_date'] ?? ''
                ],
                'pscrb_cop' => [
                    'number' => $_SESSION['application_data']['pscrb_cop'] ?? '',
                    'issued_by' => $_POST['pscrb_cop_issued_by'] ?? '',
                    'issued_date' => $_POST['pscrb_cop_issued_date'] ?? '',
                    'expiry_date' => $_POST['pscrb_cop_expiry_date'] ?? ''
                ],
                'sdsd' => [
                    'number' => $_SESSION['application_data']['sdsd'] ?? '',
                    'issued_by' => $_POST['sdsd_issued_by'] ?? '',
                    'issued_date' => $_POST['sdsd_issued_date'] ?? '',
                    'expiry_date' => $_POST['sdsd_expiry_date'] ?? ''
                ],
                'sdsd_cop' => [
                    'number' => $_SESSION['application_data']['sdsd_cop'] ?? '',
                    'issued_by' => $_POST['sdsd_cop_issued_by'] ?? '',
                    'issued_date' => $_POST['sdsd_cop_issued_date'] ?? '',
                    'expiry_date' => $_POST['sdsd_cop_expiry_date'] ?? ''
                ],
                'aff' => [
                    'number' => $_SESSION['application_data']['aff'] ?? '',
                    'issued_by' => $_POST['aff_issued_by'] ?? '',
                    'issued_date' => $_POST['aff_issued_date'] ?? '',
                    'expiry_date' => $_POST['aff_expiry_date'] ?? ''
                ],
                'aff_cop' => [
                    'number' => $_SESSION['application_data']['aff_cop'] ?? '',
                    'issued_by' => $_POST['aff_cop_issued_by'] ?? '',
                    'issued_date' => $_POST['aff_cop_issued_date'] ?? '',
                    'expiry_date' => $_POST['aff_cop_expiry_date'] ?? ''
                ],
                'meca_mefa' => [
                    'number' => $_SESSION['application_data']['meca_mefa'] ?? '',
                    'issued_by' => $_POST['meca_mefa_issued_by'] ?? '',
                    'issued_date' => $_POST['meca_mefa_issued_date'] ?? '',
                    'expiry_date' => $_POST['meca_mefa_expiry_date'] ?? ''
                ]
            ];
            
            // Prepare additional certificates as JSON
            $additional_certificates = [
                'mec_mefa_cop' => [
                    'number' => $_POST['mec_mefa_cop'] ?? '',
                    'issued_by' => $_POST['mec_mefa_cop_issued_by'] ?? '',
                    'issued_date' => $_POST['mec_mefa_cop_issued_date'] ?? '',
                    'expiry_date' => $_POST['mec_mefa_cop_expiry_date'] ?? ''
                ],
                'marpol' => [
                    'number' => $_POST['marpol'] ?? '',
                    'issued_by' => $_POST['marpol_issued_by'] ?? '',
                    'issued_date' => $_POST['marpol_issued_date'] ?? '',
                    'expiry_date' => $_POST['marpol_expiry_date'] ?? ''
                ],
                'sso' => [
                    'number' => $_POST['sso'] ?? '',
                    'issued_by' => $_POST['sso_issued_by'] ?? '',
                    'issued_date' => $_POST['sso_issued_date'] ?? '',
                    'expiry_date' => $_POST['sso_expiry_date'] ?? ''
                ],
                'watch_keeping' => [
                    'number' => $_POST['watch_keeping'] ?? '',
                    'issued_by' => $_POST['watch_keeping_issued_by'] ?? '',
                    'issued_date' => $_POST['watch_keeping_issued_date'] ?? '',
                    'expiry_date' => $_POST['watch_keeping_expiry_date'] ?? ''
                ],
                'gmdss' => [
                    'number' => $_POST['gmdss'] ?? '',
                    'issued_by' => $_POST['gmdss_issued_by'] ?? '',
                    'issued_date' => $_POST['gmdss_issued_date'] ?? '',
                    'expiry_date' => $_POST['gmdss_expiry_date'] ?? ''
                ],
                'safe_nav' => [
                    'number' => $_POST['safe_nav'] ?? '',
                    'issued_by' => $_POST['safe_nav_issued_by'] ?? '',
                    'issued_date' => $_POST['safe_nav_issued_date'] ?? '',
                    'expiry_date' => $_POST['safe_nav_expiry_date'] ?? ''
                ],
                'maritime_law' => [
                    'number' => $_POST['maritime_law'] ?? '',
                    'issued_by' => $_POST['maritime_law_issued_by'] ?? '',
                    'issued_date' => $_POST['maritime_law_issued_date'] ?? '',
                    'expiry_date' => $_POST['maritime_law_expiry_date'] ?? ''
                ],
                'nc' => [
                    'number' => $_POST['nc'] ?? '',
                    'issued_by' => $_POST['nc_issued_by'] ?? '',
                    'issued_date' => $_POST['nc_issued_date'] ?? '',
                    'expiry_date' => $_POST['nc_expiry_date'] ?? ''
                ],
                'dp' => [
                    'number' => $_POST['dp'] ?? '',
                    'issued_by' => $_POST['dp_issued_by'] ?? '',
                    'issued_date' => $_POST['dp_issued_date'] ?? '',
                    'expiry_date' => $_POST['dp_expiry_date'] ?? ''
                ],
                'padams' => [
                    'number' => $_POST['padams'] ?? '',
                    'issued_by' => $_POST['padams_issued_by'] ?? '',
                    'issued_date' => $_POST['padams_issued_date'] ?? '',
                    'expiry_date' => $_POST['padams_expiry_date'] ?? ''
                ],
                'hazmat' => [
                    'number' => $_POST['hazmat'] ?? '',
                    'issued_by' => $_POST['hazmat_issued_by'] ?? '',
                    'issued_date' => $_POST['hazmat_issued_date'] ?? '',
                    'expiry_date' => $_POST['hazmat_expiry_date'] ?? ''
                ],
                'ecdis' => [
                    'number' => $_POST['ecdis'] ?? '',
                    'issued_by' => $_POST['ecdis_issued_by'] ?? '',
                    'issued_date' => $_POST['ecdis_issued_date'] ?? '',
                    'expiry_date' => $_POST['ecdis_expiry_date'] ?? ''
                ],
                'btoc' => [
                    'number' => $_POST['btoc'] ?? '',
                    'issued_by' => $_POST['btoc_issued_by'] ?? '',
                    'issued_date' => $_POST['btoc_issued_date'] ?? '',
                    'expiry_date' => $_POST['btoc_expiry_date'] ?? ''
                ],
                'cargo_handling' => [
                    'number' => $_POST['cargo_handling'] ?? '',
                    'issued_by' => $_POST['cargo_handling_issued_by'] ?? '',
                    'issued_date' => $_POST['cargo_handling_issued_date'] ?? '',
                    'expiry_date' => $_POST['cargo_handling_expiry_date'] ?? ''
                ],
                'fast_rescue' => [
                    'number' => $_POST['fast_rescue'] ?? '',
                    'issued_by' => $_POST['fast_rescue_issued_by'] ?? '',
                    'issued_date' => $_POST['fast_rescue_issued_date'] ?? '',
                    'expiry_date' => $_POST['fast_rescue_expiry_date'] ?? ''
                ],
                'risk_assessment' => [
                    'number' => $_POST['risk_assessment'] ?? '',
                    'issued_by' => $_POST['risk_assessment_issued_by'] ?? '',
                    'issued_date' => $_POST['risk_assessment_issued_date'] ?? '',
                    'expiry_date' => $_POST['risk_assessment_expiry_date'] ?? ''
                ],
                'ship_simulator' => [
                    'number' => $_POST['ship_simulator'] ?? '',
                    'issued_by' => $_POST['ship_simulator_issued_by'] ?? '',
                    'issued_date' => $_POST['ship_simulator_issued_date'] ?? '',
                    'expiry_date' => $_POST['ship_simulator_expiry_date'] ?? ''
                ],
                'emergency_awareness' => [
                    'number' => $_POST['emergency_awareness'] ?? '',
                    'issued_by' => $_POST['emergency_awareness_issued_by'] ?? '',
                    'issued_date' => $_POST['emergency_awareness_issued_date'] ?? '',
                    'expiry_date' => $_POST['emergency_awareness_expiry_date'] ?? ''
                ],
                'ccm' => [
                    'number' => $_POST['ccm'] ?? '',
                    'issued_by' => $_POST['ccm_issued_by'] ?? '',
                    'issued_date' => $_POST['ccm_issued_date'] ?? '',
                    'expiry_date' => $_POST['ccm_expiry_date'] ?? ''
                ]
            ];
            
            // Prepare sea service record as JSON
            $sea_service_record = [
                [
                    'position' => $_POST['sea_position_1'] ?? '',
                    'vessel' => $_POST['sea_vessel_1'] ?? '',
                    'company' => $_POST['sea_company_1'] ?? '',
                    'type' => $_POST['sea_type_1'] ?? '',
                    'grt' => $_POST['sea_grt_1'] ?? '',
                    'from' => $_POST['sea_from_1'] ?? '',
                    'to' => $_POST['sea_to_1'] ?? '',
                    'months' => $_POST['sea_months_1'] ?? '',
                    'reason' => $_POST['sea_reason_1'] ?? ''
                ],
                [
                    'position' => $_POST['sea_position_2'] ?? '',
                    'vessel' => $_POST['sea_vessel_2'] ?? '',
                    'company' => $_POST['sea_company_2'] ?? '',
                    'type' => $_POST['sea_type_2'] ?? '',
                    'grt' => $_POST['sea_grt_2'] ?? '',
                    'from' => $_POST['sea_from_2'] ?? '',
                    'to' => $_POST['sea_to_2'] ?? '',
                    'months' => $_POST['sea_months_2'] ?? '',
                    'reason' => $_POST['sea_reason_2'] ?? ''
                ],
                [
                    'position' => $_POST['sea_position_3'] ?? '',
                    'vessel' => $_POST['sea_vessel_3'] ?? '',
                    'company' => $_POST['sea_company_3'] ?? '',
                    'type' => $_POST['sea_type_3'] ?? '',
                    'grt' => $_POST['sea_grt_3'] ?? '',
                    'from' => $_POST['sea_from_3'] ?? '',
                    'to' => $_POST['sea_to_3'] ?? '',
                    'months' => $_POST['sea_months_3'] ?? '',
                    'reason' => $_POST['sea_reason_3'] ?? ''
                ]
            ];
            
            // Prepare certificate checklist as JSON
            $certificate_checklist = [
                'ratings' => [
                    'cert_bt' => isset($_POST['cert_bt']) ? 1 : 0,
                    'cert_ratings' => isset($_POST['cert_ratings']) ? 1 : 0,
                    'cert_nav_watch_24' => isset($_POST['cert_nav_watch_24']) ? 1 : 0,
                    'cert_eng_watch_34' => isset($_POST['cert_eng_watch_34']) ? 1 : 0,
                    'cert_nav_watch_25' => isset($_POST['cert_nav_watch_25']) ? 1 : 0,
                    'cert_eng_watch_35' => isset($_POST['cert_eng_watch_35']) ? 1 : 0,
                    'cert_sdsd' => isset($_POST['cert_sdsd']) ? 1 : 0,
                    'cert_atot' => isset($_POST['cert_atot']) ? 1 : 0,
                    'cert_atot_cargo' => isset($_POST['cert_atot_cargo']) ? 1 : 0,
                    'cert_mefa' => isset($_POST['cert_mefa']) ? 1 : 0,
                    'cert_btoc' => isset($_POST['cert_btoc']) ? 1 : 0,
                    'cert_aff' => isset($_POST['cert_aff']) ? 1 : 0,
                    'cert_marpol' => isset($_POST['cert_marpol']) ? 1 : 0,
                    'cert_pscrb' => isset($_POST['cert_pscrb']) ? 1 : 0,
                    'cert_ism' => isset($_POST['cert_ism']) ? 1 : 0,
                    'cert_rac' => isset($_POST['cert_rac']) ? 1 : 0
                ],
                'officers' => [
                    'officer_bt' => isset($_POST['officer_bt']) ? 1 : 0,
                    'officer_bsc' => isset($_POST['officer_bsc']) ? 1 : 0,
                    'officer_brm' => isset($_POST['officer_brm']) ? 1 : 0,
                    'officer_erm' => isset($_POST['officer_erm']) ? 1 : 0,
                    'officer_radio' => isset($_POST['officer_radio']) ? 1 : 0,
                    'officer_ssbt' => isset($_POST['officer_ssbt']) ? 1 : 0,
                    'officer_atot' => isset($_POST['officer_atot']) ? 1 : 0,
                    'officer_radar' => isset($_POST['officer_radar']) ? 1 : 0,
                    'officer_mefa' => isset($_POST['officer_mefa']) ? 1 : 0,
                    'officer_aff' => isset($_POST['officer_aff']) ? 1 : 0,
                    'officer_sncr' => isset($_POST['officer_sncr']) ? 1 : 0,
                    'officer_pscrb' => isset($_POST['officer_pscrb']) ? 1 : 0,
                    'officer_meca' => isset($_POST['officer_meca']) ? 1 : 0,
                    'officer_sso' => isset($_POST['officer_sso']) ? 1 : 0,
                    'officer_marpol' => isset($_POST['officer_marpol']) ? 1 : 0,
                    'officer_mlc' => isset($_POST['officer_mlc']) ? 1 : 0,
                    'officer_btoc' => isset($_POST['officer_btoc']) ? 1 : 0
                ]
            ];
            
            // Insert into database
            $sql = "INSERT INTO applications (
                application_id, position_applied, srn_no,
                name, age, cellphone_no, nationality, birth_date, height, birth_place, weight,
                home_address, email_address, civil_status, religion,
                sss_no, pag_ibig_no, tin_no, philhealth_no, umid_no,
                school, school_address, course, year_graduate,
                emergency_name, relationship, emergency_address, mobile_no,
                documents, training_certificates, additional_certificates,
                sea_service_record, certificate_checklist,
                embark_date, expected_salary, status
            ) VALUES (
                :application_id, :position_applied, :srn_no,
                :name, :age, :cellphone_no, :nationality, :birth_date, :height, :birth_place, :weight,
                :home_address, :email_address, :civil_status, :religion,
                :sss_no, :pag_ibig_no, :tin_no, :philhealth_no, :umid_no,
                :school, :school_address, :course, :year_graduate,
                :emergency_name, :relationship, :emergency_address, :mobile_no,
                :documents, :training_certificates, :additional_certificates,
                :sea_service_record, :certificate_checklist,
                :embark_date, :expected_salary, 'pending'
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':application_id' => $application_id,
                ':position_applied' => $_SESSION['application_data']['position_applied'],
                ':srn_no' => $_SESSION['application_data']['srn_no'],
                ':name' => $_SESSION['application_data']['name'],
                ':age' => $_SESSION['application_data']['age'],
                ':cellphone_no' => $_SESSION['application_data']['cellphone_no'],
                ':nationality' => $_SESSION['application_data']['nationality'],
                ':birth_date' => $_SESSION['application_data']['birth_date'],
                ':height' => $_SESSION['application_data']['height'],
                ':birth_place' => $_SESSION['application_data']['birth_place'],
                ':weight' => $_SESSION['application_data']['weight'],
                ':home_address' => $_SESSION['application_data']['home_address'],
                ':email_address' => $_SESSION['application_data']['email_address'],
                ':civil_status' => $_SESSION['application_data']['civil_status'],
                ':religion' => $_SESSION['application_data']['religion'],
                ':sss_no' => $_SESSION['application_data']['sss_no'],
                ':pag_ibig_no' => $_SESSION['application_data']['pag_ibig_no'],
                ':tin_no' => $_SESSION['application_data']['tin_no'],
                ':philhealth_no' => $_SESSION['application_data']['philhealth_no'],
                ':umid_no' => $_SESSION['application_data']['umid_no'],
                ':school' => $_SESSION['application_data']['school'],
                ':school_address' => $_SESSION['application_data']['school_address'],
                ':course' => $_SESSION['application_data']['course'],
                ':year_graduate' => $_SESSION['application_data']['year_graduate'],
                ':emergency_name' => $_SESSION['application_data']['emergency_name'],
                ':relationship' => $_SESSION['application_data']['relationship'],
                ':emergency_address' => $_SESSION['application_data']['emergency_address'],
                ':mobile_no' => $_SESSION['application_data']['mobile_no'],
                ':documents' => json_encode($documents),
                ':training_certificates' => json_encode($training_certificates),
                ':additional_certificates' => json_encode($additional_certificates),
                ':sea_service_record' => json_encode($sea_service_record),
                ':certificate_checklist' => json_encode($certificate_checklist),
                ':embark_date' => $_POST['embark_date'] ?? null,
                ':expected_salary' => $_POST['expected_salary'] ?? null
            ]);
            
            // Store application ID in session for success page
            $_SESSION['application_id'] = $application_id;
            $_SESSION['application_submitted'] = true;
            
            // Clear application data
            unset($_SESSION['application_data']);
            
            header('Location: apply_success.php');
            exit();
            
        } catch (Exception $e) {
            // Log error and show user-friendly message
            error_log("Application submission error: " . $e->getMessage());
            $_SESSION['error_message'] = "An error occurred while submitting your application. Please try again.";
            header('Location: apply.php');
            exit();
        }
    }
}

$currentPage = $_SESSION['application_data']['page'];
$formData = $_SESSION['application_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - Navi Shipping</title>
    <link rel="stylesheet" href="apply.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="application-container">
        <!-- Header - Matching the image -->
        <div class="application-header">
            <div class="header-logo">
                <img src="../assets/image/logo.png" alt="Navi Shipping Logo">
            </div>
            <div class="header-right">
                <div class="header-info">
                    <p>📍 18 Leo St, Vermella Homes I, Almanza Uno, Las Pinas City, National Capital Region 1750, Philippines</p>
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
            <!-- Form Title inside the card -->
            <div class="form-title-banner">
                <h1>APPLICATION FORM</h1>
            </div>
            
            <form method="POST" action="apply.php">
                <?php if ($currentPage === 1): ?>
                    <!-- PAGE 1: Personal Information -->
                    <div class="position-section">
                        <div class="info-layout-horizontal">
                            <div class="info-item">
                                <label>Position Applied:</label>
                                <input type="text" name="position_applied" value="<?php echo htmlspecialchars($formData['position_applied']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>SRN No.:</label>
                                <input type="text" name="srn_no" value="<?php echo htmlspecialchars($formData['srn_no']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="personal-info-section">
                        <div class="section-title">PERSONAL INFORMATION</div>
                        <div class="info-layout">
                            <div class="info-columns">
                                <div class="info-column">
                                    <div class="info-item">
                                        <label>Name:</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Cellphone No.:</label>
                                        <input type="tel" name="cellphone_no" value="<?php echo htmlspecialchars($formData['cellphone_no']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Birth Date:</label>
                                        <input type="date" name="birth_date" value="<?php echo htmlspecialchars($formData['birth_date']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Birth Place:</label>
                                        <input type="text" name="birth_place" value="<?php echo htmlspecialchars($formData['birth_place']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Home Address:</label>
                                        <input type="text" name="home_address" value="<?php echo htmlspecialchars($formData['home_address']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Civil Status:</label>
                                        <select name="civil_status" >
                                            <option value="">Select</option>
                                            <option value="Single" <?php echo $formData['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo $formData['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Divorced" <?php echo $formData['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="Widowed" <?php echo $formData['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                        </select>
                                    </div>
                                    <div class="info-item">
                                        <label>SSS No.:</label>
                                        <input type="text" name="sss_no" value="<?php echo htmlspecialchars($formData['sss_no']); ?>">
                                    </div>
                                    <div class="info-item">
                                        <label>TIN No.:</label>
                                        <input type="text" name="tin_no" value="<?php echo htmlspecialchars($formData['tin_no']); ?>">
                                    </div>
                                    <div class="info-item">
                                        <label>UMID no.:</label>
                                        <input type="text" name="umid_no" value="<?php echo htmlspecialchars($formData['umid_no']); ?>">
                                    </div>
                                </div>
                                
                                <div class="info-column">
                                    <div class="info-item">
                                        <label>Age:</label>
                                        <input type="number" name="age" value="<?php echo htmlspecialchars($formData['age']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Nationality:</label>
                                        <input type="text" name="nationality" value="<?php echo htmlspecialchars($formData['nationality']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Height:</label>
                                        <input type="text" name="height" value="<?php echo htmlspecialchars($formData['height']); ?>" placeholder="e.g., 5'8"">
                                    </div>
                                    <div class="info-item">
                                        <label>Weight:</label>
                                        <input type="text" name="weight" value="<?php echo htmlspecialchars($formData['weight']); ?>" placeholder="e.g., 70 kg">
                                    </div>
                                    <div class="info-item">
                                        <label>Email Address:</label>
                                        <input type="email" name="email_address" value="<?php echo htmlspecialchars($formData['email_address']); ?>" >
                                    </div>
                                    <div class="info-item">
                                        <label>Religion:</label>
                                        <input type="text" name="religion" value="<?php echo htmlspecialchars($formData['religion']); ?>">
                                    </div>
                                    <div class="info-item">
                                        <label>PAG-IBIG No.:</label>
                                        <input type="text" name="pag_ibig_no" value="<?php echo htmlspecialchars($formData['pag_ibig_no']); ?>">
                                    </div>
                                    <div class="info-item">
                                        <label>PhilHealth No.:</label>
                                        <input type="text" name="philhealth_no" value="<?php echo htmlspecialchars($formData['philhealth_no']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="photo-box-wrapper">
                                <div class="photo-box"></div>
                            </div>
                        </div>
                    </div>

                    <div class="education-section">
                        <div class="section-title">HIGHEST EDUCATIONAL ATTAINMENT</div>
                        <div class="info-layout-simple">
                            <div class="info-item">
                                <label>School:</label>
                                <input type="text" name="school" value="<?php echo htmlspecialchars($formData['school']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>Address:</label>
                                <input type="text" name="school_address" value="<?php echo htmlspecialchars($formData['school_address']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>Course:</label>
                                <input type="text" name="course" value="<?php echo htmlspecialchars($formData['course']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>Year Graduate:</label>
                                <input type="text" name="year_graduate" value="<?php echo htmlspecialchars($formData['year_graduate']); ?>" placeholder="e.g., 2020">
                            </div>
                        </div>
                    </div>

                    <div class="emergency-section">
                        <div class="section-title">CONTACT PERSON IN CASE OF EMERGENCY</div>
                        <div class="info-layout-simple">
                            <div class="info-item">
                                <label>Name:</label>
                                <input type="text" name="emergency_name" value="<?php echo htmlspecialchars($formData['emergency_name']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>Relationship:</label>
                                <input type="text" name="relationship" value="<?php echo htmlspecialchars($formData['relationship']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>Address:</label>
                                <input type="text" name="emergency_address" value="<?php echo htmlspecialchars($formData['emergency_address']); ?>" >
                            </div>
                            <div class="info-item">
                                <label>Mobile No.:</label>
                                <input type="tel" name="mobile_no" value="<?php echo htmlspecialchars($formData['mobile_no']); ?>" >
                            </div>
                        </div>
                    </div>

                    <div class="document-section">
                        <div class="section-title">DOCUMENT TYPE</div>
                        <div class="document-table-wrapper">
                            <table class="doc-table">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Number</th>
                                        <th>Issued By</th>
                                        <th>Issued Date</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Seaman's Book</td>
                                        <td><input type="text" name="seaman_book_no" value="<?php echo htmlspecialchars($formData['seaman_book_no']); ?>"></td>
                                        <td><input type="text" name="seaman_book_issued_by" value="<?php echo htmlspecialchars($formData['seaman_book_issued_by']); ?>"></td>
                                        <td><input type="date" name="seaman_book_issued_date" value="<?php echo htmlspecialchars($formData['seaman_book_issued_date']); ?>"></td>
                                        <td><input type="date" name="seaman_book_expiry_date" value="<?php echo htmlspecialchars($formData['seaman_book_expiry_date']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>Mariner ID</td>
                                        <td><input type="text" name="mariner_id_no"></td>
                                        <td><input type="text" name="mariner_id_issued_by"></td>
                                        <td><input type="date" name="mariner_id_issued_date"></td>
                                        <td><input type="date" name="mariner_id_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>SID</td>
                                        <td><input type="text" name="sid" value="<?php echo htmlspecialchars($formData['sid']); ?>"></td>
                                        <td><input type="text" name="sid_issued_by"></td>
                                        <td><input type="date" name="sid_issued_date"></td>
                                        <td><input type="date" name="sid_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>DCOC</td>
                                        <td><input type="text" name="dcoc" value="<?php echo htmlspecialchars($formData['dcoc']); ?>"></td>
                                        <td><input type="text" name="dcoc_issued_by"></td>
                                        <td><input type="date" name="dcoc_issued_date"></td>
                                        <td><input type="date" name="dcoc_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>NBI</td>
                                        <td><input type="text" name="nbi" value="<?php echo htmlspecialchars($formData['nbi']); ?>"></td>
                                        <td><input type="text" name="nbi_issued_by"></td>
                                        <td><input type="date" name="nbi_issued_date"></td>
                                        <td><input type="date" name="nbi_expiry_date"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="training-section">
                        <div class="section-title">TRAINING & CERTIFICATES</div>
                        <div class="document-table-wrapper">
                            <table class="doc-table">
                                <thead>
                                    <tr>
                                        <th>Certificate Type</th>
                                        <th>Number</th>
                                        <th>Issued By</th>
                                        <th>Issued Date</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Basic Training</td>
                                        <td><input type="text" name="basic_training" value="<?php echo htmlspecialchars($formData['basic_training']); ?>"></td>
                                        <td><input type="text" name="basic_training_issued_by"></td>
                                        <td><input type="date" name="basic_training_issued_date"></td>
                                        <td><input type="date" name="basic_training_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>BT Cop</td>
                                        <td><input type="text" name="bt_cop" value="<?php echo htmlspecialchars($formData['bt_cop']); ?>"></td>
                                        <td><input type="text" name="bt_cop_issued_by"></td>
                                        <td><input type="date" name="bt_cop_issued_date"></td>
                                        <td><input type="date" name="bt_cop_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>PSCRB</td>
                                        <td><input type="text" name="pscrb" value="<?php echo htmlspecialchars($formData['pscrb']); ?>"></td>
                                        <td><input type="text" name="pscrb_issued_by"></td>
                                        <td><input type="date" name="pscrb_issued_date"></td>
                                        <td><input type="date" name="pscrb_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>PSCRB Cop</td>
                                        <td><input type="text" name="pscrb_cop" value="<?php echo htmlspecialchars($formData['pscrb_cop']); ?>"></td>
                                        <td><input type="text" name="pscrb_cop_issued_by"></td>
                                        <td><input type="date" name="pscrb_cop_issued_date"></td>
                                        <td><input type="date" name="pscrb_cop_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>SDSD</td>
                                        <td><input type="text" name="sdsd" value="<?php echo htmlspecialchars($formData['sdsd']); ?>"></td>
                                        <td><input type="text" name="sdsd_issued_by"></td>
                                        <td><input type="date" name="sdsd_issued_date"></td>
                                        <td><input type="date" name="sdsd_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>SDSD Cop</td>
                                        <td><input type="text" name="sdsd_cop" value="<?php echo htmlspecialchars($formData['sdsd_cop']); ?>"></td>
                                        <td><input type="text" name="sdsd_cop_issued_by"></td>
                                        <td><input type="date" name="sdsd_cop_issued_date"></td>
                                        <td><input type="date" name="sdsd_cop_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>AFF</td>
                                        <td><input type="text" name="aff" value="<?php echo htmlspecialchars($formData['aff']); ?>"></td>
                                        <td><input type="text" name="aff_issued_by"></td>
                                        <td><input type="date" name="aff_issued_date"></td>
                                        <td><input type="date" name="aff_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>AFF Cop</td>
                                        <td><input type="text" name="aff_cop" value="<?php echo htmlspecialchars($formData['aff_cop']); ?>"></td>
                                        <td><input type="text" name="aff_cop_issued_by"></td>
                                        <td><input type="date" name="aff_cop_issued_date"></td>
                                        <td><input type="date" name="aff_cop_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>MECA/MEFA</td>
                                        <td><input type="text" name="meca_mefa" value="<?php echo htmlspecialchars($formData['meca_mefa']); ?>"></td>
                                        <td><input type="text" name="meca_mefa_issued_by"></td>
                                        <td><input type="date" name="meca_mefa_issued_date"></td>
                                        <td><input type="date" name="meca_mefa_expiry_date"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="login.php" class="btn btn-back">← BACK TO LOGIN</a>
                        <button type="submit" name="action" value="next" class="btn btn-next">NEXT →</button>
                    </div>

                <?php elseif ($currentPage === 2): ?>
                    <!-- PAGE 2: Additional Certificates and Sea Service Record -->
                    <div class="training-section">
                        <div class="section-title">ADDITIONAL CERTIFICATES</div>
                        <div class="document-table-wrapper">
                            <table class="doc-table">
                                <thead>
                                    <tr>
                                        <th>Certificate Type</th>
                                        <th>Number</th>
                                        <th>Issued By</th>
                                        <th>Issued Date</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>MEC/MEFA COP</td>
                                        <td><input type="text" name="mec_mefa_cop"></td>
                                        <td><input type="text" name="mec_mefa_cop_issued_by"></td>
                                        <td><input type="date" name="mec_mefa_cop_issued_date"></td>
                                        <td><input type="date" name="mec_mefa_cop_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>MARPOL</td>
                                        <td><input type="text" name="marpol"></td>
                                        <td><input type="text" name="marpol_issued_by"></td>
                                        <td><input type="date" name="marpol_issued_date"></td>
                                        <td><input type="date" name="marpol_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>SSO</td>
                                        <td><input type="text" name="sso"></td>
                                        <td><input type="text" name="sso_issued_by"></td>
                                        <td><input type="date" name="sso_issued_date"></td>
                                        <td><input type="date" name="sso_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Deck/Engine/Watch Keeping</td>
                                        <td><input type="text" name="watch_keeping"></td>
                                        <td><input type="text" name="watch_keeping_issued_by"></td>
                                        <td><input type="date" name="watch_keeping_issued_date"></td>
                                        <td><input type="date" name="watch_keeping_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>GMDSS</td>
                                        <td><input type="text" name="gmdss"></td>
                                        <td><input type="text" name="gmdss_issued_by"></td>
                                        <td><input type="date" name="gmdss_issued_date"></td>
                                        <td><input type="date" name="gmdss_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Safe Navigation & COLREG</td>
                                        <td><input type="text" name="safe_nav"></td>
                                        <td><input type="text" name="safe_nav_issued_by"></td>
                                        <td><input type="date" name="safe_nav_issued_date"></td>
                                        <td><input type="date" name="safe_nav_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>MARITIME LAW</td>
                                        <td><input type="text" name="maritime_law"></td>
                                        <td><input type="text" name="maritime_law_issued_by"></td>
                                        <td><input type="date" name="maritime_law_issued_date"></td>
                                        <td><input type="date" name="maritime_law_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>NC-1/NC-2</td>
                                        <td><input type="text" name="nc"></td>
                                        <td><input type="text" name="nc_issued_by"></td>
                                        <td><input type="date" name="nc_issued_date"></td>
                                        <td><input type="date" name="nc_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>DP</td>
                                        <td><input type="text" name="dp"></td>
                                        <td><input type="text" name="dp_issued_by"></td>
                                        <td><input type="date" name="dp_issued_date"></td>
                                        <td><input type="date" name="dp_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>PADAMS</td>
                                        <td><input type="text" name="padams"></td>
                                        <td><input type="text" name="padams_issued_by"></td>
                                        <td><input type="date" name="padams_issued_date"></td>
                                        <td><input type="date" name="padams_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>HAZMAT</td>
                                        <td><input type="text" name="hazmat"></td>
                                        <td><input type="text" name="hazmat_issued_by"></td>
                                        <td><input type="date" name="hazmat_issued_date"></td>
                                        <td><input type="date" name="hazmat_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>ECDIS</td>
                                        <td><input type="text" name="ecdis"></td>
                                        <td><input type="text" name="ecdis_issued_by"></td>
                                        <td><input type="date" name="ecdis_issued_date"></td>
                                        <td><input type="date" name="ecdis_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>BTOC</td>
                                        <td><input type="text" name="btoc"></td>
                                        <td><input type="text" name="btoc_issued_by"></td>
                                        <td><input type="date" name="btoc_issued_date"></td>
                                        <td><input type="date" name="btoc_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Cargo Handling & Storage</td>
                                        <td><input type="text" name="cargo_handling"></td>
                                        <td><input type="text" name="cargo_handling_issued_by"></td>
                                        <td><input type="date" name="cargo_handling_issued_date"></td>
                                        <td><input type="date" name="cargo_handling_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Fast Rescue Boat</td>
                                        <td><input type="text" name="fast_rescue"></td>
                                        <td><input type="text" name="fast_rescue_issued_by"></td>
                                        <td><input type="date" name="fast_rescue_issued_date"></td>
                                        <td><input type="date" name="fast_rescue_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Risk Assessment</td>
                                        <td><input type="text" name="risk_assessment"></td>
                                        <td><input type="text" name="risk_assessment_issued_by"></td>
                                        <td><input type="date" name="risk_assessment_issued_date"></td>
                                        <td><input type="date" name="risk_assessment_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Ship Simulator & Bridge</td>
                                        <td><input type="text" name="ship_simulator"></td>
                                        <td><input type="text" name="ship_simulator_issued_by"></td>
                                        <td><input type="date" name="ship_simulator_issued_date"></td>
                                        <td><input type="date" name="ship_simulator_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>Emergency Awareness</td>
                                        <td><input type="text" name="emergency_awareness"></td>
                                        <td><input type="text" name="emergency_awareness_issued_by"></td>
                                        <td><input type="date" name="emergency_awareness_issued_date"></td>
                                        <td><input type="date" name="emergency_awareness_expiry_date"></td>
                                    </tr>
                                    <tr>
                                        <td>CCM</td>
                                        <td><input type="text" name="ccm"></td>
                                        <td><input type="text" name="ccm_issued_by"></td>
                                        <td><input type="date" name="ccm_issued_date"></td>
                                        <td><input type="date" name="ccm_expiry_date"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sea-service-section">
                        <div class="section-title">SEA SERVICE RECORD</div>
                        <div class="document-table-wrapper">
                            <table class="doc-table sea-service-table">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Name of Vessel</th>
                                        <th>Company</th>
                                        <th>Type of Vessel</th>
                                        <th>GRT/KWT</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Total Months</th>
                                        <th>Reason of Sign Off</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" name="sea_position_1"></td>
                                        <td><input type="text" name="sea_vessel_1"></td>
                                        <td><input type="text" name="sea_company_1"></td>
                                        <td><input type="text" name="sea_type_1"></td>
                                        <td><input type="text" name="sea_grt_1"></td>
                                        <td><input type="date" name="sea_from_1"></td>
                                        <td><input type="date" name="sea_to_1"></td>
                                        <td><input type="text" name="sea_months_1"></td>
                                        <td><input type="text" name="sea_reason_1"></td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" name="sea_position_2"></td>
                                        <td><input type="text" name="sea_vessel_2"></td>
                                        <td><input type="text" name="sea_company_2"></td>
                                        <td><input type="text" name="sea_type_2"></td>
                                        <td><input type="text" name="sea_grt_2"></td>
                                        <td><input type="date" name="sea_from_2"></td>
                                        <td><input type="date" name="sea_to_2"></td>
                                        <td><input type="text" name="sea_months_2"></td>
                                        <td><input type="text" name="sea_reason_2"></td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" name="sea_position_3"></td>
                                        <td><input type="text" name="sea_vessel_3"></td>
                                        <td><input type="text" name="sea_company_3"></td>
                                        <td><input type="text" name="sea_type_3"></td>
                                        <td><input type="text" name="sea_grt_3"></td>
                                        <td><input type="date" name="sea_from_3"></td>
                                        <td><input type="date" name="sea_to_3"></td>
                                        <td><input type="text" name="sea_months_3"></td>
                                        <td><input type="text" name="sea_reason_3"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="action" value="back" class="btn btn-back">← BACK</button>
                        <button type="submit" name="action" value="next" class="btn btn-next">NEXT →</button>
                    </div>

                <?php elseif ($currentPage === 3): ?>
                    <!-- PAGE 3: Certificate Requirements Checklist -->
                    <div class="checklist-container">
                        <div class="checklist-column">
                            <h3 class="section-title">LIST OF CERTIFICATE REQUIREMENTS</h3>
                            
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_bt"> BT (Basic Training)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_ratings"> Ratings Forming Part of Nav. Watch</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_nav_watch_24"> Nav. Watch 2/4 w/ COP</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_eng_watch_34"> Eng. Watch 3/4 w/ COP</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_nav_watch_25"> Nav. Watch 2/5 w/ COP</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_eng_watch_35"> Eng. Watch 3/5 w/ COP</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_sdsd"> SDSD (Seafarer's Designated Security Duties)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_atot"> ATOT (Advance Training for Oil Tanker)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_atot_cargo"> ATOT (Advance Training for Oil Tanker Cargo)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_mefa"> MEFA (Medical First Aid)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_btoc"> BTOC (for tankers only)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_aff"> AFF (Advance training and firefighting)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_marpol"> Consolidated Marpol 1 to 6</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_pscrb"> P.S.C.R.B (Proficiency in Survival Craft and Rescue Boat)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_ism"> ISM-R (International Safety Management for Ratings)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="cert_rac"> RAC (Risk Assessment Certificate)</label>
                            </div>
                        </div>

                        <div class="checklist-column">
                            <h3 class="section-title">LIST OF TRAININGS/CERTIFICATES FOR OFFICERS</h3>
                            
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_bt"> BT (Basic Training)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_bsc"> B.S.C (Radar Simulator Course)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_brm"> BRM (Bridge Resource Management)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_erm"> ERM (Engine Room Resource Management) Function 1 to 4</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_radio"> Radio Communication / GMDSS</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_ssbt"> SSBT (Ship Simulator and BridgeTeamwork)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_atot"> ATOT (Advance Training for Oil Tanker Cargo)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_radar"> Radar&ARPA Certificate</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_mefa"> MEFA (Medical First Aid)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_aff"> AFF (Advance training and firefighting)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_sncr"> SNCR (Safe Navigation and Collision Regulations)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_pscrb"> P.S.C.R.B (Proficiency in Survival Craft and Rescue Boat)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_meca"> MECA (Medical Care)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_sso"> SSO (Ship Security Officer)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_marpol"> Consolidated Marpol 1 to 6</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_mlc"> Management Level Certificate</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_btoc"> BTOC (For tankers only)</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Estimated Date to Embark:</label>
                            <input type="date" name="embark_date">
                        </div>
                        <div class="form-group">
                            <label>Expected Salary:</label>
                            <input type="text" name="expected_salary" placeholder="e.g., $1,500/month">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="action" value="back" class="btn btn-back">← BACK</button>
                        <button type="submit" name="action" value="submit" class="btn btn-submit">SUBMIT APPLICATION</button>
                    </div>

                <?php endif; ?>
            </form>
        </div>

    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
</body>
</html>
