<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="/style.css">

<header class="site-header">
    <a href="/" class="logo">HiveMind</a>
    <div class="header-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span><?= htmlspecialchars($_SESSION['first_name']) ?></span>
            <a href="/logout/">Log out</a>
        <?php else: ?>
            <a href="/login/">Log in</a>
            <a href="/register/">Create account</a>
        <?php endif; ?>
    </div>
</header>