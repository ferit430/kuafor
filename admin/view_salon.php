<?php
// admin/view_salon.php - Detailed Salon View for Admin
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: salons.php"); exit; }

// Salon bilgileri
$stmt = $pdo->prepare("SELECT * FROM salons WHERE id = ?");
$stmt->execute([$id]);
$salon = $stmt->fetch();

if (!$salon) { header("Location: salons.php"); exit; }

// Personeller
$stmt = $pdo->prepare("SELECT * FROM staff WHERE salon_id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetchAll();

// Hizmetler
$stmt = $pdo->prepare("SELECT * FROM services WHERE salon_id = ?");
$stmt->execute([$id]);
$services = $stmt->fetchAll();

// İşletme sahibi
$stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$salon['owner_id']]);
$owner = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($salon['name']); ?> Detayları - Admin</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">Berberim <span style="color: var(--admin-blue);">Admin</span></div>
        <a href="index.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="salons.php" class="sidebar-item active"><i class="fas fa-store"></i> İşletme Yönetimi</a>
        <a href="seo.php" class="sidebar-item"><i class="fas fa-search-dollar"></i> SEO Yardımcısı</a>
        <a href="settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Site Yönetimi</a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--admin-border);">
            <a href="logout.php" class="sidebar-item" style="color: #FF453A;"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
        </div>
    </div>

    <div class="main-content">
        <header style="margin-bottom: 50px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <a href="salons.php" style="color: var(--admin-blue); text-decoration: none; font-size: 0.9rem; font-weight: 600;"><i class="fas fa-arrow-left"></i> LİSTEYE DÖN</a>
                <h1 style="margin-top: 15px;"><?php echo htmlspecialchars($salon['name']); ?></h1>
                <p style="color: var(--admin-text-dim);"><?php echo htmlspecialchars($salon['city']); ?> şubesi detaylı incelemesi.</p>
            </div>
            <div class="badge" style="background: <?php echo $salon['status'] == 'approved' ? 'rgba(52, 199, 89, 0.2)' : 'rgba(255, 149, 0, 0.2)'; ?>; color: <?php echo $salon['status'] == 'approved' ? '#34C759' : '#FF9500'; ?>; padding: 10px 20px; font-size: 0.9rem;">
                DURUM: <?php echo strtoupper($salon['status']); ?>
            </div>
        </header>

        <div class="detail-grid">
            <div class="form-card" style="padding: 30px;">
                <div class="section-title" style="font-size: 1.2rem; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: var(--admin-blue);"><i class="fas fa-info-circle"></i> Genel Bilgiler</div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 15px;"><span style="color: var(--admin-text-dim);">Telefon:</span> <strong><?php echo htmlspecialchars($salon['phone']); ?></strong></div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 15px;"><span style="color: var(--admin-text-dim);">Şehir / İlçe:</span> <strong><?php echo htmlspecialchars($salon['city'] . ' / ' . ($salon['district'] ?? 'Belirtilmedi')); ?></strong></div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 15px;"><span style="color: var(--admin-text-dim);">Çalışma Saatleri:</span> <strong><?php echo $salon['opening_time'] . ' - ' . $salon['closing_time']; ?></strong></div>
                <div class="data-row" style="display: flex; flex-direction: column; gap: 10px;"><span style="color: var(--admin-text-dim);">Adres:</span> <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; border: 1px solid var(--admin-border); font-size: 0.9rem;"><?php echo htmlspecialchars($salon['address']); ?></div></div>
            </div>

            <div class="form-card" style="padding: 30px;">
                <div class="section-title" style="font-size: 1.2rem; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: #AF52DE;"><i class="fas fa-user-tie"></i> İşletme Sahibi</div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 15px;"><span style="color: var(--admin-text-dim);">Ad Soyad:</span> <strong><?php echo htmlspecialchars($owner['name']); ?></strong></div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 15px;"><span style="color: var(--admin-text-dim);">E-posta:</span> <strong><?php echo htmlspecialchars($owner['email']); ?></strong></div>
                <div class="data-row" style="display: flex; justify-content: space-between;"><span style="color: var(--admin-text-dim);">Telefon:</span> <strong><?php echo htmlspecialchars($owner['phone'] ?? '-'); ?></strong></div>
            </div>

            <div class="form-card" style="padding: 30px;">
                <div class="section-title" style="font-size: 1.2rem; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: #FF9500;"><i class="fas fa-certificate"></i> Belgeler & Görseller</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--admin-text-dim); margin-bottom: 10px;">Kapak Görseli</div>
                        <?php if($salon['cover_image']): ?>
                            <img src="../<?php echo $salon['cover_image']; ?>" style="width: 100%; border-radius: 15px; border: 1px solid var(--admin-border);">
                        <?php else: ?>
                            <div style="height: 100px; background: rgba(255,255,255,0.03); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: var(--admin-text-dim);">Görsel Yok</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--admin-text-dim); margin-bottom: 10px;">Yetkinlik Belgesi</div>
                        <?php if($salon['certificate_path']): ?>
                            <img src="../<?php echo $salon['certificate_path']; ?>" style="width: 100%; border-radius: 15px; border: 1px solid var(--admin-border);">
                        <?php else: ?>
                            <div style="height: 100px; background: rgba(255,255,255,0.03); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: var(--admin-text-dim);">Belge Yok</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-card" style="padding: 30px;">
                <div class="section-title" style="font-size: 1.2rem; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: #34C759;"><i class="fas fa-users-cog"></i> Kadro & Hizmet</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--admin-text-dim); margin-bottom: 10px;">Personeller (<?php echo count($staff); ?>)</div>
                        <ul style="list-style: none; font-size: 0.9rem;">
                            <?php foreach($staff as $s): ?>
                                <li style="margin-bottom: 5px;">• <?php echo htmlspecialchars($s['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--admin-text-dim); margin-bottom: 10px;">Hizmetler (<?php echo count($services); ?>)</div>
                        <ul style="list-style: none; font-size: 0.9rem;">
                            <?php foreach($services as $sv): ?>
                                <li style="margin-bottom: 5px;">• <?php echo htmlspecialchars($sv['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
