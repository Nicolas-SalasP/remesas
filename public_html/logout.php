<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';

session_unset(); 
session_destroy(); 

header('Location: ' . BASE_URL . '/');
exit();
?>