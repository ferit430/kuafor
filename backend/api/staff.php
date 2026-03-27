<?php
// backend/api/staff.php
require_once '../config/db.php';
require_once '../helpers/response_helper.php';
require_once '../middleware/auth_token.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $salon_id = $_GET['salon_id'] ?? null;
    if (!$salon_id) sendError(400, "salon_id gerekli");

    $stmt = $pdo->prepare("SELECT * FROM staff WHERE salon_id = ?");
    $stmt->execute([$salon_id]);
    sendResponse(200, "Personel listelendi", $stmt->fetchAll());
}

if ($method === 'POST') {
    $userData = authenticate();
    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->salon_id) || empty($data->name)) {
        sendError(400, "Eksik alanlar var");
    }

    // Sahiplik kontrolü
    $stmt = $pdo->prepare("SELECT id FROM salons WHERE id = ? AND owner_id = ?");
    $stmt->execute([$data->salon_id, $userData['id']]);
    if (!$stmt->fetch() && $userData['role'] !== 'admin') {
        sendError(403, "Erişim reddedildi");
    }

    $stmt = $pdo->prepare("INSERT INTO staff (salon_id, name, expertise) VALUES (?, ?, ?)");
    $stmt->execute([$data->salon_id, $data->name, $data->expertise ?? null]);

    sendResponse(201, "Personel eklendi", ["id" => $pdo->lastInsertId()]);
}

if ($method === 'DELETE') {
    $userData = authenticate();
    $id = $_GET['id'] ?? null;
    if (!$id) sendError(400, "id gerekli");

    $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->execute([$id]);
    sendResponse(200, "Personel silindi");
}
?>
