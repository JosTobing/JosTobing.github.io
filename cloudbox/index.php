<?php
require_once 'config/auth.php';

if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: customer/index.php');
    }
} else {
    header('Location: auth/login.php');
}
exit();
?>