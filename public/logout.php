<?php
session_start();
session_unset(); 
session_destroy(); 

header('Location: /remesas/public/index.php');
exit();
?>