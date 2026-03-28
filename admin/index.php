<?php
// admin/index.php - Updated with Certificate Verification
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: /admin/login.php");
    exit;
}
require_once dirname(__DIR__) . '/backend/config/db.php';

// İstatistikleri al
$salonCount = $pdo->query("SELECT COUNT(*) FROM salons")->fetchColumn();
$userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$pendingSalons = $pdo->query("SELECT COUNT(*) FROM salons WHERE status = 'pending'")->fetchColumn();
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

// Onay bekleyen salonlar
$salons = $pdo->query("SELECT * FROM salons WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

// Tüm salonlar
$allSalons = $pdo->query("SELECT * FROM salons ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Berberim</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">Berberim <span style="color: var(--admin-blue);">Admin</span></div>
        <a href="index.php" class="sidebar-item active"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="salons.php" class="sidebar-item"><i class="fas fa-store"></i> İşletme Yönetimi</a>
        <a href="seo.php" class="sidebar-item"><i class="fas fa-search-dollar"></i> SEO Yardımcısı</a>
        <a href="settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Site Yönetimi</a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--admin-border);">
            <a href="logout.php" class="sidebar-item" style="color: #FF453A;"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
        </div>
    </div>

    <div class="main-content">
        <header style="margin-bottom: 50px;">
            <h1 style="font-size: 2.5rem; font-weight: 700;">Dashboard</h1>
            <p style="color: var(--admin-text-dim); font-size: 1.1rem;">Sistem genelindeki anlık durum ve temel istatistikler.</p>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-store" style="color: var(--admin-blue);"></i>
                <div style="color: var(--admin-text-dim); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Toplam Salon</div>
                <div style="font-size: 2.5rem; font-weight: 700; margin-top: 10px;"><?php echo $salonCount; ?></div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #FF9500;">
                <i class="fas fa-clock" style="color: #FF9500;"></i>
                <div style="color: var(--admin-text-dim); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Onay Bekleyen</div>
                <div style="font-size: 2.5rem; font-weight: 700; color: #FF9500; margin-top: 10px;"><?php echo $pendingSalons; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users" style="color: #34C759;"></i>
                <div style="color: var(--admin-text-dim); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Toplam Müşteri</div>
                <div style="font-size: 2.5rem; font-weight: 700; margin-top: 10px;"><?php echo $userCount; ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check" style="color: #AF52DE;"></i>
                <div style="color: var(--admin-text-dim); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Toplam Randevu</div>
                <div style="font-size: 2.5rem; font-weight: 700; margin-top: 10px;"><?php echo $totalAppointments; ?></div>
            </div>
        </div>

        <div class="form-card" style="text-align: center; background: rgba(255,255,255,0.03);">
            <div style="font-size: 3rem; margin-bottom: 20px;">👋</div>
            <h2 style="font-size: 1.8rem; margin-bottom: 15px;">Hoş Geldiniz, Yönetici</h2>
            <p style="color: var(--admin-text-dim); max-width: 500px; margin: 0 auto; line-height: 1.6;">Sol menüdeki modülleri kullanarak işletmeleri onaylayabilir, SEO ayarlarını yapabilir veya genel site yapılandırmasını güncelleyebilirsiniz.</p>
        </div>
    </div>
</body>
</html>
