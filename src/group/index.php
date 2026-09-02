<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../includes/db.php';

$group_id = $_GET['id'] ?? null;

if (!$group_id) {
    die("No group specified.");
}

$stmt = $pdo->prepare("SELECT UniqueID, name FROM Groups_table WHERE UniqueID = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

$role = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM Membership WHERE user_id = ? AND group_id = ?");
    $stmt->execute([$_SESSION['user_id'], $group_id]);
    $membership = $stmt->fetch();

    if ($membership) {
        $role = $membership['role'];
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic']) && $role !== null) {
    $topic = $_POST['topic'];
    $content = $_POST['content'];

    if (empty($topic) || empty($content)) {
        $errors[] = "Both topic and message are required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO Discussions (group_id, topic, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$group_id, $topic, $_SESSION['user_id']]);
        $discussion_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO Posts (discussion_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$discussion_id, $_SESSION['user_id'], $content]);

        header("Location: /group/?id=$group_id");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_request' && $role === 'admin') {
    $request_id = $_POST['request_id'];

    $stmt = $pdo->prepare("SELECT user_id FROM JoinRequests WHERE UniqueID = ? AND group_id = ?");
    $stmt->execute([$request_id, $group_id]);
    $request = $stmt->fetch();

    if ($request) {
        $stmt = $pdo->prepare("UPDATE JoinRequests SET status = 'approved' WHERE UniqueID = ?");
        $stmt->execute([$request_id]);

        $stmt = $pdo->prepare("INSERT INTO Membership (user_id, group_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$request['user_id'], $group_id]);
    }

    header("Location: /group/?id=$group_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deny_request' && $role === 'admin') {
    $request_id = $_POST['request_id'];

    $stmt = $pdo->prepare("UPDATE JoinRequests SET status = 'rejected' WHERE UniqueID = ? AND group_id = ?");
    $stmt->execute([$request_id, $group_id]);

    header("Location: /group/?id=$group_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_role' && $role === 'admin') {
    $target_user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    if (in_array($new_role, ['member', 'admin']) && $target_user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE Membership SET role = ? WHERE user_id = ? AND group_id = ?");
        $stmt->execute([$new_role, $target_user_id, $group_id]);
    }

    header("Location: /group/?id=$group_id");
    exit;
}

$new_invite_link = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_invite' && $role === 'admin') {
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("INSERT INTO InviteLinks (group_id, created_by, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$group_id, $_SESSION['user_id'], $token, $expires_at]);

    $new_invite_link = "/join/?token=$token";
}

require '../includes/header.php';
?>

<h1><?= htmlspecialchars($group['name']) ?></h1>

<?php
$stmt = $pdo->prepare("SELECT UniqueID, topic FROM Discussions WHERE group_id = ?");
$stmt->execute([$group_id]);
$discussions = $stmt->fetchAll();
?>

<h2>Discussions</h2>

<?php if (empty($discussions)): ?>
    <p>No discussions yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($discussions as $discussion): ?>
            <li>
                <?php if ($role !== null): ?>
                    <a href="/discussion/?id=<?= $discussion['UniqueID'] ?>">
                        <?= htmlspecialchars($discussion['topic']) ?>
                    </a>
                <?php else: ?>
                    <?= htmlspecialchars($discussion['topic']) ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($role !== null): ?>
    <h2>Start a new discussion</h2>

    <?php if (!empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="topic">Topic:</label>
        <input type="text" id="topic" name="topic" value="<?= htmlspecialchars($topic ?? '') ?>" required>

        <label for="content">Message:</label>
        <textarea id="content" name="content" required><?= htmlspecialchars($content ?? '') ?></textarea>

        <button type="submit"> Start discussion</button>
    </form>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <h2>Pending join requests</h2>

    <?php
    $stmt = $pdo->prepare("
        SELECT jr.UniqueID, u.first_name, u.last_name
        FROM JoinRequests jr
        JOIN Users u ON jr.user_id = u.UniqueID
        WHERE jr.group_id = ? AND jr.status = 'pending'
    ");
    $stmt->execute([$group_id]);
    $pending_requests = $stmt->fetchAll();
    ?>

    <?php if (empty($pending_requests)): ?>
        <p>No pending requests.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($pending_requests as $request): ?>
                <li>
                    <?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?= $request['UniqueID'] ?>">
                        <button type="submit" name="action" value="approve_request">Approve</button>
                        <button type="submit" name="action" value="deny_request">Deny</button>
                    </form>
                </li>    
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <h2>Members</h2>

    <?php
    $stmt = $pdo->prepare("
        SELECT u.UniqueID, u.first_name, u.last_name, m.role
        FROM Membership m
        JOIN Users u ON m.user_id = u.UniqueID
        WHERE m.group_id = ?
    ");
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll();
    ?>

    <ul>
        <?php foreach ($members as $member): ?>
            <li>
                <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                (<?= htmlspecialchars($member['role']) ?>)

                <?php if ($member['UniqueID'] != $_SESSION['user_id']): ?>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" value="<?= $member['UniqueID'] ?>">

                        <?php if ($member['role'] === 'member'): ?>
                            <button type="submit" name="new_role" value="admin">Make admin</button>
                        <?php else: ?>
                            <button type="submit" name="new_role" value="member">Remove admin</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <h2>Invite link</h2>

    <?php if ($new_invite_link): ?>
        <p>New invite link (valid for 24 hours, one-time use):</p>
        <p><code><?= htmlspecialchars($new_invite_link) ?></code></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="action" value="create_invite">
        <button type="submit">Create new invite link</button>
    </form>
<?php endif; ?>