<?php
require '../includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords does not match.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT UniqueID FROM Users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        }
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO Users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $password_hash]);

        header("Location: ../login/");
        exit;
    }
}
?>

<h1>Create account</h1>

<?php if (!empty($errors)): ?>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<a href="../">Home</a>
<a href="../login/">Already have an account? Log in!</a>

<form method="POST" action="">
    <label for="first_name">First name:</label>
    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name ?? '') ?>" required>

    <label for="last_name">Last name:</label>
    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name ?? '') ?>" required>

    <label for="email">E-mail:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <label for="confirm_password">Confirm password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <button type="submit">Create account</button>
</form>