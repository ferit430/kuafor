<?php
// backend/config/db.php

// Railway/Vercel bağlantısı için en güvenli yöntem: MYSQL_URL veya bağımsız değişkenler
$mysqlUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($mysqlUrl) {
    // URL formatı: mysql://user:pass@host:port/dbname
    $dbConfig = parse_url($mysqlUrl);
    $host = $dbConfig['host'] === 'mysql.railway.internal' ? '66.33.22.247' : $dbConfig['host'];
    $port = $dbConfig['port'] ?: '17071';
    $user = $dbConfig['user'];
    $pass = $dbConfig['pass'];
    $db   = ltrim($dbConfig['path'], '/');
} else {
    // Manuel Değişkenler veya Hardcoded Fallback
    $host = getenv('DB_HOST') ?: '66.33.22.247'; 
    $port = getenv('DB_PORT') ?: '17071';
    $db   = getenv('DB_NAME') ?: 'railway';
    $user = getenv('DB_USER') ?: 'root';
    // Kullanıcının son paylaştığı kesinleşmiş şifre (osJlN...)
    $pass = getenv('DB_PASS') ?: 'osJlNEmbckLWoODPnpbPdiWZxWiTXneJ';
}

$charset = 'utf8mb4';

// Host ve port'u tek dize olarak vererek PDO'nun socket hatasını aşmasını sağlıyoruz
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
    PDO::ATTR_TIMEOUT            => 15
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Kritik Veritabanı Hatası: " . $e->getMessage() . " (Hedef: $host:$port)");
}
?>