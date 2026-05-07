<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$crewChanges = [];
$stats = [
    'disembark' => 0,
    'extend' => 0,
    'deploy' => 0
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

    $countAll = $db->fetchOne("SELECT COUNT(*) as c FROM crew_changes");
    if (($countAll['c'] ?? 0) == 0) {
        $db->execute(
            "INSERT INTO crew_changes (vessel_name, position_name, crew_to_be_replaced, license_required, replacement_name, replacement_license, status_type, date_joined, end_of_coe, end_of_extension, contact_number)
             VALUES
             ('MV FUTURE 1', 'MASTER', 'LUCAS A. CRUZ', 'MASTER MARINER', 'JUSTINE E. LIAM', 'MM', 'will_disembark', '2023-01-15', '2024-01-15', '2024-03-15', '0123456789'),
             ('MV FUTURE 1', 'MASTER', 'LUCAS A. CRUZ', 'MM', 'JUSTINE E. LIAM', 'MM', 'will_extend', '2023-01-15', '2024-01-15', '2024-03-15', '0123456789'),
             ('MV FUTURE 1', 'MASTER', 'LUCAS A. CRUZ', 'MM', 'JUSTINE E. LIAM', 'MM', 'for_deployment', '2023-01-15', '2024-01-15', '2024-03-15', '0123456789')"
        );
    }

    $crewChanges = $db->fetchAll("SELECT * FROM crew_changes ORDER BY id DESC");

    $stats['disembark'] = (int)($db->fetchOne("SELECT COUNT(*) as c FROM crew_changes WHERE status_type = 'will_disembark'")['c'] ?? 0);
    $stats['extend']    = (int)($db->fetchOne("SELECT COUNT(*) as c FROM crew_changes WHERE status_type = 'will_extend'")['c'] ?? 0);
    $stats['deploy']    = (int)($db->fetchOne("SELECT COUNT(*) as c FROM crew_changes WHERE status_type = 'for_deployment'")['c'] ?? 0);

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
    <title>Crew Change Status</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
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
                <h2 class="page-title">CREW CHANGE STATUS</h2>

                <div class="metrics" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label" style="color:#e74c3c;">WILL DISEMBARK</div>
                            <div class="metric__number"><?php echo (int)$stats['disembark']; ?></div>
                        </div>
                        <div class="metric__icon" style="color: #e74c3c;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><line x1="9" y1="11" x2="9" y2="17"></line><polyline points="6 14 9 17 12 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label" style="color:#3498db;">WILL EXTEND</div>
                            <div class="metric__number"><?php echo (int)$stats['extend']; ?></div>
                        </div>
                        <div class="metric__icon" style="color: #3498db;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label" style="color:#27ae60;">FOR DEPLOYMENT</div>
                            <div class="metric__number"><?php echo (int)$stats['deploy']; ?></div>
                        </div>
                        <div class="metric__icon" style="color: #27ae60;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><polyline points="9 7 9 1"></polyline><polyline points="6 4 9 1 12 4"></polyline></svg>
                        </div>
                    </div>
                </div>

                <div class="card card--padded">
                    <div class="card__header">
                        <div class="card__title">Crew Records</div>
                        <div class="card__actions">
                            <button class="btn primary upload" onclick="window.location.href='crew_change/crew_change_details.php'">Upload Files</button>
                            <button class="btn warn add" onclick="window.location.href='crew_change/crew_change_form.php'">Add New</button>
                        </div>
                    </div>

                    <div class="card__controls">
                        <div class="search-wrap">
                            <div class="search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <input class="input-search" type="search" placeholder="Search...">
                        </div>
                        <div class="crew-controls">
                            <button class="btn ghost">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                Vessel
                            </button>
                            <button class="btn ghost">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Position
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-wrap">
                            <table class="crew-table">
                                <thead>
                                    <tr>
                                        <th>VESSEL NAME</th>
                                        <th>POSITION</th>
                                        <th>CREW TO BE REPLACED</th>
                                        <th>LICENSE REQUIRED</th>
                                        <th>REPLACEMENT</th>
                                        <th>LICENSE</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($crewChanges) > 0): ?>
                                        <?php foreach ($crewChanges as $row): ?>
                                            <tr>
                                                <?php
                                                    $rowColor = '#e74c3c';
                                                    if (($row['status_type'] ?? '') === 'will_extend') {
                                                        $rowColor = '#3498db';
                                                    } elseif (($row['status_type'] ?? '') === 'for_deployment') {
                                                        $rowColor = '#27ae60';
                                                    }
                                                ?>
                                                <td class="fw-bold" style="color:<?php echo $rowColor; ?>;"><?php echo htmlspecialchars(strtoupper($row['vessel_name'])); ?></td>
                                                <td class="fw-bold" style="color:<?php echo $rowColor; ?>;"><?php echo htmlspecialchars(strtoupper($row['position_name'])); ?></td>
                                                <td class="fw-bold" style="color:<?php echo $rowColor; ?>;"><?php echo htmlspecialchars(strtoupper($row['crew_to_be_replaced'])); ?></td>
                                                <td class="fw-bold" style="color:<?php echo $rowColor; ?>;"><?php echo htmlspecialchars(strtoupper($row['license_required'])); ?></td>
                                                <td class="fw-bold" style="color:<?php echo $rowColor; ?>;"><?php echo htmlspecialchars(strtoupper($row['replacement_name'])); ?></td>
                                                <td class="fw-bold" style="color:<?php echo $rowColor; ?>;"><?php echo htmlspecialchars(strtoupper($row['replacement_license'])); ?></td>
                                                <td><a href="crew_change/crew_change_details.php?id=<?php echo (int)$row['id']; ?>" class="link-action">VIEW STATUS</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center;padding:20px;">No crew change records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
