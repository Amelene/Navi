<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$row = [
    'id' => 0,
    'vessel_name' => '',
    'position_name' => '',
    'crew_to_be_replaced' => '',
    'license_required' => '',
    'replacement_name' => '',
    'replacement_license' => '',
    'status_type' => 'will_disembark',
    'date_joined' => '',
    'end_of_coe' => '',
    'end_of_extension' => '',
    'contact_number' => '',
    'target_joining_date' => '',
    'place_of_joining' => ''
];

try {
    $db = Database::getInstance();
    $db->execute(
        "CREATE TABLE IF NOT EXISTS crew_changes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vessel_name VARCHAR(255) NOT NULL,
            position_name VARCHAR(255) NOT NULL,
            crew_to_be_replaced VARCHAR(255) NOT NULL,
            license_required VARCHAR(255) NOT NULL,
            replacement_name VARCHAR(255) NOT NULL,
            replacement_license VARCHAR(255) NOT NULL,
            status_type ENUM('will_disembark','will_extend','for_deployment') NOT NULL DEFAULT 'will_disembark',
            date_joined DATE NULL,
            end_of_coe DATE NULL,
            end_of_extension DATE NULL,
            contact_number VARCHAR(100) NULL,
            target_joining_date DATE NULL,
            place_of_joining VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if ($isEdit) {
        $found = $db->fetchOne("SELECT * FROM crew_changes WHERE id = ?", [$id]);
        if ($found) {
            $row = array_merge($row, $found);
        }
    }
} catch (Exception $e) {
    if (defined('DB_DEBUG') && DB_DEBUG) {
        die("Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit Crew Change' : 'Add Crew Change'; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <style>
        .form-card {
            background: rgba(255,255,255,.45);
            border: 1px solid #e5e5e5;
            border-radius: 16px;
            padding: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: .8rem; font-weight: 600; color: #1b3556; }
        .form-group input, .form-group select {
            border: 1px solid #d7d7d7;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: .85rem;
        }
        .form-actions { margin-top: 16px; display: flex; gap: 10px; }
        .btn-save {
            background: #126E82; color: #fff; border: 0; border-radius: 8px;
            padding: 10px 16px; font-weight: 600; cursor: pointer;
        }
        .btn-back {
            background: #2c3e50; color: #fff; border: 0; border-radius: 8px;
            padding: 10px 16px; font-weight: 600; cursor: pointer;
        }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
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
            <h2 class="page-title"><?php echo $isEdit ? 'EDIT CREW CHANGE' : 'ADD NEW CREW CHANGE'; ?></h2>

            <?php if (!empty($_SESSION['error_message'])): ?>
                <div style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:8px;margin-bottom:12px;">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form action="save_change.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Vessel Name</label>
                            <input type="text" name="vessel_name" required value="<?php echo htmlspecialchars($row['vessel_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" name="position_name" required value="<?php echo htmlspecialchars($row['position_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Crew to be Replaced</label>
                            <input type="text" name="crew_to_be_replaced" required value="<?php echo htmlspecialchars($row['crew_to_be_replaced']); ?>">
                        </div>
                        <div class="form-group">
                            <label>License Required</label>
                            <input type="text" name="license_required" required value="<?php echo htmlspecialchars($row['license_required']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Replacement</label>
                            <input type="text" name="replacement_name" required value="<?php echo htmlspecialchars($row['replacement_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>License</label>
                            <input type="text" name="replacement_license" required value="<?php echo htmlspecialchars($row['replacement_license']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status_type" required>
                                <option value="will_disembark" <?php echo $row['status_type'] === 'will_disembark' ? 'selected' : ''; ?>>Will Disembark</option>
                                <option value="will_extend" <?php echo $row['status_type'] === 'will_extend' ? 'selected' : ''; ?>>Will Extend</option>
                                <option value="for_deployment" <?php echo $row['status_type'] === 'for_deployment' ? 'selected' : ''; ?>>For Deployment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Joined</label>
                            <input type="date" name="date_joined" value="<?php echo htmlspecialchars((string)$row['date_joined']); ?>">
                        </div>
                        <div class="form-group">
                            <label>End of COE</label>
                            <input type="date" name="end_of_coe" value="<?php echo htmlspecialchars((string)$row['end_of_coe']); ?>">
                        </div>
                        <div class="form-group">
                            <label>End of Extension</label>
                            <input type="date" name="end_of_extension" value="<?php echo htmlspecialchars((string)$row['end_of_extension']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($row['contact_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Target Joining Date</label>
                            <input type="date" name="target_joining_date" value="<?php echo htmlspecialchars((string)$row['target_joining_date']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Place of Joining</label>
                            <input type="text" name="place_of_joining" value="<?php echo htmlspecialchars($row['place_of_joining']); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save"><?php echo $isEdit ? 'Update' : 'Save'; ?></button>
                        <button type="button" class="btn-back" onclick="window.location.href='../rep.php'">Back</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
