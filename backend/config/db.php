<?php
// backend/config/db.php

// Railway/Vercel bağlantı mimarisi (URL Parsing + IP Proxying)
$mysqlUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($mysqlUrl) {
    $dbConfig = parse_url($mysqlUrl);
    $host = $dbConfig['host'] === 'mysql.railway.internal' ? '66.33.22.247' : $dbConfig['host'];
    $port = $dbConfig['port'] ?: '17071';
    $user = $dbConfig['user'];
    $pass = $dbConfig['pass'];
    $db   = ltrim($dbConfig['path'], '/');
} else {
    $host = getenv('DB_HOST') ?: '66.33.22.247'; 
    $port = getenv('DB_PORT') ?: '17071';
    $db   = getenv('DB_NAME') ?: 'railway';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: 'osJlNEmbckLWoODPnpbPdiWZxWiTXneJ';
}

$charset = 'utf8mb4';
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