<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$group_id = $_POST['group_id'] ?? null;

if (!$group_id) {
    header("Location: /");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM Membership WHERE user_id = ? AND group_id = ?");
$stmt->execute([$_SESSION['user_id'], $group_id]);
$already_member = $stmt->fetch();

if (!$already_member) {
    $stmt = $pdo->prepare("SELECT UniqueID FROM JoinRequests WHERE user_id = ? AND group_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id'], $group_id]);
    $already_requested = $stmt->fetch();

    if (!$already_requested) {
        $stmt = $pdo->prepare("INSERT INTO JoinRequests (user_id, group_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $group_id]);
    }
}

header("Location: /");
exit;