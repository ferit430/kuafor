<?php
// salon/appointments.php
session_start();
if (!isset($_SESSION['salon_owner_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

$owner_id = $_SESSION['salon_owner_id'];

// Salon bilgisini al
$stmt = $pdo->prepare("SELECT id, name FROM salons WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$salon = $stmt->fetch();

if (!$salon) {
    header("Location: index.php");
    exit;
}

$salon_id = $salon['id'];

// Durum güncelleme (Opsiyonel: Eğer bu sayfada POST ile işlem yapılacaksa)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = $_GET['id'];
    $action = $_GET['action'];
    $allowed_statuses = ['confirmed', 'cancelled', 'completed'];
    
    if (in_array($action, $allowed_statuses)) {
        $upd = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND salon_id = ?");
        $upd->execute([$action, $target_id, $salon_id]);
        header("Location: appointments.php?msg=success");
        exit;
    }
}

// Tüm randevuları al
$stmt = $pdo->prepare("SELECT a.*, u.name as customer_name, u.phone as customer_phone,
                             GROUP_CONCAT(s.name SEPARATOR ', ') as service_names,
                             st.name as staff_name 
                      FROM appointments a 
                      JOIN users u ON a.customer_id = u.id
                      JOIN staff st ON a.staff_id = st.id
                      LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
                      LEFT JOIN services s ON aps.service_id = s.id
                      WHERE a.salon_id = ? 
                      GROUP BY a.id
                      ORDER BY a.appointment_date DESC, a.start_time DESC");
$stmt->execute([$salon_id]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Randevu Yönetimi - <?php echo htmlspecialchars($salon['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge { padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
        .status-pending { background: rgba(255, 159, 10, 0.2); color: #FF9F0A; }
        .status-confirmed { background: rgba(10, 132, 255, 0.2); color: #0A84FF; }
        .status-completed { background: rgba(48, 209, 88, 0.2); color: #30D158; }
        .status-cancelled { background: rgba(255, 69, 58, 0.2); color: #FF453A; }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container" style="max-height: 95vh; width: 95%;">
            <header>
                <h1>Randevu Yönetimi</h1>
            </header>

            <?php include 'navbar.php'; ?>

            <table>
                <thead>
                    <tr>
                        <th>Müşteri</th>
                        <th>Hizmet</th>
                        <th>Personel</th>
                        <th>Tarih & Saat</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $app): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($app['customer_name']); ?></strong><br>
                            <span style="font-size: 0.8rem; color: var(--mac-text-dim);"><?php echo htmlspecialchars($app['customer_phone']); ?></span>
                        </td>
                        <td style="font-size: 0.85rem; max-width: 250px; line-height: 1.4; color: var(--mac-text-dim);">
                            <?php echo htmlspecialchars($app['service_names']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($app['staff_name']); ?></td>
                        <td style="white-space: nowrap;">
                            <strong><?php echo date('d.m.Y', strtotime($app['appointment_date'])); ?></strong><br>
                            <span style="font-size: 0.85rem; color: var(--mac-text-dim);"><?php echo substr($app['start_time'], 0, 5); ?> - <?php echo substr($app['end_time'], 0, 5); ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $app['status']; ?>">
                                <?php 
                                    $labels = ['pending' => 'Bekliyor', 'confirmed' => 'Onaylı', 'completed' => 'Bitti', 'cancelled' => 'İptal'];
                                    echo $labels[$app['status']] ?? $app['status'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: flex-start;">
                                <?php if ($app['status'] == 'pending'): ?>
                                    <a href="?action=confirmed&id=<?php echo $app['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem; width: auto; background: #30D158;">Onayla</a>
                                <?php endif; ?>
                                <?php if ($app['status'] == 'confirmed'): ?>
                                    <a href="?action=completed&id=<?php echo $app['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem; width: auto; background: #0A84FF;">Bitti</a>
                                <?php endif; ?>
                                <?php if ($app['status'] != 'cancelled' && $app['status'] != 'completed'): ?>
                                    <a href="?action=cancelled&id=<?php echo $app['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem; width: auto; background: #FF453A;">İptal</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 50px; color: var(--mac-text-dim);">Henüz randevu bulunmuyor.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
