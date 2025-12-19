<?php

declare(strict_types=1);

session_start();

// Force logout & session reset when visiting login page
session_unset();        // Clear session variables
session_destroy();     // Destroy session data
session_regenerate_id(true); // Prevent session fixation
session_start();       // Start a fresh session

require_once __DIR__ . '/../../config/config.php';

if (!empty($_SESSION['is_admin'])) {
    header('Location: /public/admin/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';

    if ($password === ($_ENV['ADMIN_PASSWORD'] ?? '')) {
        $_SESSION['is_admin'] = true;
        header('Location: /public/admin/dashboard.php');
        exit();
    }

        $error = 'Invalid password.';
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