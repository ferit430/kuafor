<?php
// backend/api/login.php
require_once '../config/db.php';
require_once '../helpers/response_helper.php';
require_once '../helpers/jwt_helper.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, "Geçersiz İstek Yöntemi");
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    sendError(400, "E-posta veya şifre eksik");
}

$stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$stmt->execute([$data->email]);
$user = $stmt->fetch();

if (!$user || !password_verify($data->password, $user['password'])) {
    sendError(401, "Geçersiz e-posta veya şifre");
}

$payload = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'exp' => time() + (60 * 60 * 24) // 24 saat
];

$token = JWTHelper::generate($payload);

sendResponse(200, "Giriş başarılı", [
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "role" => $user['role']
    ],
    "token" => $token
]);
?>
