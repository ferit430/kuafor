<?php
// backend/api/reviews.php
require_once '../config/db.php';
require_once '../helpers/response_helper.php';
require_once '../middleware/auth_token.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $salon_id = $_GET['salon_id'] ?? null;
    if (!$salon_id) sendError(400, "salon_id gerekli");

    $stmt = $pdo->prepare("SELECT r.*, u.name as customer_name 
                           FROM reviews r JOIN users u ON r.customer_id = u.id 
                           WHERE r.salon_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$salon_id]);
    sendResponse(200, "Yorumlar listelendi", $stmt->fetchAll());
}

if ($method === 'POST') {
    $userData = authenticate();
    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->appointment_id) || empty($data->rating)) {
        sendError(400, "Eksik alanlar var");
    }

    // Randevunun kullanıcıya ait olduğunu ve tamamlandığını doğrula
    $stmt = $pdo->prepare("SELECT id, salon_id FROM appointments WHERE id = ? AND customer_id = ? AND status = 'completed'");
    $stmt->execute([$data->appointment_id, $userData['id']]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        sendError(403, "Yalnızca tamamlanmış randevular için yorum yapabilirsiniz");
    }

    $stmt = $pdo->prepare("INSERT INTO reviews (appointment_id, customer_id, salon_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data->appointment_id, $userData['id'], $appointment['salon_id'], $data->rating, $data->comment ?? null]);

    sendResponse(201, "Yorumunuz kaydedildi");
}
?>
