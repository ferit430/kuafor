<?php
// backend/api/appointments.php
require_once '../config/db.php';
require_once '../helpers/response_helper.php';
require_once '../middleware/auth_token.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PATCH");

$method = $_SERVER['REQUEST_METHOD'];
$userData = authenticate();

if ($method === 'GET') {
    // Müşteriler kendi randevularını, personel/sahipler salon randevularını görür
    if ($userData['role'] === 'customer') {
        $stmt = $pdo->prepare("SELECT a.*, s.name as salon_name, sv.name as service_name, st.name as staff_name 
                               FROM appointments a 
                               JOIN salons s ON a.salon_id = s.id 
                               JOIN services sv ON a.service_id = sv.id 
                               JOIN staff st ON a.staff_id = st.id 
                               WHERE a.customer_id = ? ORDER BY a.appointment_date DESC, a.start_time DESC");
        $stmt->execute([$userData['id']]);
    } else {
        $salon_id = $_GET['salon_id'] ?? null;
        if (!$salon_id) sendError(400, "salon_id gerekli");
        $stmt = $pdo->prepare("SELECT a.*, u.name as customer_name, sv.name as service_name, st.name as staff_name 
                               FROM appointments a 
                               JOIN users u ON a.customer_id = u.id 
                               JOIN services sv ON a.service_id = sv.id 
                               JOIN staff st ON a.staff_id = st.id 
                               WHERE a.salon_id = ? ORDER BY a.appointment_date DESC, a.start_time DESC");
        $stmt->execute([$salon_id]);
    }
    sendResponse(200, "Randevular listelendi", $stmt->fetchAll());
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    // service_id (tekli) veya service_ids (çoklu - dizi) kontrolü
    $service_ids = [];
    if (isset($data->service_ids) && is_array($data->service_ids)) {
        $service_ids = $data->service_ids;
    } elseif (!empty($data->service_id)) {
        $service_ids = [$data->service_id];
    }

    if (empty($data->salon_id) || empty($data->staff_id) || empty($service_ids) || empty($data->date) || empty($data->start_time)) {
        sendError(400, "Eksik alanlar var");
    }

    // 1. Hizmetlerin toplam süresini ve fiyatını al
    $in_query = implode(',', array_fill(0, count($service_ids), '?'));
    $stmt = $pdo->prepare("SELECT SUM(duration_minutes) as total_duration, SUM(price) as total_price FROM services WHERE id IN ($in_query)");
    $stmt->execute($service_ids);
    $totals = $stmt->fetch();
    
    if (!$totals || $totals['total_price'] == 0) sendError(404, "Hizmetler bulunamadı");

    $start_time = $data->start_time;
    $total_duration = $totals['total_duration'];
    $total_price = $totals['total_price'];
    $end_time = date('H:i:s', strtotime("+$total_duration minutes", strtotime($start_time)));

    // 2. Çakışma Kontrolü: Personel müsait mi?
    $stmt = $pdo->prepare("SELECT id FROM appointments 
                           WHERE staff_id = ? AND appointment_date = ? AND status != 'cancelled' 
                           AND (? < end_time AND ? > start_time)");
    $stmt->execute([$data->staff_id, $data->date, $start_time, $end_time]);

    if ($stmt->fetch()) {
        sendError(409, "Bu personel belirtilen saatte dolu");
    }

    // 3. Randevu oluştur
    $stmt = $pdo->prepare("INSERT INTO appointments (customer_id, salon_id, staff_id, service_id, service_ids, appointment_date, start_time, end_time, total_price) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userData['id'],
        $data->salon_id,
        $data->staff_id,
        $service_ids[0], // Geriye dönük uyumluluk için ilkini ana hizmet yapalım
        implode(',', $service_ids),
        $data->date,
        $start_time,
        $end_time,
        $total_price
    ]);

    sendResponse(201, "Randevu talebi oluşturuldu", ["id" => $pdo->lastInsertId()]);
}

if ($method === 'PATCH') {
    $data = json_decode(file_get_contents("php://input"));
    if (empty($data->id) || empty($data->status)) sendError(400, "id ve durum gerekli");

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$data->status, $data->id]);
    sendResponse(200, "Randevu durumu güncellendi");
}
?>
