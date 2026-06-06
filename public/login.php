<?php
ob_start();
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT user_id, name, email, password_hash, role, is_active
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'Your account has been deactivated. Contact an administrator.';
        } else {
            session_regenerate_id(true);
            loginUser($user['user_id'], $user['name'], $user['role']);
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>

<div class="auth-card-title">Welcome Back</div>
<div class="auth-card-sub">Sign in to your account</div>

<?php if ($error): ?>
    <div class="auth-alert auth-alert-error">
        <i class="bi bi-exclamation-circle" style="margin-right:6px;"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="POST">

    <div class="auth-field">
        <input type="email" name="email" placeholder="Email address"
               class="auth-input" required autocomplete="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="auth-field auth-pw-wrap">
        <input type="password" name="password" id="password"
               placeholder="Password" class="auth-input" required>
        <button type="button" class="auth-pw-toggle" onclick="togglePassword()">
            <i id="eyeIcon" class="bi bi-eye"></i>
        </button>
    </div>

    <button type="submit" class="auth-btn">Sign In</button>

</form>

<div class="auth-footer">
    Don't have an account? <a href="register.php">Register</a>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

<?php
$auth_content = ob_get_clean();
$pageTitle = 'Login — StudySync';
require 'auth-layout.php';
?>