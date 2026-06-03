<?php
session_start();
$_SESSION = array();

session_destroy();
header('Location: ugrad.html');
exit;