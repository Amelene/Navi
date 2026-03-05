<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$staff_no = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($staff_no)) {
    header('Location: ../staff.php');
    exit();
}

try {
    $db = Database::getInstance();

    $sql = "SELECT 
                s.*,
                CONCAT(s.first_name, ' ', s.last_name) as full_name,
                v.vessel_name,
                p.position_name,
                d.department_name
            FROM staff s
            LEFT JOIN vessels v ON s.vessel_id = v.id
            LEFT JOIN positions p ON s.position_id = p.id
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE s.staff_no = ?";

    $staff = $db->fetchOne($sql, [$staff_no]);

    if (!$staff) {
        $_SESSION['error_message'] = "Staff not found";
        header('Location: ../staff.php');
        exit();
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching staff details: " . $e->getMessage();
    header('Location: ../staff.php');
    exit();
}

$age = '';
if (!empty($staff['birth_date'])) {
    $age = (new DateTime())->diff(new DateTime($staff['birth_date']))->y;
}

$years_employed = '';
if (!empty($staff['date_hired'])) {
    $diff = (new DateTime())->diff(new DateTime($staff['date_hired']));
    $years_employed = $diff->y . ' year' . ($diff->y != 1 ? 's' : '');
    if ($diff->m > 0) $years_employed .= ', ' . $diff->m . ' month' . ($diff->m != 1 ? 's' : '');
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
    <title>Staff Details - <?php echo htmlspecialchars($staff['full_name']); ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="staff_details.css?v=<?php echo time(); ?>">
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
                <h2 class="page-title">STAFF DETAILS</h2>

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

                <form id="staffDetailsForm" method="POST" action="update_staff_details.php">
                    <input type="hidden" name="staff_no" value="<?php echo htmlspecialchars($staff_no); ?>">

                    <div class="card card--padded details-card">
                        <!-- Header -->
                        <div class="details-header">
                            <div class="status-info">
                                <span class="status-label">STATUS:</span>
                                <span class="status-value status-<?php echo htmlspecialchars($staff['staff_status']); ?> view-mode" id="statusDisplay">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $staff['staff_status']))); ?>
                                </span>
                                <select name="staff_status" class="status-select edit-mode" id="statusSelect" style="display: none;">
                                    <option value="active"   <?php echo $staff['staff_status'] === 'active'   ? 'selected' : ''; ?>>ACTIVE</option>
                                    <option value="on_leave" <?php echo $staff['staff_status'] === 'on_leave' ? 'selected' : ''; ?>>ON LEAVE</option>
                                    <option value="inactive" <?php echo $staff['staff_status'] === 'inactive' ? 'selected' : ''; ?>>INACTIVE</option>
                                </select>
                            </div>
                            <div class="details-actions">
                                <button type="button" class="btn-action btn-edit view-mode" id="editBtn">EDIT</button>
                                <button type="submit" class="btn-action btn-save edit-mode" id="saveBtn" style="display: none;">SAVE</button>
                                <button type="button" class="btn-action btn-cancel edit-mode" id="cancelBtn" style="display: none;">CANCEL</button>
                                <button type="button" class="btn-action btn-delete view-mode">DELETE</button>
                                <button type="button" class="btn-close" onclick="window.location.href='../staff.php'">
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
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['full_name']); ?></span>
                                        <div class="edit-mode name-inputs" style="display: none;">
                                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($staff['first_name']); ?>" placeholder="First Name" class="name-input">
                                            <input type="text" name="last_name"  value="<?php echo htmlspecialchars($staff['last_name']);  ?>" placeholder="Last Name"  class="name-input">
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Age:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($age); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date of Birth:</span>
                                        <span class="detail-value view-mode"><?php echo formatDate($staff['birth_date']); ?></span>
                                        <input type="date" name="birth_date" class="edit-mode" value="<?php echo formatDateInput($staff['birth_date']); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Sex:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['sex'] ?? 'N/A'); ?></span>
                                        <select name="sex" class="edit-mode" style="display: none;">
                                            <option value="Male"   <?php echo $staff['sex'] === 'Male'   ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $staff['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Phone Number:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></span>
                                        <input type="text" name="phone" class="edit-mode" value="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Nationality:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['nationality'] ?? 'N/A'); ?></span>
                                        <input type="text" name="nationality" class="edit-mode" value="<?php echo htmlspecialchars($staff['nationality'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Civil Status:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['civil_status'] ?? 'N/A'); ?></span>
                                        <select name="civil_status" class="edit-mode" style="display: none;">
                                            <option value="Single"   <?php echo $staff['civil_status'] === 'Single'   ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married"  <?php echo $staff['civil_status'] === 'Married'  ? 'selected' : ''; ?>>Married</option>
                                            <option value="Divorced" <?php echo $staff['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="Widowed"  <?php echo $staff['civil_status'] === 'Widowed'  ? 'selected' : ''; ?>>Widowed</option>
                                        </select>
                                    </div>
                                    <div class="detail-item detail-item-full">
                                        <span class="detail-label">Address:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['address'] ?? 'N/A'); ?></span>
                                        <input type="text" name="address" class="edit-mode" value="<?php echo htmlspecialchars($staff['address'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Details -->
                            <div class="details-section">
                                <h3 class="section-title">EMPLOYMENT DETAILS</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Position:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($staff['position_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Department:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($staff['department_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date Hired:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($staff['date_hired']) ? formatDate($staff['date_hired']) : 'N/A'; ?></span>
                                        <input type="date" name="date_hired" class="edit-mode" value="<?php echo !empty($staff['date_hired']) ? formatDateInput($staff['date_hired']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Years of Employment:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($years_employed ?: 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="details-section">
                                <h3 class="section-title">EMERGENCY CONTACT</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Name:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['emergency_name'] ?? 'N/A'); ?></span>
                                        <input type="text" name="emergency_name" class="edit-mode" value="<?php echo htmlspecialchars($staff['emergency_name'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Relationship:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['emergency_relationship'] ?? 'N/A'); ?></span>
                                        <input type="text" name="emergency_relationship" class="edit-mode" value="<?php echo htmlspecialchars($staff['emergency_relationship'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Phone Number:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['emergency_phone'] ?? 'N/A'); ?></span>
                                        <input type="text" name="emergency_phone" class="edit-mode" value="<?php echo htmlspecialchars($staff['emergency_phone'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Status History -->
                            <div class="details-section" id="statusHistorySection" style="display: <?php echo $staff['staff_status'] === 'inactive' ? 'block' : 'none'; ?>;">
                                <h3 class="section-title">STATUS HISTORY</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">Last Position:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['last_position'] ?? 'N/A'); ?></span>
                                        <input type="text" name="last_position" class="edit-mode" value="<?php echo htmlspecialchars($staff['last_position'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Start Date:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($staff['status_start_date']) ? formatDate($staff['status_start_date']) : 'N/A'; ?></span>
                                        <input type="date" name="status_start_date" class="edit-mode" value="<?php echo !empty($staff['status_start_date']) ? formatDateInput($staff['status_start_date']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Date of Status Change:</span>
                                        <span class="detail-value view-mode"><?php echo !empty($staff['status_change_date']) ? formatDate($staff['status_change_date']) : 'N/A'; ?></span>
                                        <input type="date" name="status_change_date" class="edit-mode" value="<?php echo !empty($staff['status_change_date']) ? formatDateInput($staff['status_change_date']) : ''; ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Reason:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['status_reason'] ?? 'N/A'); ?></span>
                                        <input type="text" name="status_reason" class="edit-mode" value="<?php echo htmlspecialchars($staff['status_reason'] ?? ''); ?>" style="display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Government Numbers -->
                            <div class="details-section">
                                <h3 class="section-title">GOVERNMENT NUMBERS</h3>
                                <div class="section-content">
                                    <div class="detail-item">
                                        <span class="detail-label">SSS:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['sss_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="sss_no" class="edit-mode" value="<?php echo htmlspecialchars($staff['sss_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">PhilHealth:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['philhealth_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="philhealth_no" class="edit-mode" value="<?php echo htmlspecialchars($staff['philhealth_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">PAG-IBIG:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['pagibig_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="pagibig_no" class="edit-mode" value="<?php echo htmlspecialchars($staff['pagibig_no'] ?? ''); ?>" style="display: none;">
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Passport:</span>
                                        <span class="detail-value view-mode"><?php echo htmlspecialchars($staff['passport_no'] ?? 'N/A'); ?></span>
                                        <input type="text" name="passport_no" class="edit-mode" value="<?php echo htmlspecialchars($staff['passport_no'] ?? ''); ?>" style="display: none;">
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
        const editBtn            = document.getElementById('editBtn');
        const saveBtn            = document.getElementById('saveBtn');
        const cancelBtn          = document.getElementById('cancelBtn');
        const form               = document.getElementById('staffDetailsForm');
        const statusSelect       = document.getElementById('statusSelect');
        const statusHistorySection = document.getElementById('statusHistorySection');

        const viewModeElements = document.querySelectorAll('.view-mode');
        const editModeElements = document.querySelectorAll('.edit-mode');

        let originalFormData = new FormData(form);

        function toggleStatusHistory() {
            if (statusSelect && statusHistorySection) {
                statusHistorySection.style.display = statusSelect.value === 'inactive' ? 'block' : 'none';
            }
        }

        if (statusSelect) statusSelect.addEventListener('change', toggleStatusHistory);

        editBtn.addEventListener('click', function () {
            originalFormData = new FormData(form);
            viewModeElements.forEach(el => el.style.display = 'none');
            editModeElements.forEach(el => el.style.display = 'block');
            toggleStatusHistory();
        });

        cancelBtn.addEventListener('click', function () {
            for (let [key, value] of originalFormData.entries()) {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) input.value = value;
            }
            editModeElements.forEach(el => el.style.display = 'none');
            viewModeElements.forEach(el => el.style.display = 'block');
            toggleStatusHistory();
        });

        form.addEventListener('submit', function (e) {
            const firstName = form.querySelector('[name="first_name"]').value.trim();
            const lastName  = form.querySelector('[name="last_name"]').value.trim();
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('First name and last name are required!');
            }
        });

        toggleStatusHistory();
    </script>
</body>
</html>
