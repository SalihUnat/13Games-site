<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

if(!isset($_SESSION['user'])) {
    $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
?>