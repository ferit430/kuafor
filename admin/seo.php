<?php
// admin/seo.php - SEO Management Module
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

// Mevcut ayarları al
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keywords = $_POST['seo_keywords'] ?? '';
    $description = $_POST['site_description'] ?? '';
    $author = $_POST['seo_author'] ?? '';
    $analytics = $_POST['google_analytics'] ?? '';

    $stmt = $pdo->prepare("UPDATE settings SET seo_keywords = ?, site_description = ?, seo_author = ?, google_analytics = ? WHERE id = ?");
    if ($stmt->execute([$keywords, $description, $author, $analytics, $settings['id']])) {
        $message = "SEO ayarları başarıyla güncellendi.";
        $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(); // Veriyi tazele
    } else {
        $message = "Bir hata oluştu.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Yardımcısı - Admin</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">Berberim <span style="color: var(--admin-blue);">Admin</span></div>
        <a href="index.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="salons.php" class="sidebar-item"><i class="fas fa-store"></i> İşletme Yönetimi</a>
        <a href="seo.php" class="sidebar-item active"><i class="fas fa-search-dollar"></i> SEO Yardımcısı</a>
        <a href="settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Site Yönetimi</a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--admin-border);">
            <a href="logout.php" class="sidebar-item" style="color: #FF453A;"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
        </div>
    </div>

    <div class="main-content">
        <header style="margin-bottom: 50px; text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: 700;">SEO Yardımcısı</h1>
            <p style="color: var(--admin-text-dim); font-size: 1.1rem;">Sitenizin arama motorlarındaki görünürlüğünü ve teknik analiz verilerini yönetin.</p>
        </header>

        <div class="form-card" style="max-width: 800px; margin: 0 auto;">
            <?php if ($message): ?>
                <div class="alert" style="background: rgba(52, 199, 89, 0.1); color: #34C759; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(52, 199, 89, 0.2);"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">META BAŞLIK / YAZAR</label>
                    <input type="text" name="seo_author" class="form-control" value="<?php echo htmlspecialchars($settings['seo_author']); ?>" placeholder="Site Yazarı">
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">META AÇIKLAMASI (DESCRIPTION)</label>
                    <textarea name="site_description" class="form-control" rows="3" placeholder="Siteniz hakkında kısa bir bilgi..."><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">ANAHTAR KELİMELER (KEYWORDS)</label>
                    <textarea name="seo_keywords" class="form-control" rows="2" placeholder="berber, kuaför, randevu... (virgül ile ayırın)"><?php echo htmlspecialchars($settings['seo_keywords']); ?></textarea>
                </div>
                <div class="input-group" style="margin-bottom: 35px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">GOOGLE ANALYTICS / TAKİP KODU</label>
                    <textarea name="google_analytics" class="form-control" rows="4" style="font-family: monospace; font-size: 0.85rem;" placeholder="<script>...</script>"><?php echo htmlspecialchars($settings['google_analytics']); ?></textarea>
                    <p style="color: var(--admin-text-dim); font-size: 0.75rem; margin-top: 10px; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-info-circle"></i> Bu alan sitenin tüm sayfalarının &lt;head&gt; kısmına eklenecek kodları içerir.
                    </p>
                </div>
                
                <button type="submit" class="btn-premium" style="width: 100%; font-size: 1rem; letter-spacing: 1px;">SEO AYARLARINI GÜNCELLE</button>
            </form>
        </div>
    </div>
</body>
</html>
