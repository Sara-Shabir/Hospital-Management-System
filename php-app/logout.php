<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    log_action($_SESSION['user_id'], 'LOGOUT', '');
}

session_unset();
session_destroy();
header('Location: /login.php');
exit;
