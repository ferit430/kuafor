<?php
// backend/api/salon_new_appointments.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response_helper.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$salon_id = $_GET['salon_id'] ?? null;
$last_id = $_GET['last_id'] ?? 0;

if (!$salon_id) {
    echo json_encode(["status" => 400, "message" => "salon_id gerekli"]);
    exit;
}

// Son randevu ID'sini bul
$stmt = $pdo->prepare("SELECT id FROM appointments WHERE salon_id = ? AND id > ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$salon_id, $last_id]);
$new = $stmt->fetch();

echo json_encode([
    "status" => 200,
    "new_booking" => $new ? true : false,
    "latest_id" => $new ? $new['id'] : $last_id
]);
?>
