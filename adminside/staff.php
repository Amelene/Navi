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
    
    // Get filter parameters
    $search     = $_GET['search']     ?? '';
    $department = $_GET['department'] ?? '';
    $position   = $_GET['position']   ?? '';
    $status     = $_GET['status']     ?? '';
    
    // Build query
    $sql = "SELECT * FROM vw_staff_details WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (full_name LIKE ? OR staff_no LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($department)) {
        $sql .= " AND department_name = ?";
        $params[] = $department;
    }
    if (!empty($position)) {
        $sql .= " AND position_name = ?";
        $params[] = $position;
    }
    if (!empty($status)) {
        $sql .= " AND staff_status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY staff_no DESC";
    
    $staffRecords = $db->fetchAll($sql, $params);
    
    // Get statistics
    $stats = [
        'total'    => $db->fetchOne("SELECT COUNT(*) as count FROM staff")['count'],
        'active'   => $db->fetchOne("SELECT COUNT(*) as count FROM staff WHERE staff_status = 'active'")['count'],
        'on_leave' => $db->fetchOne("SELECT COUNT(*) as count FROM staff WHERE staff_status = 'on_leave'")['count'],
        'inactive' => $db->fetchOne("SELECT COUNT(*) as count FROM staff WHERE staff_status = 'inactive'")['count']
    ];
    
    $departments = $db->fetchAll("SELECT DISTINCT department_name FROM departments ORDER BY department_name");
    $positions   = $db->fetchAll("SELECT DISTINCT position_name FROM positions WHERE position_name IN ('HR MANAGER', 'HR OFFICER', 'OPERATIONS MANAGER', 'FINANCE MANAGER', 'ACCOUNTANT', 'ADMIN OFFICER') ORDER BY position_name");
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Staff Management</title>
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
                <h2 class="page-title">STAFF MANAGEMENT</h2>

                <div class="metrics">
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">TOTAL STAFF</div>
                            <div class="metric__number"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="metric__icon icon-users">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">ACTIVE STAFF</div>
                            <div class="metric__number"><?php echo $stats['active']; ?></div>
                        </div>
                        <div class="metric__icon icon-check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">ON LEAVE</div>
                            <div class="metric__number"><?php echo $stats['on_leave']; ?></div>
                        </div>
                        <div class="metric__icon icon-clock">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">INACTIVE STAFF</div>
                            <div class="metric__number"><?php echo $stats['inactive']; ?></div>
                        </div>
                        <div class="metric__icon icon-power">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                        </div>
                    </div>
                </div>

                <div class="card card--padded">
                    <div class="card__header">
                        <div class="card__title">Staff Records</div>
                        <div class="card__actions">
                            <button class="btn primary upload">Upload Files</button>
                            <button class="btn warn add">Add New</button>
                        </div>
                    </div>

                    <div class="card__controls">
                        <div class="search-wrap">
                            <div class="search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <input class="input-search" type="search" placeholder="Search..." id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="crew-controls">
                            <button class="btn ghost" onclick="toggleFilter('department')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Department
                            </button>
                            <button class="btn ghost" onclick="toggleFilter('position')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Position
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-wrap">
                            <table class="crew-table">
                                <thead>
                                    <tr>
                                        <th>ID NO.</th>
                                        <th>NAME</th>
                                        <th>POSITION</th>
                                        <th>DEPARTMENT</th>
                                        <th>STATUS</th>
                                        <th>INFORMATION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($staffRecords) > 0): ?>
                                        <?php foreach ($staffRecords as $staff): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($staff['staff_no']); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($staff['full_name'])); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($staff['position_name'] ?? 'N/A')); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($staff['department_name'] ?? 'N/A')); ?></td>
                                                <td class="crew-status <?php 
                                                    echo $staff['staff_status'] === 'active'   ? 'success' : 
                                                        ($staff['staff_status'] === 'on_leave' ? 'warn'    : 'error'); 
                                                ?>">
                                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $staff['staff_status']))); ?>
                                                </td>
                                                <td>
                                                    <a href="staff_documents/staff_details.php?id=<?php echo urlencode($staff['staff_no']); ?>&name=<?php echo urlencode($staff['full_name']); ?>" class="link-action">VIEW DETAILS</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 40px;">
                                                No staff records found.
                                            </td>
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
    
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                window.location.href = '?search=' + encodeURIComponent(this.value);
            }
        });
        
        function toggleFilter(type) {
            alert('Filter functionality coming soon!');
        }
    </script>
</body>
</html>
