<?php
// admin/approve_salon.php
require_once '../backend/config/db.php';

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && $action) {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE salons SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE salons SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header("Location: index.php");
exit;
?>
