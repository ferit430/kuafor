<?php
// backend/config/db.php

$host = getenv('DB_HOST') ?: 'gondola.proxy.rlwy.net';
$port = getenv('DB_PORT') ?: '17071';
$db   = getenv('DB_NAME') ?: 'railway';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'osJI1NEmbcKLW0DPnpbPd1WZxw1TXnej';
$charset = 'utf8mb4';

// Vercel/Serverless ortamlarda port ve host ayrımı kritik
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Hata detayını canlıda görebilmek için (Geçici olarak)
     die("Bağlantı Hatası: " . $e->getMessage() . " (Host: $host)");
}
?>
