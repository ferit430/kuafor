<?php
// backend/config/db.php

// Railway Public IP for gondola.proxy.rlwy.net
$host = getenv('DB_HOST') ?: '66.33.22.247'; 
$port = getenv('DB_PORT') ?: '17071';
$db   = getenv('DB_NAME') ?: 'railway';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'osJI1NEmbcKLW0DPnpbPd1WZxw1TXnej';
$charset = 'utf8mb4';

// Host ve port'u tek dize olarak vererek PDO'nun socket hatasını aşmasını sağlıyoruz
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
    PDO::ATTR_TIMEOUT            => 5 // Bağlantı zaman aşımı
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Hata detayını görmeye devam edelim (Debug için)
     die("Kritik Veritabanı Hatası: " . $e->getMessage() . " (Hedef: $host:$port)");
}
?>
