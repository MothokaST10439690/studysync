<?php
ob_start();
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';


if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password = $_POST['password']              ?? '';
    $confirm  = $_POST['confirm_password']      ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered.';
        } else {
            $role = 'student';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hash, $role])) {
                $next = safeLocalRedirect($_POST['next'] ?? $_GET['next'] ?? null, 'dashboard.php');
                header('Location: login.php?registered=1&next=' . rawurlencode($next));
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>

<div class="auth-card-title">Create Account</div>
<div class="auth-card-sub">Join StudySync today</div>

<?php if ($error): ?>
    <div class="auth-alert auth-alert-error">
        <i class="bi bi-exclamation-circle" style="margin-right:6px;"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="auth-alert auth-alert-success">
        <i class="bi bi-check-circle" style="margin-right:6px;"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
    <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? '') ?>">

    <div class="auth-field">
        <input type="text" name="name" placeholder="Full name"
               class="auth-input" required
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>

    <div class="auth-field">
        <input type="email" name="email" placeholder="Email address"
               class="auth-input" required autocomplete="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="auth-field">
        <input type="password" name="password" placeholder="Password (min 8 chars)"
               class="auth-input" required>
    </div>

    <div class="auth-field">
        <input type="password" name="confirm_password" placeholder="Confirm password"
               class="auth-input" required>
    </div>

    <button type="submit" class="auth-btn">Create Account</button>

</form>

<div class="auth-footer">
    Already have an account? <a href="login.php">Sign In</a>
</div>

<?php
$auth_content = ob_get_clean();
$pageTitle = 'Register — StudySync';
require 'auth-layout.php';
?>
