<?php
// backend/config/db.php

// Aiven.io Veritabanı Yapılandırması (Environment Variables Üzerinden)
$host = getenv('DB_HOST') ?: 'berber-berber.e.aivencloud.com';
$port = getenv('DB_PORT') ?: '16713';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS'); // Güvenlik sebebiyle şifre sadece Vercel panelinden okunacak
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
    PDO::ATTR_TIMEOUT            => 15,
    // Aiven SSL Gereksinimi
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Aiven Veritabanı Hatası: " . $e->getMessage());
}
?>