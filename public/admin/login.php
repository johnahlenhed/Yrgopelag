<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../src/adminHandler.php';
    handleAdminUpdate($_POST);

    $password = $_POST['password'] ?? '';

    if (password_verify($password, $_ENV['ADMIN_PASSWORD_HASH'] ?? '')) {
        $_SESSION['is_admin'] = true;
        header('Location: /admin/dashboard.php');
        exit();
    } else {
        $error = 'Invalid password.';
    }
}

require __DIR__ . '/../../includes/header.php'; ?>

<h1>Admin Login</h1>

<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST">
    <label>
        Password:
        <input type="password" name="password" required>
    </label>
    <button type="submit">Login</button>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>