<?php
// salon/index.php
session_start();
if (!isset($_SESSION['salon_owner_id'])) {
    header("Location: /salon/login.php");
    exit;
}
require_once dirname(__DIR__) . '/backend/config/db.php';

$owner_id = $_SESSION['salon_owner_id'];

// Salon bilgisini al
$stmt = $pdo->prepare("SELECT * FROM salons WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$salon = $stmt->fetch();

if (!$salon) {
    header("Location: setup_salon.php");
    exit;
}

$salon_id = $salon['id'];
$_SESSION['salon_id'] = $salon_id;

// İstatistikler
$serviceCount = $pdo->query("SELECT COUNT(*) FROM services WHERE salon_id = $salon_id")->fetchColumn();
$staffCount = $pdo->query("SELECT COUNT(*) FROM staff WHERE salon_id = $salon_id")->fetchColumn();
$appointmentCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE salon_id = $salon_id AND status != 'cancelled'")->fetchColumn();
$totalEarnings = $pdo->query("SELECT SUM(total_price) FROM appointments WHERE salon_id = $salon_id AND status = 'completed'")->fetchColumn() ?? 0;

// Son randevular
$stmt = $pdo->prepare("SELECT a.*, u.name as customer_name, 
                             GROUP_CONCAT(s.name SEPARATOR ', ') as service_names,
                             st.name as staff_name 
                      FROM appointments a 
                      JOIN users u ON a.customer_id = u.id 
                      JOIN staff st ON a.staff_id = st.id 
                      LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
                      LEFT JOIN services s ON aps.service_id = s.id 
                      WHERE a.salon_id = ? 
                      GROUP BY a.id
                      ORDER BY a.appointment_date DESC, a.start_time DESC 
                      LIMIT 5");
$stmt->execute([$salon_id]);
$recentAppointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($salon['name']); ?> - İşletme Paneli</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header>
                <div>
                    <h1><?php echo htmlspecialchars($salon['name']); ?></h1>
                    <p style="margin: 5px 0 0 0; color: var(--mac-text-dim); font-size: 0.85rem;">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($salon['city']); ?>
                    </p>
                </div>
                <div class="user-info">
                    <span style="margin-right: 15px; color: var(--mac-text-dim);">Hoş geldin, <?php echo htmlspecialchars($_SESSION['salon_owner_name']); ?></span>
                    <a href="logout.php" style="color: #FF453A; text-decoration: none; font-weight: 500;">Çıkış Yap</a>
                </div>
            </header>

            <?php include 'navbar.php'; ?>

            <div class="card-grid">
                <div class="card">
                    <h3 style="font-size: 0.9rem; color: var(--mac-text-dim); margin-bottom: 10px;">Kazanç</h3>
                    <div style="font-size: 1.8rem; font-weight: 600; color: #34C759;"><?php echo number_format($totalEarnings, 2); ?> TL</div>
                </div>
                <div class="card">
                    <h3 style="font-size: 0.9rem; color: var(--mac-text-dim); margin-bottom: 10px;">Randevular</h3>
                    <div style="font-size: 1.8rem; font-weight: 600;"><?php echo $appointmentCount; ?></div>
                </div>
                <div class="card">
                    <h3 style="font-size: 0.9rem; color: var(--mac-text-dim); margin-bottom: 10px;">Hizmetler</h3>
                    <div style="font-size: 1.8rem; font-weight: 600;"><?php echo $serviceCount; ?></div>
                </div>
                <div class="card">
                    <h3 style="font-size: 0.9rem; color: var(--mac-text-dim); margin-bottom: 10px;">Personel</h3>
                    <div style="font-size: 1.8rem; font-weight: 600;"><?php echo $staffCount; ?></div>
                </div>
            </div>

            <h2 style="margin-bottom: 20px; font-size: 1.2rem; font-weight: 500;">Son Randevular</h2>
            <table>
                <thead>
                    <tr>
                        <th>Müşteri</th>
                        <th>Hizmet</th>
                        <th>Personel</th>
                        <th>Tarih & Saat</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAppointments as $app): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($app['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['service_names']); ?></td>
                        <td><?php echo htmlspecialchars($app['staff_name']); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($app['appointment_date'])); ?> <?php echo substr($app['start_time'], 0, 5); ?></td>
                        <td>
                            <span style="background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; text-transform: uppercase;">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
