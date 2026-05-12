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

        $crew_no = $_POST['crew_no'] ?? '';
        if (empty($crew_no)) throw new Exception("Crew number is required");

        $positionId = !empty($_POST['position_id']) ? (int)$_POST['position_id'] : null;
        if ($positionId !== null) {
            $validPosition = $db->fetchOne(
                "SELECT id FROM positions WHERE id = ? AND department = 'Crew' LIMIT 1",
                [$positionId]
            );
            if (!$validPosition) {
                throw new Exception("Selected position is invalid for Crew department.");
            }
        }

        $conn->beginTransaction();

        $sql = "UPDATE crew_master SET 
                first_name = ?,
                last_name = ?,
                nationality = ?,
                birth_date = ?,
                sex = ?,
                civil_status = ?,
                phone = ?,
                address = ?,
                crew_status = ?,
                position_id = ?,
                vessel_id = ?,
                emergency_name = ?,
                emergency_relationship = ?,
                emergency_phone = ?,
                bank_name = ?,
                bank_account = ?,
                sss_no = ?,
                philhealth_no = ?,
                pagibig_no = ?,
                passport_no = ?,
                srn_no = ?,
                remarks = ?,
                sirb_no = ?,
                sirb_expiry = ?,
                dcoc_no = ?,
                dcoc_expiry = ?,
                seamans_book_no = ?,
                seamans_book_expiry = ?,
                embarkation_date = ?,
                embarkation_place = ?,
                disembarkation_date = ?,
                disembarkation_place = ?,
                disembarkation_reason = ?,
                contract_start = ?,
                contract_end = ?,
                extension_contract = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE crew_no = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['first_name']          ?? '',
            $_POST['last_name']           ?? '',
            $_POST['nationality']         ?? '',
            $_POST['birth_date']          ?? null,
            $_POST['sex']                 ?? '',
            $_POST['civil_status']        ?? '',
            $_POST['phone']               ?? '',
            $_POST['address']             ?? '',
            $_POST['crew_status']         ?? 'on_vacation',
            $positionId,
            !empty($_POST['vessel_id'])   ? (int)$_POST['vessel_id']   : null,
            $_POST['emergency_name']         ?? null,
            $_POST['emergency_relationship'] ?? null,
            $_POST['emergency_phone']        ?? null,
            $_POST['bank_name']           ?? null,
            $_POST['bank_account']        ?? null,
            $_POST['sss_no']              ?? null,
            $_POST['philhealth_no']       ?? null,
            $_POST['pagibig_no']          ?? null,
            $_POST['passport_no']         ?? null,
            $_POST['srn_no']              ?? null,
            $_POST['remarks']             ?? null,
            $_POST['sirb_no']             ?? null,
            !empty($_POST['sirb_expiry'])          ? $_POST['sirb_expiry']          : null,
            $_POST['dcoc_no']             ?? null,
            !empty($_POST['dcoc_expiry'])          ? $_POST['dcoc_expiry']          : null,
            $_POST['seamans_book_no']     ?? null,
            !empty($_POST['seamans_book_expiry'])  ? $_POST['seamans_book_expiry']  : null,
            !empty($_POST['embarkation_date'])     ? $_POST['embarkation_date']     : null,
            $_POST['embarkation_place']   ?? null,
            !empty($_POST['disembarkation_date'])  ? $_POST['disembarkation_date']  : null,
            $_POST['disembarkation_place']  ?? null,
            $_POST['disembarkation_reason'] ?? null,
            !empty($_POST['contract_start']) ? $_POST['contract_start'] : null,
            !empty($_POST['contract_end'])   ? $_POST['contract_end']   : null,
            $_POST['extension_contract']  ?? null,
            $crew_no
        ]);

        $conn->commit();

        $_SESSION['success_message'] = "Crew details updated successfully!";
        header('Location: crew_details.php?id=' . urlencode($crew_no) . '&name=' . urlencode(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '')));
        exit();

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) $conn->rollback();

        $_SESSION['error_message'] = "Error updating crew details: " . $e->getMessage();
        header('Location: crew_details.php?id=' . urlencode($crew_no ?? '') . '&name=' . urlencode($_POST['first_name'] ?? ''));
        exit();
    }
} else {
    header('Location: ../crew.php');
    exit();
}
?>
