<?php
// customer/add_review.php - Handle review submission
session_start();
require_once '../backend/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comment = $_POST['comment'] ?? '';

    if (!$appointment_id || !$rating) {
        echo json_encode(['success' => false, 'message' => 'Lütfen puan seçiniz.']);
        exit;
    }

    // Randevunun bu müşteriye ait olduğunu ve TAMAMLANDIĞINI doğrula
    $stmt = $pdo->prepare("SELECT id, salon_id FROM appointments WHERE id = ? AND customer_id = ? AND status = 'completed'");
    $stmt->execute([$appointment_id, $customer_id]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Yalnızca tamamlanmış randevulara yorum yapabilirsiniz.']);
        exit;
    }

    // Daha önce yorum yapılmış mı kontrol et
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu randevu için zaten yorum yaptınız.']);
        exit;
    }

    // Kaydet
    $stmt = $pdo->prepare("INSERT INTO reviews (appointment_id, customer_id, salon_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$appointment_id, $customer_id, $appointment['salon_id'], $rating, $comment])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
?>
