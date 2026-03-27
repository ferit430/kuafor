<?php
// tmp/fix_salon_hours.php
require 'backend/config/db.php';
$sql = "UPDATE salons SET opening_time = '09:00:00', closing_time = '22:00:00' WHERE (opening_time IS NULL OR opening_time = '' OR opening_time = '00:00:00')";
$count = $pdo->exec($sql);
echo "Updated $count salons.\n";

$sql = "SELECT id, name, opening_time, closing_time FROM salons";
$stmt = $pdo->query($sql);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
