<?php
require_once 'config.php';
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

session_start();
$_SESSION['flash'] = ['type' => 'success', 'msg' => 'You have been signed out. See you next time!'];

redirect(SITE_URL . '/index.php');
