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
        'meca_mefa' => '',
        // Additional certificates page fields
        'mec_mefa_cop' => '',
        'mec_mefa_cop_issued_by' => '',
        'mec_mefa_cop_issued_date' => '',
        'mec_mefa_cop_expiry_date' => '',
        'marpol' => '',
        'marpol_issued_by' => '',
        'marpol_issued_date' => '',
        'marpol_expiry_date' => '',
        'sso' => '',
        'sso_issued_by' => '',
        'sso_issued_date' => '',
        'sso_expiry_date' => '',
        'watch_keeping' => '',
        'watch_keeping_issued_by' => '',
        'watch_keeping_issued_date' => '',
        'watch_keeping_expiry_date' => '',
        'gmdss' => '',
        'gmdss_issued_by' => '',
        'gmdss_issued_date' => '',
        'gmdss_expiry_date' => '',
        'safe_nav' => '',
        'safe_nav_issued_by' => '',
        'safe_nav_issued_date' => '',
        'safe_nav_expiry_date' => '',
        'maritime_law' => '',
        'maritime_law_issued_by' => '',
        'maritime_law_issued_date' => '',
        'maritime_law_expiry_date' => '',
        'nc' => '',
        'nc_issued_by' => '',
        'nc_issued_date' => '',
        'nc_expiry_date' => '',
        'dp' => '',
        'dp_issued_by' => '',
        'dp_issued_date' => '',
        'dp_expiry_date' => '',
        'padams' => '',
        'padams_issued_by' => '',
        'padams_issued_date' => '',
        'padams_expiry_date' => '',
        'hazmat' => '',
        'hazmat_issued_by' => '',
        'hazmat_issued_date' => '',
        'hazmat_expiry_date' => '',
        'ecdis' => '',
        'ecdis_issued_by' => '',
        'ecdis_issued_date' => '',
        'ecdis_expiry_date' => '',
        'btoc' => '',
        'btoc_issued_by' => '',
        'btoc_issued_date' => '',
        'btoc_expiry_date' => '',
        'cargo_handling' => '',
        'cargo_handling_issued_by' => '',
        'cargo_handling_issued_date' => '',
        'cargo_handling_expiry_date' => '',
        'fast_rescue' => '',
        'fast_rescue_issued_by' => '',
        'fast_rescue_issued_date' => '',
        'fast_rescue_expiry_date' => '',
        'risk_assessment' => '',
        'risk_assessment_issued_by' => '',
        'risk_assessment_issued_date' => '',
        'risk_assessment_expiry_date' => '',
        'ship_simulator' => '',
        'ship_simulator_issued_by' => '',
        'ship_simulator_issued_date' => '',
        'ship_simulator_expiry_date' => '',
        'emergency_awareness' => '',
        'emergency_awareness_issued_by' => '',
        'emergency_awareness_issued_date' => '',
        'emergency_awareness_expiry_date' => '',
        'ccm' => '',
        'ccm_issued_by' => '',
        'ccm_issued_date' => '',
        'ccm_expiry_date' => '',
        // Sea service fields
        'sea_position_1' => '',
        'sea_vessel_1' => '',
        'sea_company_1' => '',
        'sea_type_1' => '',
        'sea_grt_1' => '',
        'sea_from_1' => '',
        'sea_to_1' => '',
        'sea_months_1' => '',
        'sea_reason_1' => '',
        'sea_position_2' => '',
        'sea_vessel_2' => '',
        'sea_company_2' => '',
        'sea_type_2' => '',
        'sea_grt_2' => '',
        'sea_from_2' => '',
        'sea_to_2' => '',
        'sea_months_2' => '',
        'sea_reason_2' => '',
        'sea_position_3' => '',
        'sea_vessel_3' => '',
        'sea_company_3' => '',
        'sea_type_3' => '',
        'sea_grt_3' => '',
        'sea_from_3' => '',
        'sea_to_3' => '',
        'sea_months_3' => '',
        'sea_reason_3' => '',
        // Final page fields
        'embark_date' => '',
        'expected_salary' => ''
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lightweight autosave for position selection (AJAX)
    if (isset($_POST['autosave']) && $_POST['autosave'] === 'position') {
        $positionValue = $_POST['position_applied'] ?? '';
        if (isset($_SESSION['application_data']['position_applied'])) {
            $_SESSION['application_data']['position_applied'] = $positionValue;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

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
            
            // Prepare documents data as JSON (always from session, includes page 1 values)
            $documents = [
                'seaman_book' => [
                    'number' => $_SESSION['application_data']['seaman_book_no'] ?? '',
                    'issued_by' => $_SESSION['application_data']['seaman_book_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['seaman_book_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['seaman_book_expiry_date'] ?? ''
                ],
                'mariner_id' => [
                    'number' => $_SESSION['application_data']['mariner_id_no'] ?? '',
                    'issued_by' => $_SESSION['application_data']['mariner_id_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['mariner_id_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['mariner_id_expiry_date'] ?? ''
                ],
                'sid' => [
                    'number' => $_SESSION['application_data']['sid'] ?? '',
                    'issued_by' => $_SESSION['application_data']['sid_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['sid_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['sid_expiry_date'] ?? ''
                ],
                'dcoc' => [
                    'number' => $_SESSION['application_data']['dcoc'] ?? '',
                    'issued_by' => $_SESSION['application_data']['dcoc_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['dcoc_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['dcoc_expiry_date'] ?? ''
                ],
                'nbi' => [
                    'number' => $_SESSION['application_data']['nbi'] ?? '',
                    'issued_by' => $_SESSION['application_data']['nbi_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['nbi_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['nbi_expiry_date'] ?? ''
                ]
            ];
            
            // Prepare training certificates as JSON (always from session)
            $training_certificates = [
                'basic_training' => [
                    'number' => $_SESSION['application_data']['basic_training'] ?? '',
                    'issued_by' => $_SESSION['application_data']['basic_training_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['basic_training_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['basic_training_expiry_date'] ?? ''
                ],
                'bt_cop' => [
                    'number' => $_SESSION['application_data']['bt_cop'] ?? '',
                    'issued_by' => $_SESSION['application_data']['bt_cop_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['bt_cop_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['bt_cop_expiry_date'] ?? ''
                ],
                'pscrb' => [
                    'number' => $_SESSION['application_data']['pscrb'] ?? '',
                    'issued_by' => $_SESSION['application_data']['pscrb_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['pscrb_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['pscrb_expiry_date'] ?? ''
                ],
                'pscrb_cop' => [
                    'number' => $_SESSION['application_data']['pscrb_cop'] ?? '',
                    'issued_by' => $_SESSION['application_data']['pscrb_cop_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['pscrb_cop_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['pscrb_cop_expiry_date'] ?? ''
                ],
                'sdsd' => [
                    'number' => $_SESSION['application_data']['sdsd'] ?? '',
                    'issued_by' => $_SESSION['application_data']['sdsd_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['sdsd_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['sdsd_expiry_date'] ?? ''
                ],
                'sdsd_cop' => [
                    'number' => $_SESSION['application_data']['sdsd_cop'] ?? '',
                    'issued_by' => $_SESSION['application_data']['sdsd_cop_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['sdsd_cop_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['sdsd_cop_expiry_date'] ?? ''
                ],
                'aff' => [
                    'number' => $_SESSION['application_data']['aff'] ?? '',
                    'issued_by' => $_SESSION['application_data']['aff_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['aff_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['aff_expiry_date'] ?? ''
                ],
                'aff_cop' => [
                    'number' => $_SESSION['application_data']['aff_cop'] ?? '',
                    'issued_by' => $_SESSION['application_data']['aff_cop_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['aff_cop_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['aff_cop_expiry_date'] ?? ''
                ],
                'meca_mefa' => [
                    'number' => $_SESSION['application_data']['meca_mefa'] ?? '',
                    'issued_by' => $_SESSION['application_data']['meca_mefa_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['meca_mefa_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['meca_mefa_expiry_date'] ?? ''
                ]
            ];
            
            // Prepare additional certificates as JSON (always from session)
            $additional_certificates = [
                'mec_mefa_cop' => [
                    'number' => $_SESSION['application_data']['mec_mefa_cop'] ?? '',
                    'issued_by' => $_SESSION['application_data']['mec_mefa_cop_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['mec_mefa_cop_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['mec_mefa_cop_expiry_date'] ?? ''
                ],
                'marpol' => [
                    'number' => $_SESSION['application_data']['marpol'] ?? '',
                    'issued_by' => $_SESSION['application_data']['marpol_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['marpol_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['marpol_expiry_date'] ?? ''
                ],
                'sso' => [
                    'number' => $_SESSION['application_data']['sso'] ?? '',
                    'issued_by' => $_SESSION['application_data']['sso_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['sso_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['sso_expiry_date'] ?? ''
                ],
                'watch_keeping' => [
                    'number' => $_SESSION['application_data']['watch_keeping'] ?? '',
                    'issued_by' => $_SESSION['application_data']['watch_keeping_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['watch_keeping_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['watch_keeping_expiry_date'] ?? ''
                ],
                'gmdss' => [
                    'number' => $_SESSION['application_data']['gmdss'] ?? '',
                    'issued_by' => $_SESSION['application_data']['gmdss_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['gmdss_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['gmdss_expiry_date'] ?? ''
                ],
                'safe_nav' => [
                    'number' => $_SESSION['application_data']['safe_nav'] ?? '',
                    'issued_by' => $_SESSION['application_data']['safe_nav_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['safe_nav_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['safe_nav_expiry_date'] ?? ''
                ],
                'maritime_law' => [
                    'number' => $_SESSION['application_data']['maritime_law'] ?? '',
                    'issued_by' => $_SESSION['application_data']['maritime_law_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['maritime_law_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['maritime_law_expiry_date'] ?? ''
                ],
                'nc' => [
                    'number' => $_SESSION['application_data']['nc'] ?? '',
                    'issued_by' => $_SESSION['application_data']['nc_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['nc_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['nc_expiry_date'] ?? ''
                ],
                'dp' => [
                    'number' => $_SESSION['application_data']['dp'] ?? '',
                    'issued_by' => $_SESSION['application_data']['dp_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['dp_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['dp_expiry_date'] ?? ''
                ],
                'padams' => [
                    'number' => $_SESSION['application_data']['padams'] ?? '',
                    'issued_by' => $_SESSION['application_data']['padams_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['padams_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['padams_expiry_date'] ?? ''
                ],
                'hazmat' => [
                    'number' => $_SESSION['application_data']['hazmat'] ?? '',
                    'issued_by' => $_SESSION['application_data']['hazmat_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['hazmat_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['hazmat_expiry_date'] ?? ''
                ],
                'ecdis' => [
                    'number' => $_SESSION['application_data']['ecdis'] ?? '',
                    'issued_by' => $_SESSION['application_data']['ecdis_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['ecdis_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['ecdis_expiry_date'] ?? ''
                ],
                'btoc' => [
                    'number' => $_SESSION['application_data']['btoc'] ?? '',
                    'issued_by' => $_SESSION['application_data']['btoc_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['btoc_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['btoc_expiry_date'] ?? ''
                ],
                'cargo_handling' => [
                    'number' => $_SESSION['application_data']['cargo_handling'] ?? '',
                    'issued_by' => $_SESSION['application_data']['cargo_handling_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['cargo_handling_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['cargo_handling_expiry_date'] ?? ''
                ],
                'fast_rescue' => [
                    'number' => $_SESSION['application_data']['fast_rescue'] ?? '',
                    'issued_by' => $_SESSION['application_data']['fast_rescue_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['fast_rescue_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['fast_rescue_expiry_date'] ?? ''
                ],
                'risk_assessment' => [
                    'number' => $_SESSION['application_data']['risk_assessment'] ?? '',
                    'issued_by' => $_SESSION['application_data']['risk_assessment_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['risk_assessment_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['risk_assessment_expiry_date'] ?? ''
                ],
                'ship_simulator' => [
                    'number' => $_SESSION['application_data']['ship_simulator'] ?? '',
                    'issued_by' => $_SESSION['application_data']['ship_simulator_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['ship_simulator_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['ship_simulator_expiry_date'] ?? ''
                ],
                'brm' => [
                    'number' => $_SESSION['application_data']['brm'] ?? '',
                    'issued_by' => $_SESSION['application_data']['brm_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['brm_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['brm_expiry_date'] ?? ''
                ],
                'ssbt' => [
                    'number' => $_SESSION['application_data']['ssbt'] ?? '',
                    'issued_by' => $_SESSION['application_data']['ssbt_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['ssbt_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['ssbt_expiry_date'] ?? ''
                ],
                'emergency_awareness' => [
                    'number' => $_SESSION['application_data']['emergency_awareness'] ?? '',
                    'issued_by' => $_SESSION['application_data']['emergency_awareness_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['emergency_awareness_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['emergency_awareness_expiry_date'] ?? ''
                ],
                'ccm' => [
                    'number' => $_SESSION['application_data']['ccm'] ?? '',
                    'issued_by' => $_SESSION['application_data']['ccm_issued_by'] ?? '',
                    'issued_date' => $_SESSION['application_data']['ccm_issued_date'] ?? '',
                    'expiry_date' => $_SESSION['application_data']['ccm_expiry_date'] ?? ''
                ]
            ];
            
            // Prepare sea service record as JSON (always from session / page 2)
            $sea_service_record = [
                [
                    'position' => $_SESSION['application_data']['sea_position_1'] ?? '',
                    'vessel' => $_SESSION['application_data']['sea_vessel_1'] ?? '',
                    'company' => $_SESSION['application_data']['sea_company_1'] ?? '',
                    'type' => $_SESSION['application_data']['sea_type_1'] ?? '',
                    'grt' => $_SESSION['application_data']['sea_grt_1'] ?? '',
                    'from' => $_SESSION['application_data']['sea_from_1'] ?? '',
                    'to' => $_SESSION['application_data']['sea_to_1'] ?? '',
                    'months' => $_SESSION['application_data']['sea_months_1'] ?? '',
                    'reason' => $_SESSION['application_data']['sea_reason_1'] ?? ''
                ],
                [
                    'position' => $_SESSION['application_data']['sea_position_2'] ?? '',
                    'vessel' => $_SESSION['application_data']['sea_vessel_2'] ?? '',
                    'company' => $_SESSION['application_data']['sea_company_2'] ?? '',
                    'type' => $_SESSION['application_data']['sea_type_2'] ?? '',
                    'grt' => $_SESSION['application_data']['sea_grt_2'] ?? '',
                    'from' => $_SESSION['application_data']['sea_from_2'] ?? '',
                    'to' => $_SESSION['application_data']['sea_to_2'] ?? '',
                    'months' => $_SESSION['application_data']['sea_months_2'] ?? '',
                    'reason' => $_SESSION['application_data']['sea_reason_2'] ?? ''
                ],
                [
                    'position' => $_SESSION['application_data']['sea_position_3'] ?? '',
                    'vessel' => $_SESSION['application_data']['sea_vessel_3'] ?? '',
                    'company' => $_SESSION['application_data']['sea_company_3'] ?? '',
                    'type' => $_SESSION['application_data']['sea_type_3'] ?? '',
                    'grt' => $_SESSION['application_data']['sea_grt_3'] ?? '',
                    'from' => $_SESSION['application_data']['sea_from_3'] ?? '',
                    'to' => $_SESSION['application_data']['sea_to_3'] ?? '',
                    'months' => $_SESSION['application_data']['sea_months_3'] ?? '',
                    'reason' => $_SESSION['application_data']['sea_reason_3'] ?? ''
                ]
            ];
            
            // Prepare certificate checklist as JSON
            $certificate_checklist = [
                'ratings' => [
                    'rating_bt' => !empty($_POST['rating_bt_hidden']) ? 1 : 0,
                    'rating_rfpnw' => !empty($_POST['rating_rfpnw_hidden']) ? 1 : 0,
                    'rating_nav_watch_24_cop' => !empty($_POST['rating_nav_watch_24_cop_hidden']) ? 1 : 0,
                    'rating_nav_watch_25_cop' => !empty($_POST['rating_nav_watch_25_cop_hidden']) ? 1 : 0,
                    'rating_sdsd' => !empty($_POST['rating_sdsd_hidden']) ? 1 : 0,
                    'rating_atot' => !empty($_POST['rating_atot_hidden']) ? 1 : 0,
                    'rating_btoc' => !empty($_POST['rating_btoc_hidden']) ? 1 : 0,
                    'rating_aff' => !empty($_POST['rating_aff_hidden']) ? 1 : 0,
                    'rating_marpol_1_6' => !empty($_POST['rating_marpol_1_6_hidden']) ? 1 : 0,
                    'rating_pscrb' => !empty($_POST['rating_pscrb_hidden']) ? 1 : 0,
                    'rating_ism_r' => !empty($_POST['rating_ism_r_hidden']) ? 1 : 0,
                    'rating_nc_1' => !empty($_POST['rating_nc_1_hidden']) ? 1 : 0,
                    'rating_nc_2' => !empty($_POST['rating_nc_2_hidden']) ? 1 : 0,
                    'rating_quarantine_certificate' => !empty($_POST['rating_quarantine_certificate_hidden']) ? 1 : 0
                ],
                'officers' => [
                    'officer_bt' => !empty($_POST['officer_bt_hidden']) ? 1 : 0,
                    'officer_ismo' => !empty($_POST['officer_ismo_hidden']) ? 1 : 0,
                    'officer_rsc' => !empty($_POST['officer_rsc_hidden']) ? 1 : 0,
                    'officer_brm' => !empty($_POST['officer_brm_hidden']) ? 1 : 0,
                    'officer_radio_gmdss' => !empty($_POST['officer_radio_gmdss_hidden']) ? 1 : 0,
                    'officer_ssbt' => !empty($_POST['officer_ssbt_hidden']) ? 1 : 0,
                    'officer_atot' => !empty($_POST['officer_atot_hidden']) ? 1 : 0,
                    'officer_mefa' => !empty($_POST['officer_mefa_hidden']) ? 1 : 0,
                    'officer_aff' => !empty($_POST['officer_aff_hidden']) ? 1 : 0,
                    'officer_pscrb' => !empty($_POST['officer_pscrb_hidden']) ? 1 : 0,
                    'officer_meca' => !empty($_POST['officer_meca_hidden']) ? 1 : 0,
                    'officer_sso' => !empty($_POST['officer_sso_hidden']) ? 1 : 0,
                    'officer_consolidated' => !empty($_POST['officer_consolidated_hidden']) ? 1 : 0,
                    'officer_mlc' => !empty($_POST['officer_mlc_hidden']) ? 1 : 0,
                    'officer_btoc' => !empty($_POST['officer_btoc_hidden']) ? 1 : 0
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
                                <select name="position_applied">
                                    <option value="">Select Position</option>
                                    <?php
                                    $positionOptions = [
                                        '3rd Mate / 3rd Officer',
                                        '2nd Mate / 2nd Officer',
                                        'Chief Mate / Chief Officer',
                                        'Captain / Master',
                                        '4th Engineer',
                                        '3rd Engineer',
                                        '2nd Engineer',
                                        'Chief Engineer',
                                        'D/C',
                                        'A/B',
                                        'MSM (Messman)',
                                        'Bosun',
                                        'Cook',
                                        'E/C',
                                        'Oiler',
                                        'Fitter/Welder',
                                        'Electrician',
                                        'Wiper'
                                    ];
                                    foreach ($positionOptions as $pos):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($pos); ?>" <?php echo ($formData['position_applied'] === $pos) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                                        <td><input type="text" name="mariner_id_no" value="<?php echo htmlspecialchars($formData['mariner_id_no'] ?? ''); ?>"></td>
                                        <td><input type="text" name="mariner_id_issued_by" value="<?php echo htmlspecialchars($formData['mariner_id_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="mariner_id_issued_date" value="<?php echo htmlspecialchars($formData['mariner_id_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="mariner_id_expiry_date" value="<?php echo htmlspecialchars($formData['mariner_id_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>SID</td>
                                        <td><input type="text" name="sid" value="<?php echo htmlspecialchars($formData['sid']); ?>"></td>
                                        <td><input type="text" name="sid_issued_by" value="<?php echo htmlspecialchars($formData['sid_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sid_issued_date" value="<?php echo htmlspecialchars($formData['sid_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sid_expiry_date" value="<?php echo htmlspecialchars($formData['sid_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>DCOC</td>
                                        <td><input type="text" name="dcoc" value="<?php echo htmlspecialchars($formData['dcoc']); ?>"></td>
                                        <td><input type="text" name="dcoc_issued_by" value="<?php echo htmlspecialchars($formData['dcoc_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="dcoc_issued_date" value="<?php echo htmlspecialchars($formData['dcoc_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="dcoc_expiry_date" value="<?php echo htmlspecialchars($formData['dcoc_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>NBI</td>
                                        <td><input type="text" name="nbi" value="<?php echo htmlspecialchars($formData['nbi']); ?>"></td>
                                        <td><input type="text" name="nbi_issued_by" value="<?php echo htmlspecialchars($formData['nbi_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="nbi_issued_date" value="<?php echo htmlspecialchars($formData['nbi_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="nbi_expiry_date" value="<?php echo htmlspecialchars($formData['nbi_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php
                    $selectedPosition = trim($formData['position_applied'] ?? '');
                    $deckOfficerPositions = [
                        '3rd Mate / 3rd Officer',
                        '2nd Mate / 2nd Officer',
                        'Chief Mate / Chief Officer',
                        'Captain / Master'
                    ];
                    $engineOfficerPositions = [
                        '4th Engineer',
                        '3rd Engineer',
                        '2nd Engineer',
                        'Chief Engineer'
                    ];
                    $deckRatingsPositions = [
                        'D/C',
                        'A/B',
                        'Bosun',
                        'MSM (Messman)',
                        'Cook'
                    ];
                    $engineRatingsPositions = [
                        'E/C',
                        'Oiler',
                        'Fitter/Welder',
                        'Fitter\\Welder',
                        'Electrician',
                        'Wiper'
                    ];
                    $normalizedPosition = strtolower(trim($selectedPosition));

                    $isDeckOfficer = in_array($selectedPosition, $deckOfficerPositions, true);
                    $isEngineOfficer = in_array($selectedPosition, $engineOfficerPositions, true);
                    $isOfficerPosition = $isDeckOfficer || $isEngineOfficer;
                    $isDeckRatings = in_array($selectedPosition, $deckRatingsPositions, true);
                    $isEngineRatings = in_array($selectedPosition, $engineRatingsPositions, true) || in_array($normalizedPosition, ['e/c', 'oiler', 'fitter/welder', 'fitter\\welder', 'electrician', 'wiper'], true);
                    ?>

                    <div class="training-section" style="<?php echo ($isDeckOfficer || $isEngineOfficer || $isDeckRatings || $isEngineRatings) ? '' : 'display:none;'; ?>">
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
                                        <td>BT (Basic Training)</td>
                                        <td><input type="text" name="basic_training" value="<?php echo htmlspecialchars($formData['basic_training'] ?? ''); ?>"></td>
                                        <td><input type="text" name="basic_training_issued_by" value="<?php echo htmlspecialchars($formData['basic_training_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="basic_training_issued_date" value="<?php echo htmlspecialchars($formData['basic_training_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="basic_training_expiry_date" value="<?php echo htmlspecialchars($formData['basic_training_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'Eng. Watch 3/4 w/COP' : ($isDeckRatings ? 'Rating Forming Part of Nav. Watch' : 'ISM-O (International Safety Management for Officers)'); ?></td>
                                        <td><input type="text" name="watch_keeping" value="<?php echo htmlspecialchars($formData['watch_keeping'] ?? ''); ?>"></td>
                                        <td><input type="text" name="watch_keeping_issued_by" value="<?php echo htmlspecialchars($formData['watch_keeping_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="watch_keeping_issued_date" value="<?php echo htmlspecialchars($formData['watch_keeping_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="watch_keeping_expiry_date" value="<?php echo htmlspecialchars($formData['watch_keeping_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php
                                            if ($isEngineRatings) {
                                                echo 'Eng. Watch 3/5 w/COP';
                                            } elseif ($isDeckRatings) {
                                                echo 'Nav. Watch 2/4 w/COP';
                                            } elseif ($isEngineOfficer) {
                                                echo 'RAC (Risk Assessment Certificate)';
                                            } else {
                                                echo 'RSC (Radar Simulator Course)';
                                            }
                                            ?>
                                        </td>
                                        <td><input type="text" name="<?php echo $isEngineOfficer ? 'risk_assessment' : 'ship_simulator'; ?>" value="<?php echo htmlspecialchars($isEngineOfficer ? ($formData['risk_assessment'] ?? '') : ($formData['ship_simulator'] ?? '')); ?>"></td>
                                        <td><input type="text" name="<?php echo $isEngineOfficer ? 'risk_assessment_issued_by' : 'ship_simulator_issued_by'; ?>" value="<?php echo htmlspecialchars($isEngineOfficer ? ($formData['risk_assessment_issued_by'] ?? '') : ($formData['ship_simulator_issued_by'] ?? '')); ?>"></td>
                                        <td><input type="date" name="<?php echo $isEngineOfficer ? 'risk_assessment_issued_date' : 'ship_simulator_issued_date'; ?>" value="<?php echo htmlspecialchars($isEngineOfficer ? ($formData['risk_assessment_issued_date'] ?? '') : ($formData['ship_simulator_issued_date'] ?? '')); ?>"></td>
                                        <td><input type="date" name="<?php echo $isEngineOfficer ? 'risk_assessment_expiry_date' : 'ship_simulator_expiry_date'; ?>" value="<?php echo htmlspecialchars($isEngineOfficer ? ($formData['risk_assessment_expiry_date'] ?? '') : ($formData['ship_simulator_expiry_date'] ?? '')); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'SDSD (Seafarer\'s Designated Security Duties)' : ($isDeckRatings ? 'Nav. Watch 2/5 w/COP' : ($isEngineOfficer ? 'ERM (Engine Room Resource Management) - function 1 to 4' : 'BRM (Bridge Resource Management)')); ?></td>
                                        <td><input type="text" name="brm" value="<?php echo htmlspecialchars($formData['brm'] ?? ''); ?>"></td>
                                        <td><input type="text" name="brm_issued_by" value="<?php echo htmlspecialchars($formData['brm_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="brm_issued_date" value="<?php echo htmlspecialchars($formData['brm_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="brm_expiry_date" value="<?php echo htmlspecialchars($formData['brm_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <?php if (!$isEngineOfficer && !$isDeckRatings && !$isEngineRatings): ?>
                                    <tr>
                                        <td>Radio Communication / GMDSS (Global Maritime Distress and Safety System)</td>
                                        <td><input type="text" name="gmdss" value="<?php echo htmlspecialchars($formData['gmdss'] ?? ''); ?>"></td>
                                        <td><input type="text" name="gmdss_issued_by" value="<?php echo htmlspecialchars($formData['gmdss_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="gmdss_issued_date" value="<?php echo htmlspecialchars($formData['gmdss_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="gmdss_expiry_date" value="<?php echo htmlspecialchars($formData['gmdss_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>SSBT (Ship Simulator and Bridge Teamwork)</td>
                                        <td><input type="text" name="ssbt" value="<?php echo htmlspecialchars($formData['ssbt'] ?? ''); ?>"></td>
                                        <td><input type="text" name="ssbt_issued_by" value="<?php echo htmlspecialchars($formData['ssbt_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="ssbt_issued_date" value="<?php echo htmlspecialchars($formData['ssbt_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="ssbt_expiry_date" value="<?php echo htmlspecialchars($formData['ssbt_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><?php echo ($isDeckRatings || $isEngineRatings) ? 'ATOT (Advanced Training for Oil Tanker)' : 'ATOT (Advanced Training for Oil Tanker)'; ?></td>
                                        <td><input type="text" name="cargo_handling" value="<?php echo htmlspecialchars($formData['cargo_handling'] ?? ''); ?>"></td>
                                        <td><input type="text" name="cargo_handling_issued_by" value="<?php echo htmlspecialchars($formData['cargo_handling_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="cargo_handling_issued_date" value="<?php echo htmlspecialchars($formData['cargo_handling_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="cargo_handling_expiry_date" value="<?php echo htmlspecialchars($formData['cargo_handling_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'BTOC (Basic Training for Oil Tanker)' : ($isDeckRatings ? 'ATOT (Advanced Training for Oil Tanker)' : 'MEFA (Medical First Aid)'); ?></td>
                                        <td><input type="text" name="meca_mefa" value="<?php echo htmlspecialchars($formData['meca_mefa'] ?? ''); ?>"></td>
                                        <td><input type="text" name="meca_mefa_issued_by" value="<?php echo htmlspecialchars($formData['meca_mefa_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="meca_mefa_issued_date" value="<?php echo htmlspecialchars($formData['meca_mefa_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="meca_mefa_expiry_date" value="<?php echo htmlspecialchars($formData['meca_mefa_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'AFF (Advanced Fire Fighting)' : ($isDeckRatings ? 'BTOC (Basic Training for Oil Tanker)' : 'AFF (Advanced Fire Fighting)'); ?></td>
                                        <td><input type="text" name="btoc" value="<?php echo htmlspecialchars($formData['btoc'] ?? ''); ?>"></td>
                                        <td><input type="text" name="btoc_issued_by" value="<?php echo htmlspecialchars($formData['btoc_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="btoc_issued_date" value="<?php echo htmlspecialchars($formData['btoc_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="btoc_expiry_date" value="<?php echo htmlspecialchars($formData['btoc_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'Consolidated Marpol 1 to 6' : ($isDeckRatings ? 'AFF (Advanced Fire Fighting)' : 'PSCRB (Proficiency in Survival Craft and Rescue Boats)'); ?></td>
                                        <td><input type="text" name="aff" value="<?php echo htmlspecialchars($formData['aff'] ?? ''); ?>"></td>
                                        <td><input type="text" name="aff_issued_by" value="<?php echo htmlspecialchars($formData['aff_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="aff_issued_date" value="<?php echo htmlspecialchars($formData['aff_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="aff_expiry_date" value="<?php echo htmlspecialchars($formData['aff_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo ($isDeckRatings || $isEngineRatings) ? 'PSCRB (Proficiency in Survival Craft and Rescue Boats)' : 'PSCRB (Proficiency in Survival Craft and Rescue Boats)'; ?></td>
                                        <td><input type="text" name="pscrb" value="<?php echo htmlspecialchars($formData['pscrb'] ?? ''); ?>"></td>
                                        <td><input type="text" name="pscrb_issued_by" value="<?php echo htmlspecialchars($formData['pscrb_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="pscrb_issued_date" value="<?php echo htmlspecialchars($formData['pscrb_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="pscrb_expiry_date" value="<?php echo htmlspecialchars($formData['pscrb_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'ISM-R (International Safety Management for Ratings)' : ($isDeckRatings ? 'PSCRB (Proficiency in Survival Craft and Rescue Boats)' : 'MECA (Medical Care)'); ?></td>
                                        <td><input type="text" name="mec_mefa_cop" value="<?php echo htmlspecialchars($formData['mec_mefa_cop'] ?? ''); ?>"></td>
                                        <td><input type="text" name="mec_mefa_cop_issued_by" value="<?php echo htmlspecialchars($formData['mec_mefa_cop_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="mec_mefa_cop_issued_date" value="<?php echo htmlspecialchars($formData['mec_mefa_cop_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="mec_mefa_cop_expiry_date" value="<?php echo htmlspecialchars($formData['mec_mefa_cop_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'NC -2' : ($isDeckRatings ? 'ISM-R (International Safety Management for Ratings)' : 'SSO (Ship Security Officer)'); ?></td>
                                        <td><input type="text" name="sso" value="<?php echo htmlspecialchars($formData['sso'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sso_issued_by" value="<?php echo htmlspecialchars($formData['sso_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sso_issued_date" value="<?php echo htmlspecialchars($formData['sso_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sso_expiry_date" value="<?php echo htmlspecialchars($formData['sso_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'ETD Certificate' : ($isDeckRatings ? 'NC-1' : 'Consolidated Marpol 1 to 6'); ?></td>
                                        <td><input type="text" name="marpol" value="<?php echo htmlspecialchars($formData['marpol'] ?? ''); ?>"></td>
                                        <td><input type="text" name="marpol_issued_by" value="<?php echo htmlspecialchars($formData['marpol_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="marpol_issued_date" value="<?php echo htmlspecialchars($formData['marpol_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="marpol_expiry_date" value="<?php echo htmlspecialchars($formData['marpol_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $isEngineRatings ? 'ETR Certificate' : ($isDeckRatings ? 'NC-2' : 'MLC (Management Level Certificate)'); ?></td>
                                        <td><input type="text" name="maritime_law" value="<?php echo htmlspecialchars($formData['maritime_law'] ?? ''); ?>"></td>
                                        <td><input type="text" name="maritime_law_issued_by" value="<?php echo htmlspecialchars($formData['maritime_law_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="maritime_law_issued_date" value="<?php echo htmlspecialchars($formData['maritime_law_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="maritime_law_expiry_date" value="<?php echo htmlspecialchars($formData['maritime_law_expiry_date'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr style="<?php echo $isDeckRatings || $isEngineRatings ? '' : 'display:none;'; ?>">
                                        <td><?php echo $isEngineRatings ? 'SMAW - Shielded Metal and Welding' : 'Quarantine Certificate'; ?></td>
                                        <td><input type="text" name="ccm" value="<?php echo htmlspecialchars($formData['ccm'] ?? ''); ?>"></td>
                                        <td><input type="text" name="ccm_issued_by" value="<?php echo htmlspecialchars($formData['ccm_issued_by'] ?? ''); ?>"></td>
                                        <td><input type="date" name="ccm_issued_date" value="<?php echo htmlspecialchars($formData['ccm_issued_date'] ?? ''); ?>"></td>
                                        <td><input type="date" name="ccm_expiry_date" value="<?php echo htmlspecialchars($formData['ccm_expiry_date'] ?? ''); ?>"></td>
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
                                        <td><input type="text" name="sea_position_1" value="<?php echo htmlspecialchars($formData['sea_position_1'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_vessel_1" value="<?php echo htmlspecialchars($formData['sea_vessel_1'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_company_1" value="<?php echo htmlspecialchars($formData['sea_company_1'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_type_1" value="<?php echo htmlspecialchars($formData['sea_type_1'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_grt_1" value="<?php echo htmlspecialchars($formData['sea_grt_1'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sea_from_1" value="<?php echo htmlspecialchars($formData['sea_from_1'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sea_to_1" value="<?php echo htmlspecialchars($formData['sea_to_1'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_months_1" value="<?php echo htmlspecialchars($formData['sea_months_1'] ?? ''); ?>" readonly></td>
                                        <td><input type="text" name="sea_reason_1" value="<?php echo htmlspecialchars($formData['sea_reason_1'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" name="sea_position_2" value="<?php echo htmlspecialchars($formData['sea_position_2'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_vessel_2" value="<?php echo htmlspecialchars($formData['sea_vessel_2'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_company_2" value="<?php echo htmlspecialchars($formData['sea_company_2'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_type_2" value="<?php echo htmlspecialchars($formData['sea_type_2'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_grt_2" value="<?php echo htmlspecialchars($formData['sea_grt_2'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sea_from_2" value="<?php echo htmlspecialchars($formData['sea_from_2'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sea_to_2" value="<?php echo htmlspecialchars($formData['sea_to_2'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_months_2" value="<?php echo htmlspecialchars($formData['sea_months_2'] ?? ''); ?>" readonly></td>
                                        <td><input type="text" name="sea_reason_2" value="<?php echo htmlspecialchars($formData['sea_reason_2'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" name="sea_position_3" value="<?php echo htmlspecialchars($formData['sea_position_3'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_vessel_3" value="<?php echo htmlspecialchars($formData['sea_vessel_3'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_company_3" value="<?php echo htmlspecialchars($formData['sea_company_3'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_type_3" value="<?php echo htmlspecialchars($formData['sea_type_3'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_grt_3" value="<?php echo htmlspecialchars($formData['sea_grt_3'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sea_from_3" value="<?php echo htmlspecialchars($formData['sea_from_3'] ?? ''); ?>"></td>
                                        <td><input type="date" name="sea_to_3" value="<?php echo htmlspecialchars($formData['sea_to_3'] ?? ''); ?>"></td>
                                        <td><input type="text" name="sea_months_3" value="<?php echo htmlspecialchars($formData['sea_months_3'] ?? ''); ?>" readonly></td>
                                        <td><input type="text" name="sea_reason_3" value="<?php echo htmlspecialchars($formData['sea_reason_3'] ?? ''); ?>"></td>
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
                    <?php
                    $selectedPosition = trim($formData['position_applied'] ?? '');
                    $deckOfficerPositions = [
                        '3rd Mate / 3rd Officer',
                        '2nd Mate / 2nd Officer',
                        'Chief Mate / Chief Officer',
                        'Captain / Master'
                    ];
                    $engineOfficerPositions = [
                        '4th Engineer',
                        '3rd Engineer',
                        '2nd Engineer',
                        'Chief Engineer'
                    ];
                    $deckRatingsPositions = [
                        'D/C',
                        'A/B',
                        'Bosun',
                        'MSM (Messman)',
                        'Cook'
                    ];
                    $engineRatingsPositions = [
                        'E/C',
                        'Oiler',
                        'Fitter/Welder',
                        'Fitter\\Welder',
                        'Electrician',
                        'Wiper'
                    ];
                    $isDeckOfficer = in_array($selectedPosition, $deckOfficerPositions, true);
                    $isEngineOfficer = in_array($selectedPosition, $engineOfficerPositions, true);
                    $isOfficerPosition = $isDeckOfficer || $isEngineOfficer;
                    $isDeckRatings = in_array($selectedPosition, $deckRatingsPositions, true);
                    $isEngineRatings = in_array($selectedPosition, $engineRatingsPositions, true);
                    ?>
                    <!-- PAGE 3: Certificate Requirements Checklist -->
                    <div class="checklist-container">
                        <div class="checklist-column" id="deck-ratings-checklist-column" style="<?php echo ($isDeckRatings || $isEngineRatings) ? '' : 'display:none;'; ?>">
                            <h3 class="section-title"><?php echo $isEngineRatings ? 'LIST OF CERTIFICATE REQUIREMENTS FOR ENGINE RATINGS' : 'LIST OF CERTIFICATE REQUIREMENTS FOR DECK RATINGS'; ?></h3>

                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_bt" disabled><input type="hidden" name="rating_bt_hidden" value="0"> BT (Basic Training)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_rfpnw" disabled><input type="hidden" name="rating_rfpnw_hidden" value="0"><span class="rating-rfpnw-label">Rating Forming Part of Nav. Watch</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_nav_watch_24_cop" disabled><input type="hidden" name="rating_nav_watch_24_cop_hidden" value="0"><span class="rating-nav24-label">Nav. Watch 2/4 w/COP</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_nav_watch_25_cop" disabled><input type="hidden" name="rating_nav_watch_25_cop_hidden" value="0"><span class="rating-nav25-label">Nav. Watch 2/5 w/COP</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_sdsd" disabled><input type="hidden" name="rating_sdsd_hidden" value="0"><span class="rating-sdsd-label">SDSD (Seafarer's Designated Security Duties)</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_atot" disabled><input type="hidden" name="rating_atot_hidden" value="0"><span class="rating-atot-label">ATOT (Advanced Training for Oil Tanker)</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_btoc" disabled><input type="hidden" name="rating_btoc_hidden" value="0"> BTOC (For Tankers Only)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_aff" disabled><input type="hidden" name="rating_aff_hidden" value="0"> AFF (Advanced Fire Fighting)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_marpol_1_6" disabled><input type="hidden" name="rating_marpol_1_6_hidden" value="0"><span class="rating-marpol-label">Consolidated Marpol 1 to 6</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_pscrb" disabled><input type="hidden" name="rating_pscrb_hidden" value="0"><span class="rating-pscrb-label">PSCRB (Proficiency in Survival Craft and Rescue Boats)</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_ism_r" disabled><input type="hidden" name="rating_ism_r_hidden" value="0"><span class="rating-ismr-label">ISM-R (International Safety Management for Ratings)</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_nc_1" disabled><input type="hidden" name="rating_nc_1_hidden" value="0"> NC-1</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_nc_2" disabled><input type="hidden" name="rating_nc_2_hidden" value="0"><span class="rating-nc2-label">NC-2</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="rating_quarantine_certificate" disabled><input type="hidden" name="rating_quarantine_certificate_hidden" value="0"><span class="rating-last-label">Quarantine Certificate</span></label>
                            </div>
                        </div>

                        <div class="checklist-column" id="deck-officer-checklist-column" style="<?php echo $isOfficerPosition ? '' : 'display:none;'; ?>">
                            <h3 class="section-title"><?php echo $isEngineOfficer ? 'LIST OF CERTIFICATE REQUIRMENT FOR ENGINE OFFICER' : 'LIST OF CERTIFICATE REQUIRMENT FOR DECK OFFICER'; ?></h3>

                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_bt" disabled><input type="hidden" name="officer_bt_hidden" value="0"> BT (Basic Training)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_ismo" disabled><input type="hidden" name="officer_ismo_hidden" value="0"> ISM-O (International Safety Management for Officers)</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_rsc" disabled><input type="hidden" name="officer_rsc_hidden" value="0"><span class="officer-rsc-label">RSC (Radar Simulator Course)</span></label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_brm" disabled><input type="hidden" name="officer_brm_hidden" value="0"><span class="officer-brm-label">BRM (Bridge Resource Management)</span></label>
                            </div>
                            <div class="checklist-item officer-gmdss-item">
                                <label><input type="checkbox" name="officer_radio_gmdss" disabled><input type="hidden" name="officer_radio_gmdss_hidden" value="0">Radio Communication / GMDSS</label>
                            </div>
                            <div class="checklist-item officer-ssbt-item">
                                <label><input type="checkbox" name="officer_ssbt" disabled><input type="hidden" name="officer_ssbt_hidden" value="0">SSBT</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_atot" disabled><input type="hidden" name="officer_atot_hidden" value="0"> ATOT</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_mefa" disabled><input type="hidden" name="officer_mefa_hidden" value="0"> MEFA</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_aff" disabled><input type="hidden" name="officer_aff_hidden" value="0"> AFF</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_pscrb" disabled><input type="hidden" name="officer_pscrb_hidden" value="0"> P.S.C.R.B</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_meca" disabled><input type="hidden" name="officer_meca_hidden" value="0"> MECA</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_sso" disabled><input type="hidden" name="officer_sso_hidden" value="0"> SSO</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_consolidated" disabled><input type="hidden" name="officer_consolidated_hidden" value="0"> CONSOLIDATED</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_mlc" disabled><input type="hidden" name="officer_mlc_hidden" value="0"> MANAGEMENT LEVEL CERTIFICATE</label>
                            </div>
                            <div class="checklist-item">
                                <label><input type="checkbox" name="officer_btoc" disabled><input type="hidden" name="officer_btoc_hidden" value="0"> BTOC</label>
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
    
    <script>
        (function () {
            function computeMonths(fromValue, toValue) {
                if (!fromValue || !toValue) return '';
                const from = new Date(fromValue);
                const to = new Date(toValue);
                if (isNaN(from.getTime()) || isNaN(to.getTime()) || to < from) return '';

                let months = (to.getFullYear() - from.getFullYear()) * 12 + (to.getMonth() - from.getMonth());
                if (to.getDate() >= from.getDate()) {
                    months += 1;
                }
                return months > 0 ? String(months) : '';
            }

            function bindSeaRow(index) {
                const fromInput = document.querySelector(`input[name="sea_from_${index}"]`);
                const toInput = document.querySelector(`input[name="sea_to_${index}"]`);
                const monthsInput = document.querySelector(`input[name="sea_months_${index}"]`);
                if (!fromInput || !toInput || !monthsInput) return;

                const update = () => {
                    monthsInput.value = computeMonths(fromInput.value, toInput.value);
                };

                fromInput.addEventListener('change', update);
                toInput.addEventListener('change', update);
            }

            function isDeckOfficerPosition(positionValue) {
                const deckOfficerPositions = [
                    '3rd Mate / 3rd Officer',
                    '2nd Mate / 2nd Officer',
                    'Chief Mate / Chief Officer',
                    'Captain / Master'
                ];
                return deckOfficerPositions.includes((positionValue || '').trim());
            }

            function isDeckRatingsPosition(positionValue) {
                const deckRatingsPositions = [
                    'D/C',
                    'A/B',
                    'Bosun',
                    'MSM (Messman)',
                    'Cook'
                ];
                return deckRatingsPositions.includes((positionValue || '').trim());
            }

            function isEngineRatingsPosition(positionValue) {
                const engineRatingsPositions = [
                    'E/C',
                    'Oiler',
                    'Fitter/Welder',
                    'Fitter\\Welder',
                    'Electrician',
                    'Wiper'
                ];
                const normalized = (positionValue || '').trim().toLowerCase();
                return engineRatingsPositions.includes((positionValue || '').trim()) ||
                    ['e/c', 'oiler', 'fitter/welder', 'fitter\\welder', 'electrician', 'wiper'].includes(normalized);
            }

            function isEngineOfficerPosition(positionValue) {
                const engineOfficerPositions = [
                    '4th Engineer',
                    '3rd Engineer',
                    '2nd Engineer',
                    'Chief Engineer'
                ];
                return engineOfficerPositions.includes((positionValue || '').trim());
            }

            function applyChecklistBehavior() {
                const positionInput = document.querySelector('select[name="position_applied"]');
                const deckOfficerColumn = document.getElementById('deck-officer-checklist-column');
                const deckRatingsColumn = document.getElementById('deck-ratings-checklist-column');

                if (!positionInput) return;

                const selectedValue = (positionInput.value || '').trim();
                const deckOfficerSelected = isDeckOfficerPosition(selectedValue);
                const engineOfficerSelected = isEngineOfficerPosition(selectedValue);
                const deckRatingsSelected = isDeckRatingsPosition(selectedValue);
                const engineRatingsSelected = isEngineRatingsPosition(selectedValue);

                if (deckRatingsColumn) {
                    deckRatingsColumn.style.display = (deckRatingsSelected || engineRatingsSelected) ? '' : 'none';

                    const heading = deckRatingsColumn.querySelector('h3.section-title');
                    if (heading) {
                        heading.textContent = engineRatingsSelected
                            ? 'LIST OF CERTIFICATE REQUIREMENTS FOR ENGINE RATINGS'
                            : 'LIST OF CERTIFICATE REQUIREMENTS FOR DECK RATINGS';
                    }

                    const rfpnwLabel = deckRatingsColumn.querySelector('.rating-rfpnw-label');
                    const nav24Label = deckRatingsColumn.querySelector('.rating-nav24-label');
                    const nav25Label = deckRatingsColumn.querySelector('.rating-nav25-label');
                    const sdsdLabel = deckRatingsColumn.querySelector('.rating-sdsd-label');
                    const atotLabel = deckRatingsColumn.querySelector('.rating-atot-label');
                    const marpolLabel = deckRatingsColumn.querySelector('.rating-marpol-label');
                    const pscrbLabel = deckRatingsColumn.querySelector('.rating-pscrb-label');
                    const ismrLabel = deckRatingsColumn.querySelector('.rating-ismr-label');
                    const nc1Text = deckRatingsColumn.querySelector('input[name="rating_nc_1"]')?.closest('label');
                    const nc2Label = deckRatingsColumn.querySelector('.rating-nc2-label');
                    const lastLabel = deckRatingsColumn.querySelector('.rating-last-label');

                    if (engineRatingsSelected) {
                        if (rfpnwLabel) rfpnwLabel.textContent = 'Eng. Watch 3/4 w/COP';
                        if (nav24Label) nav24Label.textContent = 'Eng. Watch 3/5 w/COP';
                        if (nav25Label) nav25Label.textContent = 'SDSD (Seafarer\'s Designated Security Duties)';
                        if (sdsdLabel) sdsdLabel.textContent = 'ATOT (Advanced Training for Oil Tanker)';
                        if (atotLabel) atotLabel.textContent = 'BTOC (Basic Training for Oil Tanker)';
                        if (marpolLabel) marpolLabel.textContent = 'Consolidated Marpol 1 to 6';
                        if (pscrbLabel) pscrbLabel.textContent = 'PSCRB (Proficiency in Survival Craft and Rescue Boats)';
                        if (ismrLabel) ismrLabel.textContent = 'ISM-R (International Safety Management for Ratings)';
                        if (nc1Text) nc1Text.childNodes[nc1Text.childNodes.length - 1].nodeValue = ' ETD Certificate';
                        if (nc2Label) nc2Label.textContent = 'ETR Certificate';
                        if (lastLabel) lastLabel.textContent = 'SMAW - Shielded Metal and Welding';
                    } else {
                        if (rfpnwLabel) rfpnwLabel.textContent = 'Rating Forming Part of Nav. Watch';
                        if (nav24Label) nav24Label.textContent = 'Nav. Watch 2/4 w/COP';
                        if (nav25Label) nav25Label.textContent = 'Nav. Watch 2/5 w/COP';
                        if (sdsdLabel) sdsdLabel.textContent = "SDSD (Seafarer's Designated Security Duties)";
                        if (atotLabel) atotLabel.textContent = 'ATOT (Advanced Training for Oil Tanker)';
                        if (marpolLabel) marpolLabel.textContent = 'Consolidated Marpol 1 to 6';
                        if (pscrbLabel) pscrbLabel.textContent = 'PSCRB (Proficiency in Survival Craft and Rescue Boats)';
                        if (ismrLabel) ismrLabel.textContent = 'ISM-R (International Safety Management for Ratings)';
                        if (nc1Text) nc1Text.childNodes[nc1Text.childNodes.length - 1].nodeValue = ' NC-1';
                        if (nc2Label) nc2Label.textContent = 'NC-2';
                        if (lastLabel) lastLabel.textContent = 'Quarantine Certificate';
                    }
                }

                if (!deckOfficerColumn) return;

                const officerChecks = deckOfficerColumn.querySelectorAll('input[type="checkbox"]');

                if (deckOfficerSelected || engineOfficerSelected) {
                    deckOfficerColumn.style.display = '';
                } else {
                    deckOfficerColumn.style.display = 'none';
                }

                const heading = deckOfficerColumn.querySelector('h3.section-title');
                if (heading) {
                    heading.textContent = engineOfficerSelected
                        ? 'LIST OF CERTIFICATE REQUIRMENT FOR ENGINE OFFICER'
                        : 'LIST OF CERTIFICATE REQUIRMENT FOR DECK OFFICER';
                }

                const rscLabel = deckOfficerColumn.querySelector('.officer-rsc-label');
                const brmLabel = deckOfficerColumn.querySelector('.officer-brm-label');
                const gmdssItem = deckOfficerColumn.querySelector('.officer-gmdss-item');
                const ssbtItem = deckOfficerColumn.querySelector('.officer-ssbt-item');

                if (rscLabel) {
                    rscLabel.textContent = engineOfficerSelected
                        ? 'RAC (Risk Assessment Certificate)'
                        : 'RSC (Radar Simulator Course)';
                }
                if (brmLabel) {
                    brmLabel.textContent = engineOfficerSelected
                        ? 'ERM (Engine Room Resource Management) - function 1 to 4'
                        : 'BRM (Bridge Resource Management)';
                }
                if (gmdssItem) gmdssItem.style.display = engineOfficerSelected ? 'none' : '';
                if (ssbtItem) ssbtItem.style.display = engineOfficerSelected ? 'none' : '';

                const atotItem = deckOfficerColumn.querySelector('[name="officer_atot"]')?.closest('.checklist-item');
                const mefaItem = deckOfficerColumn.querySelector('[name="officer_mefa"]')?.closest('.checklist-item');
                const pscrbItem = deckOfficerColumn.querySelector('[name="officer_pscrb"]')?.closest('.checklist-item');
                const mecaItem = deckOfficerColumn.querySelector('[name="officer_meca"]')?.closest('.checklist-item');
                const ssoItem = deckOfficerColumn.querySelector('[name="officer_sso"]')?.closest('.checklist-item');
                const consolidatedItem = deckOfficerColumn.querySelector('[name="officer_consolidated"]')?.closest('.checklist-item');
                const mlcItem = deckOfficerColumn.querySelector('[name="officer_mlc"]')?.closest('.checklist-item');
                const btocItem = deckOfficerColumn.querySelector('[name="officer_btoc"]')?.closest('.checklist-item');

                if (atotItem) atotItem.style.display = '';
                if (mefaItem) mefaItem.style.display = '';
                if (pscrbItem) pscrbItem.style.display = '';
                if (mecaItem) mecaItem.style.display = '';
                if (ssoItem) ssoItem.style.display = '';
                if (consolidatedItem) consolidatedItem.style.display = '';
                if (mlcItem) mlcItem.style.display = '';
                if (btocItem) btocItem.style.display = '';

                if (engineOfficerSelected) {
                    if (atotItem) atotItem.style.display = 'none';
                    if (pscrbItem) pscrbItem.style.display = 'none';
                    if (mecaItem) mecaItem.style.display = 'none';
                    if (ssoItem) ssoItem.style.display = 'none';
                    if (consolidatedItem) consolidatedItem.style.display = 'none';
                    if (mlcItem) mlcItem.style.display = 'none';
                    if (btocItem) btocItem.style.display = 'none';
                }

                if (deckOfficerSelected || engineOfficerSelected) {
                    officerChecks.forEach((cb) => {
                        cb.checked = true;
                    });
                } else {
                    officerChecks.forEach((cb) => {
                        cb.checked = false;
                    });
                }
            }

            function applyTrainingSectionBehavior() {
                const positionInput = document.querySelector('select[name="position_applied"]');
                const trainingSection = document.querySelector('.training-section');
                if (!positionInput || !trainingSection) return;

                const selectedValue = (positionInput.value || '').trim();
                const deckOfficerSelected = isDeckOfficerPosition(selectedValue);
                const engineOfficerSelected = isEngineOfficerPosition(selectedValue);
                const deckRatingsSelected = isDeckRatingsPosition(selectedValue);

                const rows = trainingSection.querySelectorAll('tbody tr');
                rows.forEach((row) => {
                    row.style.display = '';
                });

                const labelByField = {};
                if (deckRatingsSelected) {
                    labelByField.watch_keeping = 'Rating Forming Part of Nav. Watch';
                    labelByField.risk_or_sim = 'Nav. Watch 2/4 w/COP';
                    labelByField.brm = 'Nav. Watch 2/5 w/COP';
                    labelByField.cargo_handling = "SDSD (Seafarer's Designated Security Duties)";
                    labelByField.meca_mefa = 'ATOT (Advanced Training for Oil Tanker)';
                    labelByField.btoc = 'BTOC (For Tankers Only)';
                    labelByField.aff = 'AFF (Advanced Fire Fighting)';
                    labelByField.pscrb = 'Consolidated Marpol 1 to 6';
                    labelByField.mec_mefa_cop = 'PSCRB (Proficiency in Survival Craft and Rescue Boats)';
                    labelByField.sso = 'ISM-R (International Safety Management for Ratings)';
                    labelByField.marpol = 'NC-1';
                    labelByField.maritime_law = 'NC-2';
                    labelByField.last = 'Quarantine Certificate';
                } else if (engineOfficerSelected) {
                    labelByField.watch_keeping = 'ISM-O (International Safety Management for Officers)';
                    labelByField.risk_or_sim = 'RAC (Risk Assessment Certificate)';
                    labelByField.brm = 'ERM (Engine Room Resource Management) - function 1 to 4';
                    labelByField.cargo_handling = 'ATOT (Advanced Training for Oil Tanker)';
                    labelByField.meca_mefa = 'MEFA (Medical First Aid)';
                    labelByField.btoc = 'BTOC (For Tankers Only)';
                    labelByField.aff = 'AFF (Advanced Fire Fighting)';
                    labelByField.pscrb = 'PSCRB (Proficiency in Survival Craft and Rescue Boats)';
                    labelByField.mec_mefa_cop = 'MECA (Medical Care)';
                    labelByField.sso = 'SSO (Ship Security Officer)';
                    labelByField.marpol = 'Consolidated Marpol 1 to 6';
                    labelByField.maritime_law = 'MLC (Management Level Certificate)';
                } else if (deckOfficerSelected) {
                    labelByField.watch_keeping = 'ISM-O (International Safety Management for Officers)';
                    labelByField.risk_or_sim = 'RSC (Radar Simulator Course)';
                    labelByField.brm = 'BRM (Bridge Resource Management)';
                    labelByField.cargo_handling = 'ATOT (Advanced Training for Oil Tanker)';
                    labelByField.meca_mefa = 'MEFA (Medical First Aid)';
                    labelByField.btoc = 'BTOC (For Tankers Only)';
                    labelByField.aff = 'AFF (Advanced Fire Fighting)';
                    labelByField.pscrb = 'PSCRB (Proficiency in Survival Craft and Rescue Boats)';
                    labelByField.mec_mefa_cop = 'MECA (Medical Care)';
                    labelByField.sso = 'SSO (Ship Security Officer)';
                    labelByField.marpol = 'Consolidated Marpol 1 to 6';
                    labelByField.maritime_law = 'MLC (Management Level Certificate)';
                }

                const setRowLabelByInput = (inputName, labelText) => {
                    if (!labelText) return;
                    const input = trainingSection.querySelector(`[name="${inputName}"]`);
                    if (!input) return;
                    const row = input.closest('tr');
                    if (!row) return;
                    const firstCell = row.querySelector('td');
                    if (firstCell) firstCell.textContent = labelText;
                };

                setRowLabelByInput('watch_keeping', labelByField.watch_keeping);
                setRowLabelByInput(engineOfficerSelected ? 'risk_assessment' : 'ship_simulator', labelByField.risk_or_sim);
                setRowLabelByInput('brm', labelByField.brm);
                setRowLabelByInput('cargo_handling', labelByField.cargo_handling);
                setRowLabelByInput('meca_mefa', labelByField.meca_mefa);
                setRowLabelByInput('btoc', labelByField.btoc);
                setRowLabelByInput('aff', labelByField.aff);
                setRowLabelByInput('pscrb', labelByField.pscrb);
                setRowLabelByInput('mec_mefa_cop', labelByField.mec_mefa_cop);
                setRowLabelByInput('sso', labelByField.sso);
                setRowLabelByInput('marpol', labelByField.marpol);
                setRowLabelByInput('maritime_law', labelByField.maritime_law);

                const quarantineRow = trainingSection.querySelector('[name="ccm"]')?.closest('tr');
                const btocFinalRow = trainingSection.querySelector('[name="btoc"]')?.closest('tr');
                if (quarantineRow) quarantineRow.style.display = (deckRatingsSelected || isEngineRatingsPosition(selectedValue)) ? '' : 'none';
                if (quarantineRow) {
                    const ccmFirstCell = quarantineRow.querySelector('td');
                    if (ccmFirstCell) {
                        ccmFirstCell.textContent = isEngineRatingsPosition(selectedValue)
                            ? 'SMAW - Shielded Metal and Welding'
                            : 'Quarantine Certificate';
                    }
                }
                if (btocFinalRow && !deckRatingsSelected && !engineOfficerSelected && !deckOfficerSelected && !isEngineRatingsPosition(selectedValue)) btocFinalRow.style.display = 'none';

                const gmdssRow = trainingSection.querySelector('[name="gmdss"]')?.closest('tr');
                const ssbtRow = trainingSection.querySelector('[name="ssbt"]')?.closest('tr');
                if (gmdssRow) gmdssRow.style.display = engineOfficerSelected || deckRatingsSelected ? 'none' : '';
                if (ssbtRow) ssbtRow.style.display = engineOfficerSelected || deckRatingsSelected ? 'none' : '';
            }

            function getDraftValue(name) {
                try {
                    const v = sessionStorage.getItem('apply_draft_' + name);
                    return v === null ? '' : v;
                } catch (e) {
                    return '';
                }
            }

            function hasAnyValue(fieldNames) {
                return fieldNames.some((name) => {
                    const el = document.querySelector(`[name="${name}"]`);
                    const liveValue = el ? (el.value || '').trim() : '';
                    if (liveValue !== '') return true;

                    const draftValue = (getDraftValue(name) || '').trim();
                    return draftValue !== '';
                });
            }

            function autoCheckRatingsChecklist() {
                const positionInput = document.querySelector('select[name="position_applied"]');
                const positionLive = positionInput ? (positionInput.value || '').trim() : '';
                const positionDraft = (getDraftValue('position_applied') || '').trim();
                const currentPos = positionLive || positionDraft;

                const isDeck = isDeckRatingsPosition(currentPos);
                const isEngine = isEngineRatingsPosition(currentPos);

                const ratingChecks = document.querySelectorAll('[name^="rating_"][type="checkbox"]');

                if (!isDeck && !isEngine) {
                    ratingChecks.forEach((cb) => {
                        cb.checked = false;
                        const hidden = document.querySelector(`[name="${cb.name}_hidden"]`);
                        if (hidden) hidden.value = '0';
                    });
                    return;
                }

                const rules = isEngine
                    ? [
                        { checkbox: 'rating_bt', fields: ['basic_training', 'basic_training_issued_by', 'basic_training_issued_date', 'basic_training_expiry_date'] },
                        { checkbox: 'rating_rfpnw', fields: ['watch_keeping', 'watch_keeping_issued_by', 'watch_keeping_issued_date', 'watch_keeping_expiry_date'] },
                        { checkbox: 'rating_nav_watch_24_cop', fields: ['ship_simulator', 'ship_simulator_issued_by', 'ship_simulator_issued_date', 'ship_simulator_expiry_date'] },
                        { checkbox: 'rating_nav_watch_25_cop', fields: ['brm', 'brm_issued_by', 'brm_issued_date', 'brm_expiry_date'] },
                        { checkbox: 'rating_sdsd', fields: ['cargo_handling', 'cargo_handling_issued_by', 'cargo_handling_issued_date', 'cargo_handling_expiry_date'] },
                        { checkbox: 'rating_atot', fields: ['meca_mefa', 'meca_mefa_issued_by', 'meca_mefa_issued_date', 'meca_mefa_expiry_date'] },
                        { checkbox: 'rating_btoc', fields: ['btoc', 'btoc_issued_by', 'btoc_issued_date', 'btoc_expiry_date'] },
                        { checkbox: 'rating_aff', fields: ['aff', 'aff_issued_by', 'aff_issued_date', 'aff_expiry_date'] },
                        { checkbox: 'rating_marpol_1_6', fields: ['marpol', 'marpol_issued_by', 'marpol_issued_date', 'marpol_expiry_date'] },
                        { checkbox: 'rating_pscrb', fields: ['pscrb', 'pscrb_issued_by', 'pscrb_issued_date', 'pscrb_expiry_date'] },
                        { checkbox: 'rating_ism_r', fields: ['mec_mefa_cop', 'mec_mefa_cop_issued_by', 'mec_mefa_cop_issued_date', 'mec_mefa_cop_expiry_date'] },
                        { checkbox: 'rating_nc_1', fields: ['sso', 'sso_issued_by', 'sso_issued_date', 'sso_expiry_date'] },
                        { checkbox: 'rating_nc_2', fields: ['maritime_law', 'maritime_law_issued_by', 'maritime_law_issued_date', 'maritime_law_expiry_date'] },
                        { checkbox: 'rating_quarantine_certificate', fields: ['ccm', 'ccm_issued_by', 'ccm_issued_date', 'ccm_expiry_date'] }
                    ]
                    : [
                        { checkbox: 'rating_bt', fields: ['basic_training', 'basic_training_issued_by', 'basic_training_issued_date', 'basic_training_expiry_date'] },
                        { checkbox: 'rating_rfpnw', fields: ['watch_keeping', 'watch_keeping_issued_by', 'watch_keeping_issued_date', 'watch_keeping_expiry_date'] },
                        { checkbox: 'rating_nav_watch_24_cop', fields: ['ship_simulator', 'ship_simulator_issued_by', 'ship_simulator_issued_date', 'ship_simulator_expiry_date'] },
                        { checkbox: 'rating_nav_watch_25_cop', fields: ['brm', 'brm_issued_by', 'brm_issued_date', 'brm_expiry_date'] },
                        { checkbox: 'rating_sdsd', fields: ['cargo_handling', 'cargo_handling_issued_by', 'cargo_handling_issued_date', 'cargo_handling_expiry_date'] },
                        { checkbox: 'rating_atot', fields: ['meca_mefa', 'meca_mefa_issued_by', 'meca_mefa_issued_date', 'meca_mefa_expiry_date'] },
                        { checkbox: 'rating_btoc', fields: ['btoc', 'btoc_issued_by', 'btoc_issued_date', 'btoc_expiry_date'] },
                        { checkbox: 'rating_aff', fields: ['aff', 'aff_issued_by', 'aff_issued_date', 'aff_expiry_date'] },
                        { checkbox: 'rating_marpol_1_6', fields: ['marpol', 'marpol_issued_by', 'marpol_issued_date', 'marpol_expiry_date'] },
                        { checkbox: 'rating_pscrb', fields: ['pscrb', 'pscrb_issued_by', 'pscrb_issued_date', 'pscrb_expiry_date'] },
                        { checkbox: 'rating_ism_r', fields: ['mec_mefa_cop', 'mec_mefa_cop_issued_by', 'mec_mefa_cop_issued_date', 'mec_mefa_cop_expiry_date'] },
                        { checkbox: 'rating_nc_1', fields: ['sso', 'sso_issued_by', 'sso_issued_date', 'sso_expiry_date'] },
                        { checkbox: 'rating_nc_2', fields: ['maritime_law', 'maritime_law_issued_by', 'maritime_law_issued_date', 'maritime_law_expiry_date'] },
                        { checkbox: 'rating_quarantine_certificate', fields: ['ccm', 'ccm_issued_by', 'ccm_issued_date', 'ccm_expiry_date'] }
                    ];

                rules.forEach((rule) => {
                    const cb = document.querySelector(`[name="${rule.checkbox}"]`);
                    if (!cb) return;

                    const isChecked = hasAnyValue(rule.fields);
                    cb.checked = isChecked;

                    const hidden = document.querySelector(`[name="${rule.checkbox}_hidden"]`);
                    if (hidden) hidden.value = isChecked ? '1' : '0';
                });
            }

            function autoCheckDeckOfficerChecklist() {
                const positionInput = document.querySelector('select[name="position_applied"]');
                const positionLive = positionInput ? (positionInput.value || '').trim() : '';
                const positionDraft = (getDraftValue('position_applied') || '').trim();
                const currentPos = positionLive || positionDraft;
                const isDeck = isDeckOfficerPosition(currentPos);
                const isEngine = isEngineOfficerPosition(currentPos);

                if (!isDeck && !isEngine) {
                    return;
                }

                const rules = [
                    { checkbox: 'officer_bt', fields: ['basic_training', 'basic_training_issued_by', 'basic_training_issued_date', 'basic_training_expiry_date'] },
                    { checkbox: 'officer_ismo', fields: ['watch_keeping', 'watch_keeping_issued_by', 'watch_keeping_issued_date', 'watch_keeping_expiry_date'] },
                    { checkbox: 'officer_rsc', fields: isEngine ? ['risk_assessment', 'risk_assessment_issued_by', 'risk_assessment_issued_date', 'risk_assessment_expiry_date'] : ['ship_simulator', 'ship_simulator_issued_by', 'ship_simulator_issued_date', 'ship_simulator_expiry_date'] },
                    { checkbox: 'officer_brm', fields: ['brm', 'brm_issued_by', 'brm_issued_date', 'brm_expiry_date'] },
                    { checkbox: 'officer_radio_gmdss', fields: ['gmdss', 'gmdss_issued_by', 'gmdss_issued_date', 'gmdss_expiry_date'] },
                    { checkbox: 'officer_ssbt', fields: ['ssbt', 'ssbt_issued_by', 'ssbt_issued_date', 'ssbt_expiry_date'] },
                    { checkbox: 'officer_atot', fields: ['cargo_handling', 'cargo_handling_issued_by', 'cargo_handling_issued_date', 'cargo_handling_expiry_date'] },
                    { checkbox: 'officer_mefa', fields: ['meca_mefa', 'meca_mefa_issued_by', 'meca_mefa_issued_date', 'meca_mefa_expiry_date'] },
                    { checkbox: 'officer_aff', fields: ['aff', 'aff_issued_by', 'aff_issued_date', 'aff_expiry_date'] },
                    { checkbox: 'officer_pscrb', fields: ['pscrb', 'pscrb_issued_by', 'pscrb_issued_date', 'pscrb_expiry_date'] },
                    { checkbox: 'officer_meca', fields: ['mec_mefa_cop', 'mec_mefa_cop_issued_by', 'mec_mefa_cop_issued_date', 'mec_mefa_cop_expiry_date'] },
                    { checkbox: 'officer_sso', fields: ['sso', 'sso_issued_by', 'sso_issued_date', 'sso_expiry_date'] },
                    { checkbox: 'officer_consolidated', fields: ['marpol', 'marpol_issued_by', 'marpol_issued_date', 'marpol_expiry_date'] },
                    { checkbox: 'officer_mlc', fields: ['maritime_law', 'maritime_law_issued_by', 'maritime_law_issued_date', 'maritime_law_expiry_date'] },
                    { checkbox: 'officer_btoc', fields: ['btoc', 'btoc_issued_by', 'btoc_issued_date', 'btoc_expiry_date'] }
                ];

                rules.forEach((rule) => {
                    const cb = document.querySelector(`[name="${rule.checkbox}"]`);
                    if (!cb) return;

                    const isChecked = hasAnyValue(rule.fields);
                    cb.checked = isChecked;

                    const hidden = document.querySelector(`[name="${rule.checkbox}_hidden"]`);
                    if (hidden) {
                        hidden.value = isChecked ? '1' : '0';
                    }
                });
            }

            bindSeaRow(1);
            bindSeaRow(2);
            bindSeaRow(3);

            const form = document.querySelector('form[action="apply.php"]');
            if (form) {
                form.addEventListener('submit', function () {
                    const draftFields = form.querySelectorAll('input, select, textarea');
                    draftFields.forEach((field) => {
                        if (!field.name) return;
                        if ((field.type || '').toLowerCase() === 'checkbox') return;
                        try {
                            sessionStorage.setItem('apply_draft_' + field.name, field.value || '');
                        } catch (e) {}
                    });
                });
            }

            function restoreDraftValues() {
                const draftFields = document.querySelectorAll('input, select, textarea');
                draftFields.forEach((field) => {
                    if (!field.name) return;
                    if ((field.type || '').toLowerCase() === 'checkbox') return;
                    if ((field.value || '').trim() !== '') return;
                    try {
                        const draftValue = sessionStorage.getItem('apply_draft_' + field.name);
                        if (draftValue !== null) {
                            field.value = draftValue;
                        }
                    } catch (e) {}
                });
            }

            restoreDraftValues();

            function persistPositionImmediately(positionValue) {
                if (!window.fetch) return;
                const body = new URLSearchParams();
                body.append('autosave', 'position');
                body.append('position_applied', positionValue || '');

                fetch('apply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString(),
                    credentials: 'same-origin'
                }).catch(() => {});
            }

            const positionInputLive = document.querySelector('select[name="position_applied"]');
            if (positionInputLive) {
                positionInputLive.addEventListener('change', function () {
                    const selectedValue = this.value || '';
                    try {
                        sessionStorage.setItem('apply_draft_position_applied', selectedValue);
                    } catch (e) {}
                    persistPositionImmediately(selectedValue);
                    applyTrainingSectionBehavior();
                    applyChecklistBehavior();
                    autoCheckRatingsChecklist();
                    autoCheckDeckOfficerChecklist();
                });
            }

            applyTrainingSectionBehavior();
            applyChecklistBehavior();
            autoCheckRatingsChecklist();
            autoCheckDeckOfficerChecklist();
        })();
    </script>
</body>
</html>
