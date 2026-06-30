<?php
ob_start();
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

$token = preg_replace('/[^a-f0-9]/i', '', (string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$success = '';

if (strlen($token) !== 64) {
    $error = 'This reset link is invalid.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    verifyCsrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (resetPasswordWithToken($token, $password)) {
        $success = 'Password updated. You can now sign in.';
    } else {
        $error = 'This reset link has expired or has already been used.';
    }
}
?>

<div class="auth-card-title">Create New Password</div>
<div class="auth-card-sub">Use at least eight characters</div>

<?php if ($error): ?><div class="auth-alert auth-alert-error"><i class="bi bi-exclamation-circle" style="margin-right:6px;"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="auth-alert auth-alert-success"><i class="bi bi-check-circle" style="margin-right:6px;"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (!$success && strlen($token) === 64): ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="auth-field"><input type="password" name="password" placeholder="New password" class="auth-input" required autocomplete="new-password"></div>
    <div class="auth-field"><input type="password" name="confirm_password" placeholder="Confirm new password" class="auth-input" required autocomplete="new-password"></div>
    <button type="submit" class="auth-btn">Update Password</button>
</form>
<?php endif; ?>

<div class="auth-footer"><a href="login.php">Back to sign in</a></div>

<?php
$auth_content = ob_get_clean();
$pageTitle = 'Reset Password - StudySync';
require 'auth-layout.php';
?>
