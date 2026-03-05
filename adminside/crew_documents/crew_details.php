<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$crew_no = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($crew_no)) {
    header('Location: ../crew.php');
    exit();
}

try {
    $db = Database::getInstance();
    
    $sql = "SELECT 
                cm.*,
                CONCAT(cm.first_name, ' ', cm.last_name) as full_name,
                v.vessel_name,
                p.position_name,
                d.department_name
            FROM crew_master cm
            LEFT JOIN vessels v ON cm.vessel_id = v.id
            LEFT JOIN positions p ON cm.position_id = p.id
            LEFT JOIN departments d ON cm.department_id = d.id
            WHERE cm.crew_no = ?";
    
    $crew = $db->fetchOne($sql, [$crew_no]);
    
    if (!$crew) {
        $_SESSION['error_message'] = "Crew not found";
        header('Location: ../crew.php');
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching crew details: " . $e->getMessage();
    header('Location: ../crew.php');
    exit();
}

$age = '';
if (!empty($crew['birth_date'])) {
    $birthDate = new DateTime($crew['birth_date']);
    $today     = new DateTime();
    $age       = $today->diff($birthDate)->y;
}

function formatDate($date) {
    if (empty($date)) return '';
    return (new DateTime($date))->format('F d, Y');
}

function formatDateInput($date) {
    if (empty($date)) return '';
    return (new DateTime($date))->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Details - <?php echo htmlspecialchars($crew['full_name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="crew_details.css?v=<?php echo time(); ?>">
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
                <h2 class="page-title">CREW DETAILS</h2>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <form id="crewDetailsForm" method="POST" action="update_crew_details.php">
                    <input type="hidden" name="crew_no" value="<?php echo htmlspecialchars($crew_no); ?>">

                    <div class="card card--padded details-card">
                        <!-- Header -->
                        <div class="details-header">
                            <div class="status-info">
                                <span class="status-label">STATUS:</span>
                                <span class="status-value status-<?php echo htmlspecialchars($crew['crew_status']); ?> view-mode" id="statusDisplay">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $crew['crew_status']))); ?>
                                </span>
                                <select name="crew_status" class="status-select edit-mode" id="statusSelect" style="display: none;">
                                    <option value="on_board"   <?php echo $crew['crew_status'] === 'on_board'   ? 'selected' : ''; ?>>ON BOARD</option>
                                    <option value="on_vacation"<?php echo $crew['crew_status'] === 'on_vacation' ? 'selected' : ''; ?>>ON VACATION</option>
                                    <option value="inactive"   <?php echo $crew['crew_status'] === 'inactive'   ? 'selected' : ''; ?>>INACTIVE</option>
                                </select>
                            </div>
                            <div class="details-actions">
                                <button type="button" class="btn-action btn-edit view-mode" id="editBtn">EDIT</button>
                                <button type="submit" class="btn-action btn-save edit-mode" id="saveBtn" style="display: none;">SAVE</button>
                                <button type="button" class="btn-action btn-cancel edit-mode" id="cancelBtn" style="display: none;">CANCEL</button>
                                <button type="button" class="btn-action btn-delete view-mode">DELETE</button>
                                <button type="button" class="btn-close" onclick="window.location.href='../crew.php'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="details-grid">
                            <!-- Personal Information -->
                            <div class="details-section">
                                <h3 class="section-title">PERSONAL INFORMATION</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Name:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['full_name']); ?></span>
                                        <div class="edit-mode name-inputs" style="display: none;">
                                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($crew['first_name']); ?>" placeholder="First Name" class="name-input">
                                            <input type="text" name="last_name"  value="<?php echo htmlspecialchars($crew['last_name']);  ?>" placeholder="Last Name"  class="name-input">
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Age:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($age); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date of Birth:</span>
                                        <span class="detail-value view-mode"><?php echo formatDate($crew['birth_date']); ?></span>
                                        <input type="date" name="birth_date" class="edit-mode" value="<?php echo formatDateInput($crew['birth_date']); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Sex:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['sex'] ?? 'N/A'); ?></span>
                                        <select name="sex" class="edit-mode" style="display: none;">
                                            <option value="Male"   <?php echo $crew['sex'] === 'Male'   ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $crew['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Phone Number:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['phone'] ?? 'N/A'); ?></span>
                                        <input type="text" name="phone" class="edit-mode" value="<?php echo htmlspecialchars($crew['phone'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Nationality:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['nationality'] ?? 'N/A'); ?></span>
                                        <input type="text" name="nationality" class="edit-mode" value="<?php echo htmlspecialchars($crew['nationality'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Civil Status:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['civil_status'] ?? 'N/A'); ?></span>
                                        <select name="civil_status" class="edit-mode" style="display: none;">
                                            <option value="Single"   <?php echo $crew['civil_status'] === 'Single'   ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married"  <?php echo $crew['civil_status'] === 'Married'  ? 'selected' : ''; ?>>Married</option>
                                            <option value="Divorced" <?php echo $crew['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="Widowed"  <?php echo $crew['civil_status'] === 'Widowed'  ? 'selected' : ''; ?>>Widowed</option>
                                        </select>
                                    </div>
                                    <div class="detail-item detail-item-full">
                                        <span class="detail-label">Address:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['address'] ?? 'N/A'); ?></span>
                                        <input type="text" name="address" class="edit-mode" value="<?php echo htmlspecialchars($crew['address'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="details-section">
                                <h3 class="section-title">EMERGENCY CONTACT</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Name:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['emergency_name'] ?? 'N/A'); ?></span>
                                        <input type="text" name="emergency_name" class="edit-mode" value="<?php echo htmlspecialchars($crew['emergency_name'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Relationship:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['emergency_relationship'] ?? 'N/A'); ?></span>
                                        <input type="text" name="emergency_relationship" class="edit-mode" value="<?php echo htmlspecialchars($crew['emergency_relationship'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Phone Number:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['emergency_phone'] ?? 'N/A'); ?></span>
                                        <input type="text" name="emergency_phone" class="edit-mode" value="<?php echo htmlspecialchars($crew['emergency_phone'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Information -->
                            <div class="details-section">
                                <h3 class="section-title">BANK INFORMATION</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Bank:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['bank_name'] ?? 'N/A'); ?></span>
                                        <input type="text" name="bank_name" class="edit-mode" value="<?php echo htmlspecialchars($crew['bank_name'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Account No.:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['bank_account'] ?? 'N/A'); ?></span>
                                        <input type="text" name="bank_account" class="edit-mode" value="<?php echo htmlspecialchars($crew['bank_account'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Government Numbers -->
                            <div class="details-section">
                                <h3 class="section-title">GOVERNMENT NUMBERS</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">SSS:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['sss_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="sss_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['sss_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">PhilHealth:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['philhealth_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="philhealth_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['philhealth_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">PAG-IBIG:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['pagibig_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="pagibig_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['pagibig_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Passport:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['passport_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="passport_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['passport_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Embarkation Details -->
                            <div class="details-section">
                                <h3 class="section-title">EMBARKATION DETAILS</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Position:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($crew['position_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Vessel Assigned:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($crew['vessel_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date of Embarkation:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['embarkation_date']) ? formatDate($crew['embarkation_date']) : 'None'; ?></span>
                                        <input type="date" name="embarkation_date" class="edit-mode" value="<?php echo !empty($crew['embarkation_date']) ? formatDateInput($crew['embarkation_date']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Place of Embarkation:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['embarkation_place'] ?? 'None'); ?></span>
                                        <input type="text" name="embarkation_place" class="edit-mode" value="<?php echo htmlspecialchars($crew['embarkation_place'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contract Start:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['contract_start']) ? formatDate($crew['contract_start']) : 'None'; ?></span>
                                        <input type="date" name="contract_start" class="edit-mode" value="<?php echo !empty($crew['contract_start']) ? formatDateInput($crew['contract_start']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contract End:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['contract_end']) ? formatDate($crew['contract_end']) : 'None'; ?></span>
                                        <input type="date" name="contract_end" class="edit-mode" value="<?php echo !empty($crew['contract_end']) ? formatDateInput($crew['contract_end']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Extension Contract:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['extension_contract'] ?? 'None'); ?></span>
                                        <input type="text" name="extension_contract" class="edit-mode" value="<?php echo htmlspecialchars($crew['extension_contract'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Seafarer's Identification -->
                            <div class="details-section">
                                <h3 class="section-title">SEAFARER'S IDENTIFICATION</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">SRN No.:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['srn_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="srn_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['srn_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Remarks:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['remarks'] ?? 'For Approval'); ?></span>
                                        <input type="text" name="remarks" class="edit-mode" value="<?php echo htmlspecialchars($crew['remarks'] ?? 'For Approval'); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">SIRB No.:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['sirb_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="sirb_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['sirb_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Expiry Date:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['sirb_expiry']) ? formatDate($crew['sirb_expiry']) : 'N/A'; ?></span>
                                        <input type="date" name="sirb_expiry" class="edit-mode" value="<?php echo !empty($crew['sirb_expiry']) ? formatDateInput($crew['sirb_expiry']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">DCOC No.:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['dcoc_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="dcoc_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['dcoc_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Expiry Date:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['dcoc_expiry']) ? formatDate($crew['dcoc_expiry']) : 'N/A'; ?></span>
                                        <input type="date" name="dcoc_expiry" class="edit-mode" value="<?php echo !empty($crew['dcoc_expiry']) ? formatDateInput($crew['dcoc_expiry']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Seaman's Book No.:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['seamans_book_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="seamans_book_no" class="edit-mode" value="<?php echo htmlspecialchars($crew['seamans_book_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Expiry Date:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['seamans_book_expiry']) ? formatDate($crew['seamans_book_expiry']) : 'N/A'; ?></span>
                                        <input type="date" name="seamans_book_expiry" class="edit-mode" value="<?php echo !empty($crew['seamans_book_expiry']) ? formatDateInput($crew['seamans_book_expiry']) : ''; ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Disembarkation Details -->
                            <div class="details-section">
                                <h3 class="section-title">DISEMBARKATION DETAILS</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Date of Disembarkation:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($crew['disembarkation_date']) ? formatDate($crew['disembarkation_date']) : 'None'; ?></span>
                                        <input type="date" name="disembarkation_date" class="edit-mode" value="<?php echo !empty($crew['disembarkation_date']) ? formatDateInput($crew['disembarkation_date']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Place of Disembarkation:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['disembarkation_place'] ?? 'None'); ?></span>
                                        <input type="text" name="disembarkation_place" class="edit-mode" value="<?php echo htmlspecialchars($crew['disembarkation_place'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item detail-item-full">
                                        <span class="detail-label">Reason of Disembarkation:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($crew['disembarkation_reason'] ?? 'None'); ?></span>
                                        <input type="text" name="disembarkation_reason" class="edit-mode" value="<?php echo htmlspecialchars($crew['disembarkation_reason'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script>
        const editBtn   = document.getElementById('editBtn');
        const saveBtn   = document.getElementById('saveBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const form      = document.getElementById('crewDetailsForm');

        const viewModeElements = document.querySelectorAll('.view-mode');
        const editModeElements = document.querySelectorAll('.edit-mode');

        let originalFormData = new FormData(form);

        editBtn.addEventListener('click', function () {
            originalFormData = new FormData(form);
            viewModeElements.forEach(el => el.style.display = 'none');
            editModeElements.forEach(el => el.style.display = 'block');
        });

        cancelBtn.addEventListener('click', function () {
            for (let [key, value] of originalFormData.entries()) {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) input.value = value;
            }
            editModeElements.forEach(el => el.style.display = 'none');
            viewModeElements.forEach(el => el.style.display = 'block');
        });

        form.addEventListener('submit', function (e) {
            const firstName = form.querySelector('[name="first_name"]').value.trim();
            const lastName  = form.querySelector('[name="last_name"]').value.trim();
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('First name and last name are required!');
            }
        });
    </script>
</body>
</html>
