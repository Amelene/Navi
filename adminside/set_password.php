<?php
session_start();

require_once '../config/database.php';

// Avoid session confusion: if someone is already logged in (e.g., admin),
// clear session before handling staff password setup link.
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}

function redirectWithError(string $message): void
{
    $_SESSION['set_password_error'] = $message;
    header('Location: set_password.php');
    exit();
}

function redirectWithSuccess(string $message): void
{
    $_SESSION['set_password_success'] = $message;
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$errorMessage = $_SESSION['set_password_error'] ?? '';
unset($_SESSION['set_password_error']);

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash = $token !== '' ? hash('sha256', $token) : '';

$tokenRow = null;
if ($tokenHash !== '') {
    $tokenRow = $db->fetchOne(
        "SELECT pst.id, pst.user_id, pst.expires_at, pst.used_at, u.email
         FROM password_setup_tokens pst
         INNER JOIN users u ON u.id = pst.user_id
         WHERE pst.token_hash = ?
         LIMIT 1",
        [$tokenHash]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($token === '' || !$tokenRow) {
        redirectWithError('Invalid or missing password setup token.');
    }

    if (!empty($tokenRow['used_at'])) {
        redirectWithError('This password setup link has already been used.');
    }

    if (strtotime($tokenRow['expires_at']) < time()) {
        redirectWithError('This password setup link has expired.');
    }

    if (strlen($password) < 8) {
        redirectWithError('Password must be at least 8 characters.');
    }

    if ($password !== $confirmPassword) {
        redirectWithError('Passwords do not match.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $db->beginTransaction();

        $db->execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$passwordHash, (int)$tokenRow['user_id']]
        );

        $db->execute(
            "UPDATE password_setup_tokens SET used_at = NOW() WHERE id = ?",
            [(int)$tokenRow['id']]
        );

        $db->commit();
    } catch (Exception $e) {
        try {
            $db->rollback();
        } catch (Exception $ignored) {}
        redirectWithError('Unable to set password at the moment. Please try again.');
    }

    redirectWithSuccess('Password created successfully. You can now log in.');
    exit();
}

$validToken = false;
if ($token !== '' && $tokenRow && empty($tokenRow['used_at']) && strtotime($tokenRow['expires_at']) >= time()) {
    $validToken = true;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Set Password</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
    <style>
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card-box {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid #eaecf0;
            border-radius: 12px;
            padding: 22px;
        }
        .title {
            margin: 0 0 14px;
            font-size: 22px;
            font-weight: 700;
            color: #101828;
        }
        .muted {
            color: #475467;
            font-size: 14px;
            margin-bottom: 14px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: #344054;
            text-transform: uppercase;
        }
        .form-control {
            height: 44px;
            border: 1px solid #d0d5dd;
            border-radius: 10px;
            padding: 0 12px;
            font-size: 14px;
        }
        .alert {
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .alert-error {
            background: #fef3f2;
            border: 1px solid #fecdca;
            color: #b42318;
        }
        .btn {
            width: 100%;
            height: 44px;
            border: 0;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            background: #0f766e;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card-box">
        <h1 class="title">Set Your Password</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if (!$validToken): ?>
            <div class="alert alert-error">
                Invalid, used, or expired password setup link.
            </div>
        <?php else: ?>
            <p class="muted">
                Account: <strong><?php echo htmlspecialchars($tokenRow['email']); ?></strong>
            </p>
            <form method="POST" action="set_password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>

                <button type="submit" class="btn">Save Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
