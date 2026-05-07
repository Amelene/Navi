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
            status
        FROM applications 
        ORDER BY submitted_at DESC"
    );
    
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
                                                    <?php if ($app['status'] === 'pending' || $app['status'] === 'on_hold'): ?>
                                                        <form action="application_action.php" method="POST" style="display:inline-flex; gap:6px;">
                                                            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($app['application_id']); ?>">
                                                            <?php if ($app['status'] !== 'confirmed'): ?>
                                                                <button type="submit" name="action" value="accept" class="btn primary upload" style="padding:6px 10px; min-width:auto;">Accept</button>
                                                            <?php endif; ?>
                                                            <?php if ($app['status'] !== 'on_hold'): ?>
                                                                <button type="submit" name="action" value="decline" class="btn warn add" style="padding:6px 10px; min-width:auto; background:#dc2626; border-color:#dc2626;">Decline</button>
                                                            <?php endif; ?>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color:#16a34a; font-weight:700;">Processed</span>
                                                    <?php endif; ?>
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
</body>
</html>
