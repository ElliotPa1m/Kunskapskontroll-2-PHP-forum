<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav>
    <a href="/">Home</a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <span><?= htmlspecialchars($_SESSION['first_name']) ?></span>
        <a href="/logout/">Log out</a>
    <?php else: ?>
        <a href="/login/">Log in</a>
        <a href="/register/">Create account</a>
    <?php endif; ?>
</nav>