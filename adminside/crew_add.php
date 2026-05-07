<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$errors = $_SESSION['crew_add_errors'] ?? [];
$old = $_SESSION['crew_add_old'] ?? [];
unset($_SESSION['crew_add_errors'], $_SESSION['crew_add_old']);

try {
    $db = Database::getInstance();

    $positions = $db->fetchAll("SELECT id, position_name FROM positions ORDER BY position_name");
    $vessels = $db->fetchAll("SELECT id, vessel_name FROM vessels ORDER BY vessel_name");
    $departments = $db->fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");

    $year = date('Y');
    $latest = $db->fetchOne("SELECT crew_no FROM crew_master WHERE crew_no LIKE ? ORDER BY id DESC LIMIT 1", ["CRW-{$year}-%"]);
    $nextNumber = 1;

    if (!empty($latest['crew_no']) && preg_match('/CRW-\d{4}-(\d+)/', $latest['crew_no'], $m)) {
        $nextNumber = ((int)$m[1]) + 1;
    }

    $suggestedCrewNo = sprintf('CRW-%s-%03d', $year, $nextNumber);
} catch (Exception $e) {
    die('Error loading crew form data: ' . $e->getMessage());
}

function oldv($key, $default = '')
{
    global $old;
    return htmlspecialchars((string)($old[$key] ?? $default));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add New Crew</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
</head>
<body class="crew-add-page">
<div class="dashboard">
    <?php
    $GLOBALS['base_path'] = '../';
    $GLOBALS['nav_path']  = '';
    include '../includes/sidebar.php';
    ?>

    <main class="main">
        <div class="main__content">
            <h2 class="page-title">ADD NEW CREW</h2>

            <?php if (!empty($errors)): ?>
                <div class="card card--padded" style="border-left:4px solid #f44336; margin-bottom:16px;">
                    <div class="card__title" style="color:#c62828;">Please fix the following:</div>
                    <ul style="margin:8px 0 0 20px; color:#c62828;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card--padded crew-add-card">
                <form action="crew_save.php" method="POST" class="crew-add-form">
                    <div class="grid crew-add-grid">
                        <div>
                            <label class="form-label">Crew No *</label>
                            <input type="text" name="crew_no" class="form-control" required value="<?php echo oldv('crew_no', $suggestedCrewNo); ?>">
                        </div>

                        <div>
                            <label class="form-label">Crew Status *</label>
                            <select name="crew_status" class="form-control" required>
                                <option value="on_board" <?php echo oldv('crew_status', 'on_board') === 'on_board' ? 'selected' : ''; ?>>ON BOARD</option>
                                <option value="on_vacation" <?php echo oldv('crew_status') === 'on_vacation' ? 'selected' : ''; ?>>ON VACATION</option>
                                <option value="inactive" <?php echo oldv('crew_status') === 'inactive' ? 'selected' : ''; ?>>INACTIVE</option>
                                <option value="terminated" <?php echo oldv('crew_status') === 'terminated' ? 'selected' : ''; ?>>TERMINATED</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required value="<?php echo oldv('first_name'); ?>">
                        </div>

                        <div>
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required value="<?php echo oldv('last_name'); ?>">
                        </div>

                        <div>
                            <label class="form-label">On Board Position *</label>
                            <select name="position_id" class="form-control" required>
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>" <?php echo oldv('position_id') == (string)$p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['position_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Vessel Assigned *</label>
                            <select name="vessel_id" class="form-control" required>
                                <option value="">Select Vessel</option>
                                <?php foreach ($vessels as $v): ?>
                                    <option value="<?php echo (int)$v['id']; ?>" <?php echo oldv('vessel_id') == (string)$v['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['vessel_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>" <?php echo oldv('department_id') == (string)$d['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" class="form-control" value="<?php echo oldv('birth_date'); ?>">
                        </div>

                        <div>
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo oldv('phone'); ?>">
                        </div>

                        <div>
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control" value="<?php echo oldv('nationality'); ?>">
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo oldv('address'); ?>">
                        </div>
                    </div>

                    <div class="crew-add-actions">
                        <button type="submit" class="btn warn add">Save Crew</button>
                        <a href="crew.php" class="btn primary upload crew-add-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
