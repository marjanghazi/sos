<?php
session_start();
$pg = 'ums.headoffice';
if (!isset($_SESSION['admin-login']) || ($_SESSION['username'] != "$pg" && $_SESSION['username'] != 'edoffice' && $_SESSION['username'] != 'admin')) {
    echo "Access Denied. You do not have permission for this page. <a href='../index.php' >Go Back</a>";
    die();
}

    include 'controlpanel.php';

?>