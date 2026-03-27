<?php
require 'backend/config/db.php';
$stmt = $pdo->query('SELECT id, name, opening_time, closing_time FROM salons');
$salons = $stmt->fetchAll();
echo "--- SALONS ---\n";
print_r($salons);

$stmt = $pdo->query('SELECT id, salon_id, name, duration_minutes FROM services');
$services = $stmt->fetchAll();
echo "\n--- SERVICES ---\n";
print_r($services);
?>
