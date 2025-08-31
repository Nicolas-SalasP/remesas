<?php
// /remesas/src/config.php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
$base_path = preg_replace('/\/public\/.*$/', '/public', $base_path); // Limpia la ruta para que siempre termine en /public

define('BASE_URL', rtrim($protocol . '://' . $host . $base_path, '/'));
?>