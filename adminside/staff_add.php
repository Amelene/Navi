<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database config
require_once '../config/database.php';

try {
    $db = Database::getInstance();

    // Load departments and positions for dropdowns
    $departments = $db->fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");
    $requiredRoles = [
        'HR MANAGER',
        'ACCOUNTING OFFICER',
        'CREWING OFFICER',
        'FINANCE MANAGER'
    ];

    foreach ($requiredRoles as $roleName) {
        $existingRole = $db->fetchOne("SELECT id FROM positions WHERE position_name = ?", [$roleName]);
        if (!$existingRole) {
            $db->execute("INSERT INTO positions (position_name) VALUES (?)", [$roleName]);
        }
    }

    $positions = $db->fetchAll("
        SELECT id, position_name
        FROM positions
        WHERE position_name IN (
            'HR MANAGER',
            'ACCOUNTING OFFICER',
            'CREWING OFFICER',
            'FINANCE MANAGER'
        )
        ORDER BY FIELD(position_name, 'HR MANAGER', 'ACCOUNTING OFFICER', 'CREWING OFFICER', 'FINANCE MANAGER')
    ");

    // Auto-generate next staff no (format: STF-YYYY-XXX)
    $year = date('Y');
    $latest = $db->fetchOne("SELECT staff_no FROM staff WHERE staff_no LIKE ? ORDER BY id DESC LIMIT 1", ["STF-{$year}-%"]);
    $nextNumber = 1;

    if ($latest && !empty($latest['staff_no'])) {
        if (preg_match('/STF\-\d{4}\-(\d+)/', $latest['staff_no'], $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        }
    }

    $suggestedStaffNo = sprintf('STF-%s-%03d', $year, $nextNumber);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$successData = $_SESSION['staff_add_success'] ?? null;
$errorMessage = $_SESSION['staff_add_error'] ?? '';
unset($_SESSION['staff_add_success'], $_SESSION['staff_add_error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add New Staff</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(260px, 1fr));
            gap: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: #344054;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }
        .form-control {
            height: 44px;
            border: 1px solid #d0d5dd;
            border-radius: 10px;
            padding: 0 12px;
            font-size: 14px;
            background: #fff;
        }
        .form-actions {
            margin-top: 18px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn-link {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .alert {
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-success {
            background: #ecfdf3;
            border: 1px solid #abefc6;
            color: #067647;
        }
        .alert-error {
            background: #fef3f2;
            border: 1px solid #fecdca;
            color: #b42318;
        }
        .invite-box {
            margin-top: 10px;
            background: #ffffff;
            border: 1px dashed #12a8c6;
            border-radius: 10px;
            padding: 12px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <?php
    $GLOBALS['base_path'] = '../';
    $GLOBALS['nav_path']  = '';
    include '../includes/sidebar.php';
    ?>

    <main class="main">
        <div class="main__content">
            <h2 class="page-title">ONBOARD NEW TALENT</h2>

            <div class="card card--padded">
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <?php if (!empty($successData) && is_array($successData)): ?>
                    <div class="alert alert-success">
                        <strong>Staff added successfully.</strong>
                        <div class="invite-box">
                            <div><strong>Email:</strong> <?php echo htmlspecialchars($successData['email'] ?? ''); ?></div>
                            <div><strong>Temporary Password:</strong> <?php echo htmlspecialchars($successData['temp_password'] ?? ''); ?></div>
                            <div><strong>Login URL:</strong> <?php echo htmlspecialchars($successData['login_url'] ?? ''); ?></div>
                            <div><strong>Message:</strong> I-send ito via email manually habang wala pang SMTP integration.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="staff_save.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">System Access Role</label>
                            <select name="system_role" class="form-control" required>
                                <option value="staff" selected>Staff</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="staff_no" class="form-control" value="<?php echo htmlspecialchars($suggestedStaffNo); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="09XXXXXXXXX" required>
                        </div>

                        <div class="form-group full">
                            <label class="form-label">System Access Role</label>
                            <select name="position_id" class="form-control" required>
                                <option value="">Select System Access Role</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo (int)$pos['id']; ?>">
                                        <?php echo htmlspecialchars($pos['position_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="staff_status" class="form-control" required>
                                <option value="active" selected>Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Salary (₱)</label>
                            <input type="number" name="salary" class="form-control" min="0" step="0.01" placeholder="15000">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date Hired</label>
                            <input type="date" name="date_hired" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="staff.php" class="btn ghost btn-link">Cancel</a>
                        <button type="submit" class="btn warn add">Create Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
