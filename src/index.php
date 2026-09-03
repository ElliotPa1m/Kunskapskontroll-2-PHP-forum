<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/db.php';
?>
<?php

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT g.UniqueID, g.name
        FROM Groups_table g
        JOIN Membership m ON g.UniqueID = m.group_id
        WHERE m.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $my_groups = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT UniqueID, name
        FROM Groups_table
        WHERE UniqueID NOT IN (
            SELECT group_id FROM Membership WHERE user_id = ?
        )
    ");
    $stmt->execute([$user_id]);
    $other_groups = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT UniqueID, name FROM Groups_table");
    $other_groups = $stmt->fetchAll();
    $my_groups = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_name']) && isset($_SESSION['user_id'])) {
    $group_name = $_POST['group_name'];

    if (!empty($group_name)) {
        $stmt = $pdo->prepare("INSERT INTO Groups_table (name, created_by) VALUES (?, ?)");
        $stmt->execute([$group_name, $_SESSION['user_id']]);
        $new_group_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO Membership (user_id, group_id, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$_SESSION['user_id'], $new_group_id]);
    }

    header("Location: /");
    exit;
}

require 'includes/header.php';
?>


<h1>Welcome to the forum</h1>

<?php if (isset($_SESSION['user_id'])): ?>
    <h2>My groups</h2>
    <?php if (empty($my_groups)): ?>
        <p>You're not a member of any group yet. </p>
    <?php else: ?>
        <ul>
            <?php foreach ($my_groups as $group): ?>
                <li>
                    <a href="/group/?id=<?= $group['UniqueID'] ?>">
                        <?= htmlspecialchars($group['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<h2>Other groups</h2>
<?php if (empty($other_groups)): ?>
    <p>No other groups right now. </p>
<?php else: ?>
    <ul>
        <?php foreach ($other_groups as $group): ?>
            <li>
                <a href="/group/?id=<?= $group['UniqueID'] ?>">
                    <?= htmlspecialchars($group['name']) ?>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="/group/apply.php" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?= $group['UniqueID'] ?>">
                        <button type="submit">Apply to join</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])): ?>
    <h2>Create a new group</h2>

    <form method="POST" action="">
        <label for="group_name">Group name:</label>
        <input type="text" id="group_name" name="group_name" required>
        <button type="submit"> Create group</button>
    </form>
<?php endif; ?>