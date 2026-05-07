<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

try {
    $db = Database::getInstance();

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $sessionEmail = trim($_SESSION['user_email'] ?? '');
    $sessionRole = strtoupper(trim($_SESSION['user_role'] ?? 'STAFF'));

    $user = null;
    if ($userId > 0) {
        $user = $db->fetchOne("SELECT id, email, role, created_at, updated_at FROM users WHERE id = ? LIMIT 1", [$userId]);
    }

    $email = $user['email'] ?? $sessionEmail;
    $role = strtoupper($user['role'] ?? strtolower($sessionRole));
    if ($role === '') {
        $role = 'STAFF';
    }

    $displayName = 'User';

    $staffProfile = null;
    if ($userId > 0) {
        $staffProfile = $db->fetchOne(
            "SELECT first_name, last_name
             FROM staff
             WHERE auth_user_id = ?
             LIMIT 1",
            [$userId]
        );
    }

    if ($staffProfile && (!empty($staffProfile['first_name']) || !empty($staffProfile['last_name']))) {
        $nameParts = [
            trim($staffProfile['first_name'] ?? ''),
            trim($staffProfile['last_name'] ?? '')
        ];
        $nameParts = array_values(array_filter($nameParts, function ($v) {
            return $v !== '';
        }));
        $displayName = implode(' ', $nameParts);
    } elseif (!empty($email) && strpos($email, '@') !== false) {
        $displayName = explode('@', $email)[0];
    } elseif (!empty($_SESSION['crew_name'])) {
        $displayName = $_SESSION['crew_name'];
    } else {
        $displayName = strtolower($role);
    }

    $displayName = trim($displayName) !== '' ? $displayName : 'User';
    $initials = strtoupper(substr($displayName, 0, 2));
    $initials = $initials !== '' ? $initials : 'US';

    $loginAt = $_SESSION['login_at'] ?? date('Y-m-d H:i:s');
    if (!isset($_SESSION['login_at'])) {
        $_SESSION['login_at'] = $loginAt;
    }

} catch (Exception $e) {
    if (defined('DB_DEBUG') && DB_DEBUG) {
        die('Error: ' . $e->getMessage());
    }
    die('Unable to load settings page.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security & Verification</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/content.css?v=<?php echo time(); ?>">
    <style>

        @font-face {
    font-family: 'Poppins';
    src: url('../assets/fonts/Poppins-Bold.ttf') format('truetype');
    font-weight: bold;
    font-style: normal;
}

@font-face {
    font-family: 'Poppins';
    src: url('../assets/fonts/Poppins-SemiBold.ttf') format('truetype');
    font-weight: 600;
    font-style: normal;
}
        .settings-title {  font-size: 1.5rem;
    color: var(--primary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
    font-family: 'Poppins', sans-serif;
    font-weight: bold;}

        .settings-subtitle { margin: 0 0 24px; color: var(--warn); font-size: 16px; font-weight: 600;, font family: 'Poppins', sans-serif; }

        .settings-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card-white {
            background: #fff;
            border: 1px solid #dce9ff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 8px 20px rgba(8, 31, 92, 0.04);
        }

        .section-heading {
            margin: 0 0 18px;
            font-size: 18px;
            color: var(--primary);
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
        }

        .profile-row { display: grid; grid-template-columns: 100px 1fr; gap: 22px; align-items: center; }
        .avatar {
            width: 100px; height: 100px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: var(--warn); color: #fff; font-size: 24px; font-weight: 700;
            border: 6px solid #edf4ff;
        }
        .role-badge {
            display: inline-block; margin-top: 14px; padding: 6px 16px;
            border-radius: 999px; font-size: 12px; font-weight: 800;
            color: var(--primary); background: #e9f1ff;
        }

        .profile-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .field { border: 1px solid #d9e8ff; border-radius: 16px; padding: 12px 12px; }
        .field-label { font-size: 12px; color: #6b84ac; font-weight: 700; display: block; margin-bottom: 4px; }
        .field-value { font-size: 14px; font-weight: 700; color: black; }

        .health-card {
            border-radius: 24px;
            background: #031a54;
            color: #fff;
            padding: 26px;
            text-align: center;
            min-height: 260px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .health-title { font-size: 16px; letter-spacing: 2px; margin-bottom: 14px; opacity: .95; font-weight: 800; }
        .ring {
            width: 170px; height: 170px; border-radius: 50%;
            margin: 0 auto 14px;
            border: 10px solid #f5b901;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800;
        }
        .health-note { font-size: 17px; opacity: .95; }

        .bottom-grid { margin-top: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .action-item {
            border: 1px solid #d9e8ff; border-radius: 16px; padding: 18px 16px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;
        }
        .action-title { font-size: 16px; font-weight: 800; color: #021b4e; margin: 0; }
        .action-sub { margin: 0; color: #6b84ac; font-size: 14px; }
        .action-link { color: var(--warn); font-weight: 800; text-decoration: none; font-size: 14px; border: none; background: transparent; cursor: pointer; }

        .activity-item { border: 1px solid #d9e8ff; border-radius: 16px; padding: 16px; margin-bottom: 12px; }
        .activity-title { margin: 0; font-size: 16px; color: #031f5b; font-weight: 800; }
        .activity-meta { margin-top: 5px; color: #6b84ac; font-size: 14px; }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 10, 29, 0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.show { display: flex; }

        .password-modal {
            width: 100%;
            max-width: 640px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            background: #031a54;
            color: #fff;
            padding: 20px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
        }

        .modal-close {
            border: none;
            background: transparent;
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            cursor: pointer;
        }

        .modal-body {
            padding: 28px;
        }

        .pwd-field {
            margin-bottom: 16px;
        }

        .pwd-label {
            display: block;
            font-size: 16px;
            font-weight: 800;
            color: #021b4e;
            margin-bottom: 8px;
        }

        .pwd-input-wrap {
            position: relative;
        }

        .pwd-input {
            width: 100%;
            border: 1px solid #d9e8ff;
            border-radius: 14px;
            height: 58px;
            padding: 0 52px 0 16px;
            font-size: 14px;
            font-weight: 600;
            color: #031f5b;
            outline: none;
        }

        .pwd-input:focus {
            border-color: #0a4be0;
            box-shadow: 0 0 0 3px rgba(10, 75, 224, 0.08);
        }

        .toggle-eye {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #7d8fb1;
            cursor: pointer;
            font-size: 20px;
            width: 36px;
            height: 36px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .btn-save-password {
            min-width: 180px;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: #041c5a;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-save-password:hover {
            background: #05246f;
        }

        .flash-msg {
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-weight: 700;
        }

        .flash-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .flash-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 1200px) {
            .settings-grid, .bottom-grid { grid-template-columns: 1fr; }
            .profile-fields { grid-template-columns: 1fr; }
            .profile-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .modal-title { font-size: 30px; }
            .modal-body { padding: 18px; }
            .btn-save-password { width: 100%; min-width: 0; }
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
        <div class="main__content settings-wrap">
            <h1 class="settings-title">Security & Verification</h1>
            <p class="settings-subtitle">Manage your account access and security settings.</p>

            <?php if (!empty($_SESSION['success_message'])): ?>
                <div class="flash-msg flash-success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error_message'])): ?>
                <div class="flash-msg flash-error">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <section class="card-white">
                    <h2 class="section-heading">Account Profile</h2>
                    <div class="profile-row">
                        <div>
                            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
                            <span class="role-badge"><?php echo htmlspecialchars($role); ?></span>
                        </div>
                        <div class="profile-fields">
                            <div class="field">
                                <span class="field-label">DISPLAY NAME</span>
                                <div class="field-value"><?php echo htmlspecialchars($displayName); ?></div>
                            </div>
                            <div class="field">
                                <span class="field-label">EMAIL ADDRESS</span>
                                <div class="field-value"><?php echo htmlspecialchars($email ?: 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- <section class="health-card">
                    <div class="health-title">SECURITY HEALTH</div>
                    <div class="ring">50%</div>
                    <div class="health-note">Enable 2FA to improve your score.</div>
                </section> -->
                                <section class="card-white">
                    <h2 class="section-heading">Activity Log</h2>
                    <div class="activity-item">
                        <p class="activity-title">User Login</p>
                        <div class="activity-meta"><?php echo htmlspecialchars(date('m/d/Y, g:i:s A', strtotime($loginAt))); ?></div>
                        <div class="activity-meta">127.0.0.1 · <?php echo htmlspecialchars($role); ?></div>
                    </div>
                </section>
            </div>

            <div class="bottom-grid">
                <section class="card-white">
                    <h2 class="section-heading">Login & Recovery</h2>

                    <div class="action-item">
                        <div>
                            <p class="action-title">Password</p>
                            <p class="action-sub">Managed via Auth Provider</p>
                        </div>
                        <button type="button" class="action-link" id="openPasswordModal">Update</button>
                    </div>

                    <!-- <div class="action-item">
                        <div>
                            <p class="action-title">Two-Factor Authentication</p>
                            <p class="action-sub">Not Enabled</p>
                        </div>
                        <a href="#" class="action-link">Setup</a>
                    </div> -->
                </section>

            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="passwordModal">
    <div class="password-modal" role="dialog" aria-modal="true" aria-labelledby="pwdModalTitle">
        <div class="modal-header">
            <h3 class="modal-title" id="pwdModalTitle">Update Password</h3>
            <button type="button" class="modal-close" id="closePasswordModal">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="update_password.php" id="passwordForm">
                <input type="hidden" name="skip_current_password" value="1">

                <div class="pwd-field">
                    <label class="pwd-label" for="new_password">New Password</label>
                    <div class="pwd-input-wrap">
                        <input type="password" id="new_password" name="new_password" class="pwd-input" minlength="8" required>
                        <button type="button" class="toggle-eye" data-target="new_password">👁</button>
                    </div>
                </div>

                <div class="pwd-field">
                    <label class="pwd-label" for="confirm_password">Confirm New Password</label>
                    <div class="pwd-input-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" class="pwd-input" minlength="8" required>
                        <button type="button" class="toggle-eye" data-target="confirm_password">👁</button>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-save-password">SAVE PASSWORD</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
(function () {
    const modal = document.getElementById('passwordModal');
    const openBtn = document.getElementById('openPasswordModal');
    const closeBtn = document.getElementById('closePasswordModal');
    const form = document.getElementById('passwordForm');

    function openModal() { modal.classList.add('show'); }
    function closeModal() { modal.classList.remove('show'); }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    document.querySelectorAll('.toggle-eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            const newPwd = document.getElementById('new_password').value;
            const confirmPwd = document.getElementById('confirm_password').value;
            if (newPwd !== confirmPwd) {
                e.preventDefault();
                alert('New password and confirm password do not match.');
            }
        });
    }
})();
</script>
</body>
</html>
