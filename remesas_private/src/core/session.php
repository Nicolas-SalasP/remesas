<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /remesas/public/login.php');
    exit();
}

?>