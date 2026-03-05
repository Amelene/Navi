<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$change_id = isset($_GET['id']) ? $_GET['id'] : '001';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Change Status Details</title>
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/content.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="crew_change_details.css?v=<?php echo time(); ?>">
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
                <h2 class="page-title">CREW CHANGE STATUS</h2>

                <div class="card card--padded change-card">
                    <!-- Header -->
                    <div class="change-header">
                        <div class="status-info">
                            <span class="status-value-header">WILL DISEMBARK</span>
                        </div>
                        <div class="change-actions">
                            <button class="btn-action btn-edit">EDIT</button>
                            <button class="btn-action btn-delete">DELETE</button>
                            <button class="btn-close" onclick="window.location.href='../rep.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Crew Information Section -->
                    <div class="change-section">
                        <h3 class="section-title">CREW INFORMATION</h3>
                        <div class="section-content-grid">
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Position</span>
                                    <span class="info-value">Master</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Crew to be Replaced</span>
                                    <span class="info-value">Lucas A. Cruz</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">License Required</span>
                                    <span class="info-value">Master Mariner (MM)</span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Date Joined</span>
                                    <span class="info-value">01-15-2023</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">End of COE</span>
                                    <span class="info-value">01-15-2024</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">End of Extension</span>
                                    <span class="info-value">03-15-2024</span>
                                </div>
                            </div>
                        </div>

                        <div class="remarks-section">
                            <div class="remarks-header">
                                <span class="remarks-label">Remarks</span>
                                <button class="btn-edit-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="remarks-content">
                                <textarea class="remarks-textarea" rows="3" placeholder="Enter remarks here..."></textarea>
                            </div>
                        </div>

                        <div class="answer-section">
                            <div class="answer-label">Answer</div>
                            <div class="answer-content">
                                <textarea class="answer-textarea" rows="3" placeholder="Enter answer here..."></textarea>
                            </div>
                        </div>

                        <div class="upload-buttons">
                            <div class="upload-group">
                                <span class="upload-label">Request For Relieve</span>
                                <button class="btn-upload">Upload Files</button>
                            </div>
                            <div class="upload-group">
                                <span class="upload-label">Request For Extension</span>
                                <button class="btn-upload">Upload Files</button>
                            </div>
                        </div>
                    </div>

                    <!-- Relieve Candidate Section -->
                    <div class="change-section">
                        <h3 class="section-title">RELIEVE CANDIDATE</h3>
                        <div class="section-content-grid">
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Candidates</span>
                                    <span class="info-value">Michael Johnson</span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">License</span>
                                    <span class="info-value">Master Mariner (MM)</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Contact Number</span>
                                    <span class="info-value">0123456789</span>
                                </div>
                            </div>
                        </div>

                        <div class="remarks-section">
                            <div class="remarks-header">
                                <span class="remarks-label">Candidate Remarks</span>
                                <button class="btn-edit-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="remarks-content">
                                <textarea class="remarks-textarea" rows="3" placeholder="Enter candidate remarks here..."></textarea>
                            </div>
                        </div>

                        <div class="answer-section">
                            <div class="answer-label">Questions/Remarks</div>
                            <div class="answer-content">
                                <textarea class="answer-textarea" rows="3" placeholder="Enter questions or remarks here..."></textarea>
                            </div>
                        </div>

                        <div class="answer-section">
                            <div class="answer-label">Answer</div>
                            <div class="answer-content">
                                <textarea class="answer-textarea" rows="3" placeholder="Enter answer here..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Joining Information Section -->
                    <div class="change-section">
                        <h3 class="section-title">JOINING INFORMATION</h3>
                        <div class="section-content-grid">
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Target Joining Date</span>
                                    <span class="info-value"></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-item">
                                    <span class="info-label">Place of Joining</span>
                                    <span class="info-value"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
