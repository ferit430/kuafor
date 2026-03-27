<?php
// backend/api/available_slots.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response_helper.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$date = $_GET['date'] ?? null;
$salon_id = $_GET['salon_id'] ?? null;
$staff_id = $_GET['staff_id'] ?? null;
$service_ids_str = $_GET['service_ids'] ?? null;

if (!$date || !$salon_id || !$staff_id || !$service_ids_str) {
    sendError(400, "Eksik parametreler (date, salon_id, staff_id, service_ids)");
}

// 1. Toplam Hizmet Süresini Hesapla
$service_ids = explode(',', $service_ids_str);
$in_query = implode(',', array_fill(0, count($service_ids), '?'));
$stmt = $pdo->prepare("SELECT SUM(duration_minutes) as total_duration FROM services WHERE id IN ($in_query)");
$stmt->execute($service_ids);
$total_duration = $stmt->fetch()['total_duration'] ?? 30;

// 2. Salon Çalışma Saatlerini Al
$stmt = $pdo->prepare("SELECT opening_time, closing_time FROM salons WHERE id = ?");
$stmt->execute([$salon_id]);
$salon = $stmt->fetch();
if (!$salon) sendError(404, "Salon bulunamadı");

$opening = (!empty($salon['opening_time'])) ? $salon['opening_time'] : '09:00:00';
$closing = (!empty($salon['closing_time'])) ? $salon['closing_time'] : '22:00:00';

// 3. Mevcut Randevuları Al
$stmt = $pdo->prepare("SELECT start_time, end_time FROM appointments 
                       WHERE staff_id = ? AND appointment_date = ? AND status != 'cancelled'");
$stmt->execute([$staff_id, $date]);
$appointments = $stmt->fetchAll();

// 4. Slot Üret
$available_slots = [];
$current_time = strtotime($opening);
$end_limit = strtotime($closing);

// Eğer bugün seçiliyse, geçmiş saatleri ele
if ($date == date('Y-m-d')) {
    $now = strtotime(date('H:i:s'));
    if ($current_time < $now) {
        $current_time = ceil($now / (15 * 60)) * (15 * 60); // Bir sonraki 15 dakikaya yuvarla
    }
}

while ($current_time + ($total_duration * 60) <= $end_limit) {
    $slot_start = date('H:i:s', $current_time);
    $slot_end = date('H:i:s', $current_time + ($total_duration * 60));
    
    $is_booked = false;
    foreach ($appointments as $app) {
        // Çakışma kontrolü: [slot_start, slot_end] çakışıyor mu [app_start, app_end]
        if ($slot_start < $app['end_time'] && $slot_end > $app['start_time']) {
            $is_booked = true;
            break;
        }
    }
    
    if (!$is_booked) {
        $available_slots[] = substr($slot_start, 0, 5);
    }
    
    // 30 dakikalık adımlarla ilerle (isteğe bağlı 15 dk da olur)
    $current_time += 30 * 60;
}

sendResponse(200, "Müsait saatler listelendi", [
    "slots" => $available_slots,
    "total_duration" => $total_duration
]);
?>
