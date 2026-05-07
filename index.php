<?php
/**
 * Root Entry Point
 * Redirects to the appropriate login page based on access type.
 *
 * Admin side : adminside/login.php
 * Crew side  : crewside/login.php
 */

// If already logged in as admin, go to admin dashboard
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: crewside/index.php');
    exit();
}

// Default: redirect to admin login
header('Location: crewside/login.php');
exit();
?>
