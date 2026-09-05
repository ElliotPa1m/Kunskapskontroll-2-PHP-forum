<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../includes/db.php';

$discussion_id = $_GET['id'] ?? null;

if (!$discussion_id) {
    die("No discussion specified.");
}

$stmt = $pdo->prepare("SELECT UniqueID, group_id, topic FROM Discussions WHERE UniqueID = ?");
$stmt->execute([$discussion_id]);
$discussion = $stmt->fetch();

if (!$discussion) {
    die("Discussion not found");
}

if (!isset($_SESSION['user_id'])) {
    die("You need to be logged in and a member of this group to view this discussion.");
}

$stmt = $pdo->prepare("SELECT role FROM Membership WHERE user_id = ? AND group_id = ?");
$stmt->execute([$_SESSION['user_id'], $discussion['group_id']]);
$membership = $stmt->fetch();

if (!$membership) {
    die("You need to be a member of this group to view this discussion.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];

    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO Posts (discussion_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$discussion_id, $_SESSION['user_id'], $content]);
    }

    header("Location: /discussion/?id=$discussion_id");
    exit;
}

require '../includes/header.php';
?>

<h1><?= htmlspecialchars($discussion['topic']) ?></h1>

<?php
$stmt = $pdo->prepare("
    SELECT p.content, p.created_at, u.first_name, u.last_name
    FROM Posts p
    JOIN Users u ON p.user_id = u.UniqueID
    WHERE p.discussion_id = ?
    ORDER BY p.created_at ASC
");
$stmt->execute([$discussion_id]);
$posts = $stmt->fetchAll();
?>

<div>
    <?php foreach ($posts as $index => $post): ?>
        <?php if ($index === 1): ?>
            <h2>Comments</h2>
        <?php endif; ?>

        <div class="card <?= $index === 0 ? 'original-post' : 'reply' ?>">
            <strong><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></strong>
            <span><?= htmlspecialchars($post['created_at']) ?></span>
            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<h2>Reply</h2>

<form method="POST" action="">
    <textarea name="content" required></textarea>
    <button type="submit">Post reply</button>
</form>