<?php
// backend/api/notifications.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response_helper.php';
require_once __DIR__ . '/../middleware/auth_token.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PATCH");

$method = $_SERVER['REQUEST_METHOD'];
$userData = authenticate();

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$userData['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Eğer bildirim yoksa hoşgeldin bildirimi ekleyelim (Örnek)
    if (empty($notifications)) {
        $notifications = [[
            "id" => 0,
            "title" => "Hoş Geldiniz!",
            "message" => "Berberim uygulamasına hoş geldiniz. Randevularınızı buradan takip edebilirsiniz.",
            "type" => "info",
            "is_read" => 0,
            "created_at" => date('Y-m-d H:i:s')
        ]];
    }
    
    sendResponse(200, "Bildirimler listelendi", $notifications);
}

if ($method === 'PATCH') {
    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->id)) sendError(400, "id gerekli");
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$data->id, $userData['id']]);
    sendResponse(200, "Bildirim okundu olarak işaretlendi");
}
?>
