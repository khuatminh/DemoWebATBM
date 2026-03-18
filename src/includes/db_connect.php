<?php
// ============================================
// VulnShop - Database Connection
// ⚠️ VULNERABLE VERSION - For demo purposes only
// ============================================

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'vulnuser';
$db_pass = getenv('DB_PASS') ?: 'vulnpass123';
$db_name = getenv('DB_NAME') ?: 'vulnshop';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

session_start();
?>
