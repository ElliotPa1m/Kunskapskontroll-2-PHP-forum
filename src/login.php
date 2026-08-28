<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Both fields are required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT UniqueID, first_name, password_hash FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = "Incorrect e-mail or password.";
        }
    }

    if (empty($errors)) {
        $_SESSION['user_id'] = $user['UniqueID'];
        $_SESSION['first_name'] = $user['first_name'];

        header("Location: index.php");
        exit;
    }
}

require 'includes/header.php';
?>

<h1>Log in</h1>

<?php if (!empty($errors)): ?>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST" action="login.php">
    <label for="email">E-mail:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Log in</button>
</form>