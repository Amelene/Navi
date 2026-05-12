<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../config/database.php';

// Fetch applications from database
try {
    $db = Database::getInstance();
    
    $total_count     = $db->fetchOne("SELECT COUNT(*) as count FROM applications")['count'];
    $confirmed_count = $db->fetchOne("SELECT COUNT(*) as count FROM applications WHERE status = 'confirmed'")['count'];
    $pending_count   = $db->fetchOne("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'")['count'];
    $onhold_count    = $db->fetchOne("SELECT COUNT(*) as count FROM applications WHERE status = 'on_hold'")['count'];
    
    $applications = $db->fetchAll(
        "SELECT 
            application_id,
            name,
            DATE_FORMAT(submitted_at, '%m - %d - %Y') as date_submitted,
            DATE_FORMAT(submitted_at, '%h:%i %p') as time_submitted,
            status,
            position_applied
        FROM applications 
        ORDER BY submitted_at DESC"
    );

    $vessels = $db->fetchAll("SELECT id, vessel_name FROM vessels ORDER BY vessel_name ASC");
    
} catch (Exception $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $total_count = $confirmed_count = $pending_count = $onhold_count = 0;
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Record</title>
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
                <h2 class="page-title">APPLICATION RECORD</h2>

                <div class="metrics">
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">TOTAL APPLICANT</div>
                            <div class="metric__number"><?php echo $total_count; ?></div>
                        </div>
                        <div class="metric__icon icon-user">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">CONFIRMED</div>
                            <div class="metric__number"><?php echo $confirmed_count; ?></div>
                        </div>
                        <div class="metric__icon icon-check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">PENDING</div>
                            <div class="metric__number"><?php echo $pending_count; ?></div>
                        </div>
                        <div class="metric__icon icon-clock">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">ON HOLD</div>
                            <div class="metric__number"><?php echo $onhold_count; ?></div>
                        </div>
                        <div class="metric__icon icon-power">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                        </div>
                    </div>
                </div>

                <div class="card card--padded">
                    <div class="card__header">
                        <div class="card__title">Applicants</div>
                        <div class="card__actions">
                            <button class="btn warn add">Add New</button>
                        </div>
                    </div>

                    <div class="card__controls">
                        <div class="search-wrap">
                            <div class="search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <input class="input-search" type="search" placeholder="Search...">
                        </div>
                    </div>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success" style="margin-bottom:10px;">
                            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-error" style="margin-bottom:10px;">
                            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-container">
                        <div class="table-wrap">
                            <table class="crew-table">
                                <thead>
                                    <tr>
                                        <th>APPLICANT ID</th>
                                        <th>NAME</th>
                                        <th>DATE SUBMITTED</th>
                                        <th>TIME</th>
                                        <th>STATUS</th>
                                        <th>ACTION</th>
                                        <th>APPLICATION DETAILS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($applications)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 30px; color: #6b7280;">
                                                No applications found. Applications will appear here once submitted.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($applications as $app): ?>
                                            <?php
                                            $status_class = 'success';
                                            $status_text  = 'CONFIRMED';
                                            if ($app['status'] === 'pending') {
                                                $status_class = 'warn';
                                                $status_text  = 'PENDING';
                                            } elseif ($app['status'] === 'on_hold' || $app['status'] === 'rejected') {
                                                $status_class = 'danger';
                                                $status_text  = strtoupper(str_replace('_', ' ', $app['status']));
                                            }
                                            ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($app['application_id']); ?></td>
                                                <td class="fw-bold"><?php echo strtoupper(htmlspecialchars($app['name'])); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($app['date_submitted']); ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($app['time_submitted']); ?></td>
                                                <td class="crew-status <?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                                                <td>
                                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                        <?php if ($app['status'] === 'pending' || $app['status'] === 'on_hold'): ?>
                                                            <button
                                                                type="button"
                                                                class="btn primary upload btn-open-confirm-modal"
                                                                style="padding:6px 10px; min-width:auto;"
                                                                data-application-id="<?php echo htmlspecialchars($app['application_id']); ?>"
                                                                data-applicant-name="<?php echo htmlspecialchars($app['name']); ?>"
                                                                data-position-applied="<?php echo htmlspecialchars((string)($app['position_applied'] ?? '')); ?>"
                                                            >
                                                                Confirm
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($app['status'] !== 'on_hold'): ?>
                                                            <form action="application_action.php" method="POST" style="display:inline;">
                                                                <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($app['application_id']); ?>">
                                                                <input type="hidden" name="action" value="on_hold">
                                                                <button type="submit" class="btn warn add" style="padding:6px 10px; min-width:auto; background:#f59e0b; border-color:#f59e0b;">
                                                                    On Hold
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <form action="application_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this application and all related uploaded files? This cannot be undone.');">
                                                            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($app['application_id']); ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn warn add" style="padding:6px 10px; min-width:auto; background:#dc2626; border-color:#dc2626;">
                                                                Delete
                                                            </button>
                                                        </form>

                                                        <?php if ($app['status'] === 'confirmed'): ?>
                                                            <span style="color:#16a34a; font-weight:700;">Processed</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><a href="application_details.php?id=<?php echo urlencode($app['application_id']); ?>" class="link-action">VIEW DETAILS</a></td>
                                            </tr>
                                        <?php endforeach; ?>
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

    <div id="confirmVesselModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:10px; width:min(520px, 92vw); padding:18px;">
            <h3 style="margin:0 0 10px 0; color:#0f172a;">Confirm Applicant to Crew</h3>
            <p id="confirmVesselText" style="margin:0 0 14px 0; color:#475569; font-size:14px;"></p>

            <form action="application_action.php" method="POST" id="confirmVesselForm">
                <input type="hidden" name="application_id" id="confirmApplicationId">
                <input type="hidden" name="action" value="accept">

                <label for="confirmVesselId" style="display:block; font-weight:600; margin-bottom:6px;">Select Vessel</label>
                <select name="vessel_id" id="confirmVesselId" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; margin-bottom:14px;">
                    <option value="">-- Select Vessel --</option>
                    <?php foreach ($vessels as $v): ?>
                        <option value="<?php echo (int)$v['id']; ?>"><?php echo htmlspecialchars($v['vessel_name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" id="cancelConfirmVessel" class="btn" style="background:#e2e8f0; border-color:#e2e8f0; color:#0f172a;">Cancel</button>
                    <button type="submit" class="btn primary upload">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('confirmVesselModal');
            const txt = document.getElementById('confirmVesselText');
            const appIdInput = document.getElementById('confirmApplicationId');
            const cancelBtn = document.getElementById('cancelConfirmVessel');

            document.querySelectorAll('.btn-open-confirm-modal').forEach(btn => {
                btn.addEventListener('click', function () {
                    const appId = this.getAttribute('data-application-id') || '';
                    const name = this.getAttribute('data-applicant-name') || '';
                    const position = this.getAttribute('data-position-applied') || '';

                    appIdInput.value = appId;
                    txt.textContent = `Applicant: ${name}${position ? ' | Position: ' + position : ''}. Please choose vessel assignment before confirming.`;
                    modal.style.display = 'flex';
                });
            });

            cancelBtn?.addEventListener('click', function () {
                modal.style.display = 'none';
            });

            modal?.addEventListener('click', function (e) {
                if (e.target === modal) modal.style.display = 'none';
            });
        })();
    </script>
</body>
</html>
