<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav>
    <a href="index.php">Home</a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</span>
        <a href="logout.php">Log out</a>
    <?php else: ?>
        <a href="login.php">Log in</a>
        <a href="register.php">Create account</a>
    <?php endif; ?>
</nav>