<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

// Already authenticated: skip the form entirely.
if (!empty($_SESSION['is_admin'])) {
    header('Location: /public/admin/dashboard.php');
    exit();
}

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_SECONDS = 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lockedUntil = $_SESSION['login_locked_until'] ?? 0;

    if (time() < $lockedUntil) {
        $error = 'Too many failed attempts. Please try again in a minute.';
    } elseif (!csrfVerify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordHash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '';

        if ($passwordHash !== '' && password_verify($password, $passwordHash)) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['is_admin'] = true;
            session_regenerate_id(true); // Prevent session fixation
            header('Location: /public/admin/dashboard.php');
            exit();
        }

        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOCKOUT_SECONDS;
            $_SESSION['login_attempts'] = 0;
        }

        $error = 'Invalid password.';
    }
}

require __DIR__ . '/../../includes/header.php'; ?>

<h1>Admin Login</h1>

<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST">
    <?php echo csrfField(); ?>
    <label>
        Password:
        <input type="password" name="password" required>
    </label>
    <button type="submit">Login</button>
</form>

<div class="login-notice">
    <h2>Are you an admin on this website?</h2>
    <p>You're probably not. So leave this page alone!</p>
    <p>You might think "then why did you put the link in the navbar?".<br> Well, that's none of your business! Move along and stop making me have imaginary conversations.</p>
    <p>Or just <a href="/public/index.php">book another night.</a></p>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>