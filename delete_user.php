<?php
require_once 'config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid = intval($_POST['user_id'] ?? 0);
    if($uid && $uid !== $_SESSION['user_id']){
        $st = $conn->prepare("DELETE FROM users WHERE id=?");
        $st->bind_param('i', $uid);
        if($st->execute()){
            $_SESSION['flash'] = ['type'=>'success','msg'=>'User and their essays have been removed.'];
        } else {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'Failed to remove user.'];
        }
        $st->close();
    } else {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Invalid action. You cannot remove yourself.'];
    }
}
redirect(SITE_URL.'/dashboard.php');
