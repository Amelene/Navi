<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
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
                            <div class="metric__label">WILL DISEMBARK</div>
                            <div class="metric__number">256</div>
                        </div>
                        <div class="metric__icon" style="color: #e74c3c;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><line x1="9" y1="11" x2="9" y2="17"></line><polyline points="6 14 9 17 12 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">WILL EXTEND</div>
                            <div class="metric__number">216</div>
                        </div>
                        <div class="metric__icon" style="color: #3498db;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                    </div>
                    <div class="metric card">
                        <div class="metric__content">
                            <div class="metric__label">FOR DEPLOYMENT</div>
                            <div class="metric__number">10</div>
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
                            <button class="btn primary upload">Upload Files</button>
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
                                    <tr>
                                        <td class="fw-bold" style="color:#e74c3c;">MV FUTURE 1</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MASTER</td>
                                        <td class="fw-bold" style="color:#e74c3c;">LUCAS A. CRUZ</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MASTER MARINER</td>
                                        <td class="fw-bold" style="color:#e74c3c;">JUSTINE E. LIAM</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MM</td>
                                        <td><a href="crew_change/crew_change_details.php?id=001" class="link-action">VIEW STATUS</a></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#e74c3c;">MV FUTURE 1</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MASTER</td>
                                        <td class="fw-bold" style="color:#e74c3c;">LUCAS A. CRUZ</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MM</td>
                                        <td class="fw-bold" style="color:#e74c3c;">JUSTINE E. LIAM</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MM</td>
                                        <td><a href="crew_change/crew_change_details.php?id=002" class="link-action">VIEW STATUS</a></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold" style="color:#e74c3c;">MV FUTURE 1</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MASTER</td>
                                        <td class="fw-bold" style="color:#e74c3c;">LUCAS A. CRUZ</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MM</td>
                                        <td class="fw-bold" style="color:#e74c3c;">JUSTINE E. LIAM</td>
                                        <td class="fw-bold" style="color:#e74c3c;">MM</td>
                                        <td><a href="crew_change/crew_change_details.php?id=003" class="link-action">VIEW STATUS</a></td>
                                    </tr>
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
