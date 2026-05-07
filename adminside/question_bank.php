<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

$department = trim($_GET['department'] ?? '');
$category = trim($_GET['category'] ?? '');
$vessel = trim($_GET['vessel'] ?? '');
$search = trim($_GET['search'] ?? '');

$activeFilters = [
    'department' => $department,
    'category'   => $category,
    'vessel'     => $vessel,
    'search'     => $search
];
$activeFilters = array_filter($activeFilters, static fn($v) => $v !== '');
$filterQuery = http_build_query($activeFilters);
$returnTo = 'question_bank.php' . ($filterQuery !== '' ? ('?' . $filterQuery) : '');

$where = ["q.status = 'active'"];
$params = [];

if ($department !== '') {
    $where[] = "ec.department = ?";
    $params[] = $department;
}
if ($category !== '') {
    $where[] = "ec.category = ?";
    $params[] = $category;
}
if ($vessel !== '') {
    $where[] = "ec.vessel_type = ?";
    $params[] = $vessel;
}
if ($search !== '') {
    $where[] = "(q.question_text LIKE ? OR q.question_id LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = implode(' AND ', $where);

$questions = $db->fetchAll(
    "SELECT 
        q.id,
        q.question_id,
        q.question_text,
        q.image_filename,
        q.question_order,
        ec.department,
        ec.category,
        ec.vessel_type
     FROM questions q
     INNER JOIN exam_categories ec ON ec.id = q.exam_category_id
     WHERE {$whereSql}
     ORDER BY ec.department, ec.category, ec.vessel_type, q.question_order, q.id",
    $params
);

$categories = $db->fetchAll(
    "SELECT id, department, category, vessel_type
     FROM exam_categories
     WHERE status = 'active'
     ORDER BY department, category, vessel_type"
);

$departments = [];
$categoryNames = [];
$vessels = [];

foreach ($categories as $cat) {
    $departments[$cat['department']] = true;
    $categoryNames[$cat['category']] = true;
    $vessels[$cat['vessel_type']] = true;
}
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="icon type=image/png href=../assets/image/logo.png>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Question Bank - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
    <style>
        .question-tools {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            align-items: center;
        }
        .question-tools input,
        .question-tools select {
            height: 42px;
            min-width: 180px;
            padding: 8px 12px;
            border: 1px solid #d7dbe0;
            border-radius: 8px;
            background: #fff;
        }
        .question-tools input {
            min-width: 260px;
        }
        .question-tools button,
        .btn-link {
            height: 42px;
            padding: 0 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            line-height: 1;
        }
        .btn-primary { background:#1f6feb; color:#fff; }
        .btn-secondary { background:#5f6b76; color:#fff; }
        .btn-danger { background:#dc3545; color:#fff; }
        .btn-primary:hover { background:#1a5fd0; }
        .btn-secondary:hover { background:#4f5963; }
        .btn-danger:hover { background:#c12f3e; }
        .thumb { width:88px; height:58px; object-fit:cover; border-radius:6px; border:1px solid #ddd; }
        .muted { color:#777; font-size:12px; }
        .crew-table td { vertical-align: middle; }
        .table-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .table-actions .btn-link { min-width: 74px; }
        @media (max-width: 900px) {
            .question-tools input,
            .question-tools select {
                min-width: 100%;
                width: 100%;
            }
            .question-tools button,
            .question-tools .btn-link {
                width: 100%;
            }
        }
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
            <h2 class="page-title">QUESTION BANK</h2>

            <div class="card card--padded">
                <div class="card__header">
                    <div class="card__title">Manage Questions</div>
                    <div class="card__actions">
                        <a class="btn-link btn-primary" href="question_form.php?return_to=<?php echo urlencode($returnTo); ?>">+ Add Question</a>
                    </div>
                </div>

                <form method="GET" class="question-tools">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo); ?>">
                    <select name="department">
                        <option value="">All Departments</option>
                        <?php foreach (array_keys($departments) as $dep): ?>
                            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo $department === $dep ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dep); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach (array_keys($categoryNames) as $catName): ?>
                            <option value="<?php echo htmlspecialchars($catName); ?>" <?php echo $category === $catName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($catName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="vessel">
                        <option value="">All Vessel Types</option>
                        <?php foreach (array_keys($vessels) as $ves): ?>
                            <option value="<?php echo htmlspecialchars($ves); ?>" <?php echo $vessel === $ves ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ves); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="search" placeholder="Search question text/id..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="question_bank.php" class="btn-link btn-secondary">Reset</a>
                </form>

                <div class="table-container">
                    <div class="table-wrap">
                        <table class="crew-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Department</th>
                                    <th>Category</th>
                                    <th>Vessel</th>
                                    <th>Question</th>
                                    <th>Image</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($questions)): ?>
                                <tr><td colspan="8" style="text-align:center; padding:20px;">No questions found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                    <?php
                                    $imgSrc = '';
                                    if (!empty($q['image_filename'])) {
                                        $candidate = '../crewside/abstract_question/' . $q['image_filename'];
                                        if (file_exists($candidate)) {
                                            $imgSrc = '../crewside/abstract_question/' . $q['image_filename'];
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo (int)$q['id']; ?><br><span class="muted"><?php echo htmlspecialchars($q['question_id'] ?? ''); ?></span></td>
                                        <td><?php echo htmlspecialchars($q['department']); ?></td>
                                        <td><?php echo htmlspecialchars($q['category']); ?></td>
                                        <td><?php echo htmlspecialchars($q['vessel_type']); ?></td>
                                        <td><?php echo htmlspecialchars(mb_strimwidth($q['question_text'], 0, 90, '...')); ?></td>
                                        <td>
                                            <?php if ($imgSrc): ?>
                                                <img class="thumb" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Question image">
                                            <?php else: ?>
                                                <span class="muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (int)$q['question_order']; ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="btn-link btn-secondary"
                                                   href="question_form.php?id=<?php echo (int)$q['id']; ?>&return_to=<?php echo urlencode($returnTo); ?>">Edit</a>
                                                <a class="btn-link btn-danger"
                                                   href="question_delete.php?id=<?php echo (int)$q['id']; ?>&return_to=<?php echo urlencode($returnTo); ?>"
                                                   onclick="return confirm('Delete this question?');">Delete</a>
                                            </div>
                                        </td>
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

