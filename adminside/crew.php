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
    $search = $_GET['search'] ?? '';
    $vessel = $_GET['vessel'] ?? '';
    $position = $_GET['position'] ?? '';
    $status = $_GET['status'] ?? '';
    
    // Build query
    $sql = "SELECT * FROM vw_crew_details WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (full_name LIKE ? OR crew_no LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($vessel)) {
        $sql .= " AND vessel_name = ?";
        $params[] = $vessel;
    }
    
    if (!empty($position)) {
        $sql .= " AND position_name = ?";
        $params[] = $position;
    }
    
    if (!empty($status)) {
        $sql .= " AND crew_status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY crew_no DESC";
    
    // Fetch crew records
    $crewRecords = $db->fetchAll($sql, $params);

    // Build expiry summary per crew (active docs with expiration_date only)
    $crewNos = [];
    foreach ($crewRecords as $row) {
        if (!empty($row['crew_no'])) {
            $crewNos[] = (string)$row['crew_no'];
        }
    }

    $expiryByCrew = [];
    $expirySummary = [
        'critical_crews' => 0, // at least one doc expiring within 0-7 days
        'warning_crews'  => 0, // at least one doc expiring within 8-30 days (and no critical)
        'normal_crews'   => 0, // no docs in <=30 days, but has future docs >30 days
        'expired_crews'  => 0  // at least one expired doc
    ];

    if (!empty($crewNos)) {
        $placeholders = implode(',', array_fill(0, count($crewNos), '?'));
        $expirySql = "
            SELECT crew_no, expiration_date
            FROM crew_documents
            WHERE crew_no IN ($placeholders)
              AND status = 'active'
              AND expiration_date IS NOT NULL
              AND expiration_date <> '0000-00-00'
        ";
        $expiryRows = $db->fetchAll($expirySql, $crewNos);

        foreach ($crewNos as $cno) {
            $expiryByCrew[$cno] = [
                'critical' => 0,
                'warning'  => 0,
                'normal'   => 0,
                'expired'  => 0
            ];
        }

        if (!empty($expiryRows)) {
            $today = new DateTime('today');
            foreach ($expiryRows as $er) {
                $cno = (string)($er['crew_no'] ?? '');
                $exp = (string)($er['expiration_date'] ?? '');
                if ($cno === '' || $exp === '') continue;

                try {
                    $expDate = new DateTime($exp);
                    $expDate->setTime(0, 0, 0);
                    $days = (int)$today->diff($expDate)->format('%r%a');

                    if (!isset($expiryByCrew[$cno])) {
                        $expiryByCrew[$cno] = ['critical' => 0, 'warning' => 0, 'normal' => 0, 'expired' => 0];
                    }

                    if ($days < 0) {
                        $expiryByCrew[$cno]['expired']++;
                    } elseif ($days <= 7) {
                        $expiryByCrew[$cno]['critical']++;
                    } elseif ($days <= 30) {
                        $expiryByCrew[$cno]['warning']++;
                    } else {
                        $expiryByCrew[$cno]['normal']++;
                    }
                } catch (Exception $ignore) {
                    // ignore invalid dates
                }
            }
        }

        foreach ($expiryByCrew as $bucket) {
            if (($bucket['expired'] ?? 0) > 0) {
                $expirySummary['expired_crews']++;
            }
            if (($bucket['critical'] ?? 0) > 0) {
                $expirySummary['critical_crews']++;
            } elseif (($bucket['warning'] ?? 0) > 0) {
                $expirySummary['warning_crews']++;
            } elseif (($bucket['normal'] ?? 0) > 0) {
                $expirySummary['normal_crews']++;
            }
        }
    }
    
    // Get statistics
    $stats = [
        'total'      => $db->fetchOne("SELECT COUNT(*) as count FROM crew_master")['count'],
        'on_board'   => $db->fetchOne("SELECT COUNT(*) as count FROM crew_master WHERE crew_status = 'on_board'")['count'],
        'on_vacation'=> $db->fetchOne("SELECT COUNT(*) as count FROM crew_master WHERE crew_status = 'on_vacation'")['count'],
        'inactive'   => $db->fetchOne("SELECT COUNT(*) as count FROM crew_master WHERE crew_status = 'inactive'")['count']
    ];
    
    // Get vessels for filter
    $vessels = $db->fetchAll("SELECT vessel_name FROM vessels ORDER BY vessel_name");
    
    // Get positions for filter (all Crew department positions)
    $positions = $db->fetchAll("SELECT position_name FROM positions WHERE department = 'Crew' ORDER BY position_name");
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Management</title>
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
                <h2 class="page-title">CREW MANAGEMENT</h2>

                <div class="metrics">
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">TOTAL CREW</div>
                            <div class="metric__number"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="metric__icon icon-user">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">CREW ON BOARD</div>
                            <div class="metric__number"><?php echo $stats['on_board']; ?></div>
                        </div>
                        <div class="metric__icon icon-check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">ON VACATION</div>
                            <div class="metric__number"><?php echo $stats['on_vacation']; ?></div>
                        </div>
                        <div class="metric__icon icon-clock">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">INACTIVE CREW</div>
                            <div class="metric__number"><?php echo $stats['inactive']; ?></div>
                        </div>
                        <div class="metric__icon icon-power">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                        </div>
                    </div>
                </div>

                <div class="card card--padded">
                    <div class="card__header">
                        <div class="card__title">Crew Records</div>
                        <div class="card__actions">
                            <button class="btn primary upload" onclick="window.location.href='crew_upload.php'">Upload Files</button>
                            <button class="btn warn add" onclick="window.location.href='crew_add.php'">Add New</button>
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
                            <div class="vessel-select-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                <select class="vessel-select" id="vesselFilter" onchange="applyFilter()">
                                    <option value="">All Vessels</option>
                                    <?php foreach ($vessels as $v): ?>
                                        <option value="<?php echo htmlspecialchars($v['vessel_name']); ?>" 
                                            <?php echo $vessel === $v['vessel_name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($v['vessel_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="position-select-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                <select class="position-select" id="positionFilter" onchange="applyFilter()">
                                    <option value="">All Positions</option>
                                    <?php foreach ($positions as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['position_name']); ?>"
                                            <?php echo $position === $p['position_name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['position_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-wrap">
                            <table class="crew-table">
                                <thead>
                                    <tr>
                                        <th class="col-crewno">Crew No.</th>
                                        <th>NAME</th>
                                        <th>ON BOARD POSITION</th>
                                        <th>VESSEL ASSIGNED</th>
                                        <th>STATUS</th>
                                        <th>INFORMATION</th>
                                        <th>DOCUMENTS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($crewRecords) > 0): ?>
                                        <?php foreach ($crewRecords as $crew): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($crew['crew_no']); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($crew['full_name'])); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($crew['position_name'] ?? 'N/A')); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($crew['vessel_name'] ?? 'N/A')); ?></td>
                                                <td class="crew-status <?php 
                                                    echo $crew['crew_status'] === 'on_board' ? 'success' : 
                                                        ($crew['crew_status'] === 'on_vacation' ? 'warn' : 'error'); 
                                                ?>">
                                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $crew['crew_status']))); ?>
                                                </td>
                                                <td>
                                                    <a href="crew_documents/crew_details.php?id=<?php echo urlencode($crew['crew_no']); ?>&name=<?php echo urlencode($crew['full_name']); ?>" class="link-action">VIEW DETAILS</a>
                                                </td>
                                                <td>
                                                    <a href="crew_documents/crew_documents.php?id=<?php echo urlencode($crew['crew_no']); ?>&name=<?php echo urlencode($crew['full_name']); ?>" class="link-action">VIEW DOCUMENTS</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 40px;">
                                                No crew records found.
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
                applyFilter();
            }
        });
        
        function applyFilter() {
            const search = document.getElementById('searchInput').value;
            const vessel = document.getElementById('vesselFilter').value;
            const position = document.getElementById('positionFilter').value;
            
            let url = 'crew.php?';
            let params = [];
            
            if (search)   params.push('search='   + encodeURIComponent(search));
            if (vessel)   params.push('vessel='   + encodeURIComponent(vessel));
            if (position) params.push('position=' + encodeURIComponent(position));
            
            window.location.href = params.length === 0 ? 'crew.php' : url + params.join('&');
        }
    </script>
</body>
</html>
