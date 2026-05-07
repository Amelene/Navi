<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: question_bank.php');
    exit();
}

$db = Database::getInstance();

$returnTo = trim($_POST['return_to'] ?? '');
if ($returnTo === '') {
    $returnTo = 'question_bank.php';
}

$id = (int)($_POST['id'] ?? 0);
$exam_category_id = (int)($_POST['exam_category_id'] ?? 0);
$question_id = trim($_POST['question_id'] ?? '');
$question_text = trim($_POST['question_text'] ?? '');
$question_order = (int)($_POST['question_order'] ?? 0);
$correct_letter = strtoupper(trim($_POST['correct_letter'] ?? 'A'));
$remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

$options = [];
foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
    $value = trim($_POST['option_' . $letter] ?? '');
    if ($value === '') {
        $query = ['msg' => "Choice {$letter} is required.", 'return_to' => $returnTo];
        if ($id) {
            $query['id'] = $id;
        }
        header('Location: question_form.php?' . http_build_query($query));
        exit();
    }
    $options[$letter] = $value;
}

if ($exam_category_id <= 0 || $question_text === '') {
    $query = ['msg' => 'Category and question text are required.', 'return_to' => $returnTo];
    if ($id) {
        $query['id'] = $id;
    }
    header('Location: question_form.php?' . http_build_query($query));
    exit();
}

$imageFilename = null;
$uploadDir = '../crewside/abstract_question/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_FILES['question_image']) && ($_FILES['question_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['question_image']['error'] !== UPLOAD_ERR_OK) {
        $query = ['msg' => 'Image upload failed.', 'return_to' => $returnTo];
        if ($id) {
            $query['id'] = $id;
        }
        header('Location: question_form.php?' . http_build_query($query));
        exit();
    }

    $original = $_FILES['question_image']['name'];
    $tmp = $_FILES['question_image']['tmp_name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    if (!in_array($ext, $allowed, true)) {
        $query = ['msg' => 'Invalid image type.', 'return_to' => $returnTo];
        if ($id) {
            $query['id'] = $id;
        }
        header('Location: question_form.php?' . http_build_query($query));
        exit();
    }

    $imageFilename = 'qimg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $uploadDir . $imageFilename)) {
        $query = ['msg' => 'Unable to save image.', 'return_to' => $returnTo];
        if ($id) {
            $query['id'] = $id;
        }
        header('Location: question_form.php?' . http_build_query($query));
        exit();
    }
}

try {
    $db->beginTransaction();

    if ($id > 0) {
        $existing = $db->fetchOne("SELECT image_filename FROM questions WHERE id = ? LIMIT 1", [$id]);
        if (!$existing) {
            throw new Exception('Question not found.');
        }

        if ($imageFilename !== null) {
            // New upload takes priority over remove flag
            $db->execute(
                "UPDATE questions
                 SET exam_category_id = ?, question_id = ?, question_text = ?, image_filename = ?, question_order = ?
                 WHERE id = ?",
                [$exam_category_id, $question_id !== '' ? $question_id : null, $question_text, $imageFilename, $question_order, $id]
            );

            if (!empty($existing['image_filename'])) {
                $old = $uploadDir . $existing['image_filename'];
                if (file_exists($old)) {
                    @unlink($old);
                }
            }
        } elseif ($remove_image) {
            // Remove existing image without uploading a new one
            $db->execute(
                "UPDATE questions
                 SET exam_category_id = ?, question_id = ?, question_text = ?, image_filename = NULL, question_order = ?
                 WHERE id = ?",
                [$exam_category_id, $question_id !== '' ? $question_id : null, $question_text, $question_order, $id]
            );

            if (!empty($existing['image_filename'])) {
                $old = $uploadDir . $existing['image_filename'];
                if (file_exists($old)) {
                    @unlink($old);
                }
            }
        } else {
            $db->execute(
                "UPDATE questions
                 SET exam_category_id = ?, question_id = ?, question_text = ?, question_order = ?
                 WHERE id = ?",
                [$exam_category_id, $question_id !== '' ? $question_id : null, $question_text, $question_order, $id]
            );
        }

        $db->execute("DELETE FROM question_options WHERE question_id = ?", [$id]);
        $questionDbId = $id;
    } else {
        $db->execute(
            "INSERT INTO questions (exam_category_id, question_id, question_text, image_filename, question_order, status)
             VALUES (?, ?, ?, ?, ?, 'active')",
            [$exam_category_id, $question_id !== '' ? $question_id : null, $question_text, $imageFilename, $question_order]
        );
        $questionDbId = (int)$db->lastInsertId();

        // Defensive fallback: if lastInsertId is invalid, resolve by most recent matching row
        if ($questionDbId <= 0) {
            $resolved = $db->fetchOne(
                "SELECT id
                 FROM questions
                 WHERE exam_category_id = ?
                   AND question_text = ?
                   AND status = 'active'
                 ORDER BY id DESC
                 LIMIT 1",
                [$exam_category_id, $question_text]
            );
            $questionDbId = (int)($resolved['id'] ?? 0);
        }
    }

    if ($questionDbId <= 0) {
        throw new Exception('Invalid question ID detected while saving. Save aborted to prevent orphan choices.');
    }

    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
        $db->execute(
            "INSERT INTO question_options (question_id, option_letter, option_text, is_correct)
             VALUES (?, ?, ?, ?)",
            [$questionDbId, $letter, $options[$letter], $letter === $correct_letter ? 1 : 0]
        );
    }

    $db->commit();
    header('Location: ' . $returnTo);
    exit();

} catch (Exception $e) {
    $db->rollback();
    error_log('question_save error: ' . $e->getMessage());
    $query = ['msg' => 'Save failed: ' . $e->getMessage(), 'return_to' => $returnTo];
    if ($id) {
        $query['id'] = $id;
    }
    header('Location: question_form.php?' . http_build_query($query));
    exit();
}
