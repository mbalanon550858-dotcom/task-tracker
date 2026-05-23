<?php
// ============================================================
// includes/db.php — Database Connection (Railway + Local)
// Automatically switches between Railway and XAMPP
// ============================================================

$host = getenv('MYSQLHOST')     ?: 'localhost';
$user = getenv('MYSQLUSER')     ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: 'task_tracker_db';
$port = getenv('MYSQLPORT')     ?: 3306;

$conn = mysqli_connect($host, $user, $pass, $name, (int)$port);

if (!$conn) {
    die('<div style="font-family:monospace;color:red;padding:20px;">
         ❌ Database connection failed: ' . mysqli_connect_error() . '</div>');
}

mysqli_set_charset($conn, 'utf8mb4');
