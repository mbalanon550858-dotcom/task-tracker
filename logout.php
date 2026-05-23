<?php
// ============================================================
// logout.php — Destroy session and redirect
// ============================================================
require_once 'auth.php';
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit();
