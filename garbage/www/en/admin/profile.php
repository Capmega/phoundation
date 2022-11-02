<?php
require_once(__DIR__.'/../libs/startup.php');

rights_or_access_denied('admin');

$profile      = true;
$_GET['user'] = $_SESSION['user']['username'];
include('user.php');
?>