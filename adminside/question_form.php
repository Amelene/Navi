<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$question = [
    'id' => 0,
    'exam_category_id' => '',
    'question_id' => '',
    'question_text' => '',
    'image_filename' => '',
    'question_order' => 0
];
$options = [
    'A' => '',
    'B' => '',
    'C' => '',
    'D' => '',
    'E' => ''
];
$correctLetter = 'A';

if ($isEdit) {
    $question = $db->fetchOne("SELECT * FROM questions WHERE id = ? LIMIT 1", [$id]);
    if (!$question) {
        header('Location: question_bank.php');
        exit();
    }

    $optRows = $db->fetchAll(
        "SELECT option_letter, option_text, is_correct
         FROM question_options
         WHERE question_id = ?
         ORDER BY option_letter",
        [$id]
    );

    foreach ($optRows as $row) {
        $letter = $row['option_letter'];
        $options[$letter] = $row['option_text'];
        if ((int)$row['is_correct'] === 1) {
            $correctLetter = $letter;
        }
    }
}

$categories = $db->fetchAll(
    "SELECT id, department, category, vessel_type
     FROM exam_categories
     WHERE status = 'active'
     ORDER BY department, category, vessel_type"
);

$msg = $_GET['msg'] ?? '';
$returnTo = trim($_GET['return_to'] ?? '');
if ($returnTo === '') {
    $returnTo = 'question_bank.php';
}
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="icon type=image/png href=../assets/image/logo.png>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $isEdit ? 'Edit Question' : 'Add Question'; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
    <style>
        .form-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .form-row { margin-bottom:12px; }
        .form-row label { display:block; font-weight:600; margin-bottom:6px; }
        .form-row input, .form-row select, .form-row textarea {
            width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;
        }
        .btn-link { padding:9px 13px; border:none; border-radius:6px; text-decoration:none; display:inline-block; cursor:pointer; }
        .btn-primary { background:#1f6feb; color:#fff; }
        .btn-secondary { background:#6c757d; color:#fff; }
        .preview { margin-top:8px; max-width:220px; border:1px solid #ddd; border-radius:6px; }
        .alert { margin-bottom:10px; padding:10px 12px; border-radius:6px; background:#e7f3ff; color:#084298; }
    </style>
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
            <h2 class="page-title"><?php echo $isEdit ? 'EDIT QUESTION' : 'ADD QUESTION'; ?></h2>

            <div class="card card--padded">
                <?php if ($msg !== ''): ?>
                    <div class="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>

                <form method="POST" action="question_save.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo (int)$question['id']; ?>">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo); ?>">

                    <div class="form-grid">
                        <div class="form-row">
                            <label>Exam Category</label>
                            <select name="exam_category_id" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php
                                    $selected = ((string)$question['exam_category_id'] === (string)$cat['id']) ? 'selected' : '';
                                    $label = $cat['department'] . ' / ' . $cat['category'] . ' / ' . $cat['vessel_type'];
                                    ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label>Question Code (optional)</label>
                            <input type="text" name="question_id" value="<?php echo htmlspecialchars($question['question_id'] ?? ''); ?>" placeholder="e.g. ABSTRACT_Q1">
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Question Text</label>
                        <textarea name="question_text" rows="4" required><?php echo htmlspecialchars($question['question_text'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-row">
                            <label>Question Order</label>
                            <input type="number" name="question_order" min="0" value="<?php echo (int)($question['question_order'] ?? 0); ?>">
                        </div>

                        <div class="form-row">
                            <label>Image (optional)</label>
                            <input type="file" name="question_image" accept=".png,.jpg,.jpeg,.webp,.gif">
                            <?php if (!empty($question['image_filename']) && file_exists('../crewside/abstract_question/' . $question['image_filename'])): ?>
                                <img class="preview" src="<?php echo htmlspecialchars('../crewside/abstract_question/' . $question['image_filename']); ?>" alt="Current image">
                                <label style="display:flex; align-items:center; gap:8px; margin-top:8px; font-weight:500;">
                                    <input type="checkbox" name="remove_image" value="1">
                                    Remove current image
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php foreach (['A','B','C','D','E'] as $letter): ?>
                        <div class="form-row">
                            <label>Choice <?php echo $letter; ?></label>
                            <input type="text" name="option_<?php echo $letter; ?>" required value="<?php echo htmlspecialchars($options[$letter] ?? ''); ?>">
                        </div>
                    <?php endforeach; ?>

                    <div class="form-row">
                        <label>Correct Answer</label>
                        <select name="correct_letter" required>
                            <?php foreach (['A','B','C','D','E'] as $letter): ?>
                                <option value="<?php echo $letter; ?>" <?php echo $correctLetter === $letter ? 'selected' : ''; ?>>
                                    <?php echo $letter; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row" style="display:flex; gap:10px;">
                        <button type="submit" class="btn-link btn-primary">Save Question</button>
                        <a href="<?php echo htmlspecialchars($returnTo); ?>" class="btn-link btn-secondary">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>

