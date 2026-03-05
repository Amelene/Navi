<?php
session_start();

// Clear all crew session variables
unset($_SESSION['crew_logged_in']);
unset($_SESSION['crew_id']);
unset($_SESSION['crew_no']);
unset($_SESSION['crew_name']);
unset($_SESSION['crew_position']);
unset($_SESSION['crew_vessel']);
unset($_SESSION['crew_role']);

// Destroy the session
session_destroy();

// Redirect to crew login page
header('Location: login.php');
exit();
?>
