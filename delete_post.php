<?php
require_once 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(SITE_URL . '/dashboard.php');

$post_id = intval($_POST['post_id'] ?? 0);
$uid = $_SESSION['user_id'];

if (!$post_id) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
    redirect(SITE_URL . '/dashboard.php');
}

if (is_admin()) {
    $st = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $st->bind_param('i', $post_id);
} else {
    $st = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $st->bind_param('ii', $post_id, $uid);
}

if ($st->execute() && $st->affected_rows > 0) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Essay deleted successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Could not delete essay or permission denied.'];
}
$st->close();
redirect(SITE_URL . '/dashboard.php');
