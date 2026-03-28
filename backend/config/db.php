<?php
// backend/config/db.php

// Turkticaret.net veya Vercel Yapılandırması (Environment Variables Önceliklidir)
$host = getenv('DB_HOST') ?: 'ftp.kuaforrandevu.store';
$port = getenv('DB_PORT') ?: '3306'; 
$db   = getenv('DB_NAME') ?: 'kua146randstore_kuaforrandevu';
$user = getenv('DB_USER') ?: 'kua146randstore_admin';
$pass = getenv('DB_PASS') ?: 'Ferit.28@';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
    PDO::ATTR_TIMEOUT            => 5,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Hata durumunda (Örneğin hostingde host localhost olmalıysa) alternatif deneme
     if ($host !== 'localhost') {
         try {
             $dsn_local = "mysql:host=localhost;port=3306;dbname=$db;charset=$charset";
             $pdo = new PDO($dsn_local, $user, $pass, $options);
         } catch (\PDOException $e2) {
             die("Veritabanı Bağlantı Hatası: " . $e2->getMessage());
         }
     } else {
         die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
     }
}
?>