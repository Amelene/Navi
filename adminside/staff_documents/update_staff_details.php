<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db   = Database::getInstance();
        $conn = $db->getConnection();

        $staff_no = $_POST['staff_no'] ?? '';
        if (empty($staff_no)) throw new Exception("Staff number is required");

        $conn->beginTransaction();

        $sql = "UPDATE staff SET 
                first_name = ?,
                last_name = ?,
                nationality = ?,
                birth_date = ?,
                sex = ?,
                civil_status = ?,
                phone = ?,
                address = ?,
                staff_status = ?,
                emergency_name = ?,
                emergency_relationship = ?,
                emergency_phone = ?,
                sss_no = ?,
                philhealth_no = ?,
                pagibig_no = ?,
                passport_no = ?,
                date_hired = ?,
                last_position = ?,
                status_start_date = ?,
                status_change_date = ?,
                status_reason = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE staff_no = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['first_name']             ?? '',
            $_POST['last_name']              ?? '',
            $_POST['nationality']            ?? '',
            $_POST['birth_date']             ?? null,
            $_POST['sex']                    ?? '',
            $_POST['civil_status']           ?? '',
            $_POST['phone']                  ?? '',
            $_POST['address']                ?? '',
            $_POST['staff_status']           ?? 'active',
            $_POST['emergency_name']         ?? null,
            $_POST['emergency_relationship'] ?? null,
            $_POST['emergency_phone']        ?? null,
            $_POST['sss_no']                 ?? null,
            $_POST['philhealth_no']          ?? null,
            $_POST['pagibig_no']             ?? null,
            $_POST['passport_no']            ?? null,
            !empty($_POST['date_hired'])          ? $_POST['date_hired']          : null,
            $_POST['last_position']          ?? null,
            !empty($_POST['status_start_date'])   ? $_POST['status_start_date']   : null,
            !empty($_POST['status_change_date'])  ? $_POST['status_change_date']  : null,
            $_POST['status_reason']          ?? null,
            $staff_no
        ]);

        $conn->commit();

        $_SESSION['success_message'] = "Staff details updated successfully!";
        header('Location: staff_details.php?id=' . urlencode($staff_no) . '&name=' . urlencode(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '')));
        exit();

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) $conn->rollback();

        $_SESSION['error_message'] = "Error updating staff details: " . $e->getMessage();
        header('Location: staff_details.php?id=' . urlencode($staff_no ?? '') . '&name=' . urlencode($_POST['first_name'] ?? ''));
        exit();
    }
} else {
    header('Location: ../staff.php');
    exit();
}
?>
