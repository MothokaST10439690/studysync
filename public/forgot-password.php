<?php
ob_start();
require_once __DIR__ . '/config/db_helpers.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$submitted = false;
$developmentResetUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim((string)($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $token = createPasswordResetToken($email);
        if ($token) {
            $configuredUrl = rtrim((string)getenv('APP_URL'), '/');
            if ($configuredUrl === '') {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $configuredUrl = $scheme . '://' . $host;
            }
            $resetUrl = $configuredUrl . '/reset-password.php?token=' . rawurlencode($token);
            $from = (string)getenv('MAIL_FROM');
            if ($from !== '') {
                $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8";
                @mail($email, 'Reset your StudySync password', "Use this link within 60 minutes to reset your password:\n\n{$resetUrl}\n", $headers);
            }
            if (strtolower((string)getenv('APP_ENV')) !== 'production') {
                $developmentResetUrl = $resetUrl;
            }
        }
    }
    $submitted = true;
}
?>

<div class="auth-card-title">Forgot Password</div>
<div class="auth-card-sub">Request a secure reset link</div>

<?php if ($submitted): ?>
    <div class="auth-alert auth-alert-success"><i class="bi bi-envelope-check" style="margin-right:6px;"></i>If an active account matches that email, a reset link has been created.</div>
    <?php if ($developmentResetUrl): ?>
        <div class="auth-alert" style="background:#fdf6ec;border:1px solid #f5d9b0;color:#8a4d0a;word-break:break-all;">
            Development preview: <a href="<?= htmlspecialchars($developmentResetUrl) ?>" style="color:inherit;font-weight:600;">open reset link</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
    <div class="auth-field"><input type="email" name="email" placeholder="Email address" class="auth-input" required autocomplete="email"></div>
    <button type="submit" class="auth-btn">Send Reset Link</button>
</form>

<div class="auth-footer">Remembered it? <a href="login.php">Back to sign in</a></div>

<?php
$auth_content = ob_get_clean();
$pageTitle = 'Forgot Password - StudySync';
require 'auth-layout.php';
?>
