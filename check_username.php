<?php
require_once 'config.php';
header('Content-Type: application/json');
$un = trim($_GET['username'] ?? '');
if(!$un || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $un)){
    echo json_encode(['valid'=>false,'available'=>false,'msg'=>'Must be 3–20 letters, numbers, or _']);
    exit;
}
$st = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$st->bind_param('s', $un); $st->execute();
$taken = $st->get_result()->num_rows > 0; $st->close();
echo json_encode(['valid'=>true,'available'=>!$taken,'msg'=>$taken?'Username is already taken.':'Username is available!']);
