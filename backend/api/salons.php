<?php
// backend/api/salons.php
require_once '../config/db.php';
require_once '../helpers/response_helper.php';
require_once '../middleware/auth_token.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Salonları listele (isteğe bağlı şehre göre filtrele)
    $city = $_GET['city'] ?? null;
    $query = "SELECT * FROM salons WHERE status = 'approved'";
    $params = [];
    if ($city) {
        $query .= " AND city = ?";
        $params[] = $city;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $salons = $stmt->fetchAll();
    sendResponse(200, "Salonlar listelendi", $salons);
}

if ($method === 'POST') {
    // Korumalı: Salon ekle (salon sahipleri için)
    $userData = authenticate();
    if ($userData['role'] !== 'salon_owner' && $userData['role'] !== 'admin') {
        sendError(403, "Erişim reddedildi");
    }

    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->name) || empty($data->city)) {
        sendError(400, "Salon adı veya şehir eksik");
    }

    $stmt = $pdo->prepare("INSERT INTO salons (owner_id, name, type, description, address, city, latitude, longitude, opening_time, closing_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userData['id'],
        $data->name,
        $data->type ?? 'male',
        $data->description ?? null,
        $data->address ?? null,
        $data->city,
        $data->latitude ?? null,
        $data->longitude ?? null,
        $data->opening_time ?? '09:00:00',
        $data->closing_time ?? '20:00:00'
    ]);

    sendResponse(201, "Salon oluşturuldu ve onay bekliyor", ["id" => $pdo->lastInsertId()]);
}
?>
