# SMTP + Password Setup Stabilization TODO

- [x] Install PHPMailer via Composer using local Composer + XAMPP PHP PATH
- [x] Add SMTP config helper (`config/smtp.php`)
- [x] Update `adminside/staff_save.php` to send setup link via PHPMailer with native mail fallback
- [x] Update `adminside/staff_add.php` success/warning UI messaging for email status/manual link
- [x] Fix role mapping during staff creation (`position_name` -> `users.role`)
- [x] Update `adminside/login.php` to allow mapped staff roles
- [x] Add session-confusion guard in `adminside/set_password.php` (clear existing logged-in session)
- [x] Fix transaction order in `adminside/staff_save.php` so token insert is inside same transaction before commit
- [x] Run PHP syntax checks on changed files
- [ ] Apply SMTP fallback credentials for environments without env vars (critical-path email sending fix)
- [ ] Re-test end-to-end: create staff -> receive link -> set password -> login with new account
