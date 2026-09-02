<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../includes/db.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    die("No invite token specified.");
}

$stmt = $pdo->prepare("SELECT UniqueID, group_id, expires_at, used_at FROM InviteLinks WHERE token = ?");
$stmt->execute([$token]);
$invite = $stmt->fetch();

if (!$invite) {
    die("Invalid invite link.");
}

if ($invite['used_at'] !== null) {
    die("The invite has been used already.");
}

if (strtotime($invite['expires_at']) < time()) {
    die("The invite link has expired.");
}

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM Membership WHERE user_id = ? AND group_id = ?");
    $stmt->execute([$_SESSION['user_id'], $invite['group_id']]);
    $already_member = $stmt->fetch();

    if (!$already_member) {
        $stmt = $pdo->prepare("INSERT INTO Membership (user_id, group_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$_SESSION['user_id'], $invite['group_id']]);
    }

    $stmt = $pdo->prepare("UPDATE InviteLinks SET used_at = NOW() WHERE UniqueID = ?");
    $stmt->execute([$invite['UniqueID']]);

    header("Location: /group/?id=" . $invite['group_id']);
    exit;
}

$stmt = $pdo->prepare("SELECT name FROM Groups_table WHERE UniqueID = ?");
$stmt->execute([$invite['group_id']]);
$invite_group = $stmt->fetch();
?>

<h1>Join <?= htmlspecialchars($invite_group['name'] ?? 'this group') ?></h1>

<p>You need an account to use this invite link.</p>

<a href="/register/?token=<?= urlencode($token) ?>">Create an account</a>
<a href="/login/?token=<?= urlencode($token) ?>">Log in</a>