<?php
// backend/api/register.php
require_once '../config/db.php';
require_once '../helpers/response_helper.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, "Geçersiz İstek Yöntemi");
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->name) || empty($data->email) || empty($data->password)) {
    sendError(400, "Lütfen tüm zorunlu alanları doldurun");
}

// E-posta kontrolü
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$data->email]);
if ($stmt->fetch()) {
    sendError(400, "Bu e-posta adresi zaten kayıtlı");
}

$hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);
$role = !empty($data->role) ? $data->role : 'customer';

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data->name, $data->email, $hashedPassword, $data->phone ?? null, $role]);
    
    sendResponse(201, "Kullanıcı başarıyla kaydedildi", ["id" => $pdo->lastInsertId()]);
} catch (Exception $e) {
    sendError(500, "Kayıt sırasında bir hata oluştu: " . $e->getMessage());
}
?>
