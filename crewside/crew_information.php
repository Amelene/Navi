<?php
session_start();

// Check if crew is logged in
if (!isset($_SESSION['crew_logged_in']) || $_SESSION['crew_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get department and category from URL
$department = $_GET['dept'] ?? '';
$category = $_GET['category'] ?? '';
if (empty($department) || empty($category)) {
    header('Location: index.php');
    exit();
}

// Format department and category to proper case for display
$department_display = ucwords(strtolower($department));
$category_display = ucwords(strtolower($category));

// Include database config
require_once '../config/database.php';

// Handle ID CHECK request
$crew_data = null;
$error = '';
if (isset($_POST['check_id'])) {
    $crew_no = $_POST['crew_no'] ?? '';
    
    if (!empty($crew_no)) {
        try {
            $db = Database::getInstance();
            $crew_data = $db->fetchOne(
                "SELECT cm.crew_no, 
                        CONCAT(cm.first_name, ' ', cm.last_name) as crew_name,
                        d.department_name
                 FROM crew_master cm
                 LEFT JOIN departments d ON cm.department_id = d.id
                 WHERE cm.crew_no = ? AND cm.user_status = 'active'",
                [$crew_no]
            );
            
            // Format crew name to proper case
            if ($crew_data && isset($crew_data['crew_name'])) {
                $crew_data['crew_name'] = ucwords(strtolower($crew_data['crew_name']));
            }
            
            if (!$crew_data) {
                $error = 'Crew ID not found or inactive';
            }
        } catch (Exception $e) {
            $error = 'Error checking crew ID';
        }
    }
}

// Handle PROCEED request
if (isset($_POST['proceed'])) {
    $crew_no = $_POST['crew_no'] ?? '';
    $crew_name = $_POST['crew_name'] ?? '';
    $dept = $_POST['department'] ?? '';
    $cat = $_POST['category'] ?? '';
    $vessel_type = $_POST['vessel_type'] ?? '';
    $test_date = $_POST['date'] ?? '';
    
    // Here you can add logic to save the examination data
    // For now, we'll just redirect or show success message
    $_SESSION['exam_data'] = [
        'crew_no' => $crew_no,
        'crew_name' => $crew_name,
        'department' => $dept,
        'category' => $cat,
        'vessel_type' => $vessel_type,
        'test_date' => $test_date
    ];
    
    // Get crew_id from database and store in session
    try {
        $db = Database::getInstance();
        $crewInfo = $db->fetchOne("SELECT id FROM crew_master WHERE crew_no = ?", [$crew_no]);
        if ($crewInfo) {
            $_SESSION['crew_id'] = $crewInfo['id'];
        }
    } catch (Exception $e) {
        // Log error but continue
        error_log("Error getting crew_id: " . $e->getMessage());
    }

    // Clear previous exam questions to force reload for new category
    unset($_SESSION['exam_questions']);
    
    header('Location: examination.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Information - <?php echo htmlspecialchars($department . ' - ' . $category); ?></title>
    <link rel="stylesheet" href="crew_information.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="../assets/image/logo.png" alt="NS Logo">
        </div>

        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">CREW INFORMATION</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="crewForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="crew_no">Crew No.</label>
                        <input 
                            type="text" 
                            id="crew_no" 
                            name="crew_no" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($_POST['crew_no'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="crew_name">Crew Name</label>
                        <input 
                            type="text" 
                            id="crew_name" 
                            name="crew_name" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($crew_data['crew_name'] ?? ''); ?>"
                            readonly
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input 
                            type="text" 
                            id="department" 
                            name="department" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($department_display); ?>"
                            readonly
                        >
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input 
                            type="text" 
                            id="category" 
                            name="category" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($category_display); ?>"
                            readonly
                        >
                    </div>
                </div>

                <?php if (strtoupper($department) === 'DECK'): ?>
                <div class="form-group">
                    <label>Vessel Type</label>
                    <div class="vessel-type-buttons">
                        <button type="button" class="vessel-btn" data-value="Oil Tanker" onclick="selectVesselType(this, 'Oil Tanker')">
                            Oil Tanker
                        </button>
                        <button type="button" class="vessel-btn" data-value="Dry Cargo" onclick="selectVesselType(this, 'Dry Cargo')">
                            Dry Cargo
                        </button>
                    </div>
                    <input type="hidden" id="vessel_type" name="vessel_type" required>
                </div>
                <?php else: ?>
                <input type="hidden" id="vessel_type" name="vessel_type" value="GENERAL">
                <?php endif; ?>

                <div class="form-group">
                    <label for="date">Date</label>
                    <input 
                        type="text" 
                        id="date" 
                        name="date" 
                        class="form-input"
                        value="<?php echo date('F d, Y'); ?>"
                        readonly
                    >
                </div>

                <div class="button-group">
                    <button type="submit" name="check_id" class="btn btn-check">ID CHECK</button>
                    <button type="submit" name="proceed" class="btn btn-proceed" 
                        <?php echo empty($crew_data) ? 'disabled' : ''; ?>>
                        PROCEED
                    </button>
                </div>
            </form>

            <div class="back-link">
                <a href="deck_categories.php?dept=<?php echo urlencode($department); ?>">← Back to Categories</a>
            </div>
        </div>
    </div>

    <script>
        function selectVesselType(button, value) {
            // Remove active class from all buttons
            document.querySelectorAll('.vessel-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Set hidden input value
            document.getElementById('vessel_type').value = value;
        }
    </script>
</body>
</html>
