<?php
// admin/settings.php - Site Configuration Module
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
    $title = $_POST['site_title'] ?? '';
    $email = $_POST['site_email'] ?? '';
    $phone = $_POST['site_phone'] ?? '';
    $address = $_POST['site_address'] ?? '';
    $footer = $_POST['footer_text'] ?? '';
    $facebook = $_POST['facebook_url'] ?? '';
    $instagram = $_POST['instagram_url'] ?? '';
    $twitter = $_POST['twitter_url'] ?? '';
    $youtube = $_POST['youtube_url'] ?? '';
    $logo_height = $_POST['logo_height'] ?? 40;

    // Logo yükleme işlemi
    $logo_path = $settings['site_logo'];
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'svg'];
        $filename = $_FILES['site_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "logo_" . time() . "." . $ext;
            $upload_path = "../assets/img/" . $new_name;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_path)) {
                $logo_path = "assets/img/" . $new_name;
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE settings SET site_title = ?, site_email = ?, site_phone = ?, site_address = ?, footer_text = ?, facebook_url = ?, instagram_url = ?, twitter_url = ?, youtube_url = ?, site_logo = ?, logo_height = ? WHERE id = ?");
    if ($stmt->execute([$title, $email, $phone, $address, $footer, $facebook, $instagram, $twitter, $youtube, $logo_path, $logo_height, $settings['id']])) {
        $message = "Site ayarları başarıyla güncellendi.";
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
    <title>Site Yönetimi - Admin</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">Berberim <span style="color: var(--admin-blue);">Admin</span></div>
        <a href="index.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="salons.php" class="sidebar-item"><i class="fas fa-store"></i> İşletme Yönetimi</a>
        <a href="seo.php" class="sidebar-item"><i class="fas fa-search-dollar"></i> SEO Yardımcısı</a>
        <a href="settings.php" class="sidebar-item active"><i class="fas fa-cog"></i> Site Yönetimi</a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--admin-border);">
            <a href="logout.php" class="sidebar-item" style="color: #FF453A;"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
        </div>
    </div>

    <div class="main-content">
        <header style="margin-bottom: 50px; text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: 700;">Site Yönetimi</h1>
            <p style="color: var(--admin-text-dim); font-size: 1.1rem;">Sistem genel ayarlarını ve kurumsal iletişim bilgilerini güncelleyin.</p>
        </header>

        <div class="form-card" style="max-width: 850px; margin: 0 auto;">
            <?php if ($message): ?>
                <div class="alert" style="background: rgba(52, 199, 89, 0.1); color: #34C759; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(52, 199, 89, 0.2);"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="background: rgba(255, 255, 255, 0.02); padding: 25px; border-radius: 18px; border: 1px solid var(--admin-border); margin-bottom: 30px; display: flex; align-items: center; gap: 30px;">
                    <div style="width: 150px; height: 150px; background: rgba(0,0,0,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px dashed var(--admin-border);">
                        <img src="../<?php echo $settings['site_logo']; ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">LOGO DEĞİŞTİR (PNG/SVG)</label>
                        <input type="file" name="site_logo" class="form-control" style="padding: 10px; height: auto;">
                        <div style="margin-top: 15px; display: flex; align-items: center; gap: 15px;">
                            <label style="font-size: 0.85rem; color: var(--admin-text-dim);">Logo Yüksekliği (px):</label>
                            <input type="number" name="logo_height" value="<?php echo $settings['logo_height'] ?? 40; ?>" style="width: 80px; padding: 8px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--admin-border); border-radius: 8px;">
                        </div>
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">SİSTEM BAŞLIĞI (SITE TITLE)</label>
                    <input type="text" name="site_title" class="form-control" value="<?php echo htmlspecialchars($settings['site_title']); ?>" placeholder="Berberim - Randevu Sistemi">
                </div>
                <div class="form-grid">
                    <div class="input-group">
                        <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">RESMİ E-POSTA</label>
                        <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($settings['site_email']); ?>" placeholder="info@berberim.com">
                    </div>
                    <div class="input-group">
                        <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">İLETİŞİM TELEFONU</label>
                        <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($settings['site_phone']); ?>" placeholder="0(555) 000 00 00">
                    </div>
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">FİZİKSEL ADRES</label>
                    <textarea name="site_address" class="form-control" rows="2" placeholder="Kurumsal ofis adresi..."><?php echo htmlspecialchars($settings['site_address']); ?></textarea>
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="font-weight: 600; font-size: 0.9rem; color: var(--admin-text-dim); margin-bottom: 10px; display: block;">FOOTER (ALT BİLGİ) METNİ</label>
                    <textarea name="footer_text" class="form-control" rows="2" placeholder="© 2026 Berberim. Tüm hakları saklıdır."><?php echo htmlspecialchars($settings['footer_text']); ?></textarea>
                </div>

                <div style="margin-top: 40px; border-top: 1px solid var(--admin-border); padding-top: 30px;">
                    <h3 style="margin-bottom: 25px; color: var(--admin-blue);"><i class="fas fa-share-alt"></i> Sosyal Medya Bağlantıları</h3>
                    <div class="form-grid">
                        <div class="input-group">
                            <label style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text-dim); margin-bottom: 8px; display: block;">INSTAGRAM</label>
                            <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/kullanici">
                        </div>
                        <div class="input-group">
                            <label style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text-dim); margin-bottom: 8px; display: block;">FACEBOOK</label>
                            <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/sayfa">
                        </div>
                        <div class="input-group">
                            <label style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text-dim); margin-bottom: 8px; display: block;">TWITTER (X)</label>
                            <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>" placeholder="https://x.com/kullanici">
                        </div>
                        <div class="input-group">
                            <label style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text-dim); margin-bottom: 8px; display: block;">YOUTUBE</label>
                            <input type="url" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>" placeholder="https://youtube.com/kanal">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-premium" style="width: 100%; font-size: 1.1rem; letter-spacing: 1px;">SİSTEM AYARLARINI KAYDET</button>
            </form>
        </div>
    </div>
</body>
</html>
