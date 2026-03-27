<?php
// salon/register.php - Updated with Certificate Upload
session_start();
require_once '../backend/config/db.php';

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $salon_name = $_POST['salon_name'] ?? '';
    $city = $_POST['city'] ?? '';
    $type = "";
    if (isset($_POST['type']) && is_array($_POST['type'])) {
        $type = implode(',', $_POST['type']);
    }
    
    // Sertifika/Belge yükleme
    $certificate_path = "";
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === 0) {
        $uploadDir = '../uploads/certificates/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $uploadDir . $fileName)) {
            $certificate_path = 'uploads/certificates/' . $fileName;
        }
    }

    if (empty($name) || empty($email) || empty($password) || empty($salon_name) || empty($certificate_path)) {
        $message = "Lütfen tüm zorunlu alanları ve yetkinlik belgesini doldurun.";
    } else {
        // E-posta kontrolü
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Bu e-posta adresi zaten kullanımda.";
        } else {
            $pdo->beginTransaction();
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'salon_owner')");
                $stmt->execute([$name, $email, $phone, $hashedPassword]);
                $owner_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO salons (owner_id, name, type, city, certificate_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$owner_id, $salon_name, $type, $city, $certificate_path]);
                
                $pdo->commit();
                $success = true;
                $message = "Kaydınız ve salon talebiniz oluşturuldu! Yetkinlik belgeniz yönetici onayından geçtikten sonra giriş yapabilirsiniz.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İşletme Kaydı - Berberim</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .register-card { width: 500px; padding: 40px; background: var(--mac-glass); backdrop-filter: blur(25px); border: 1px solid var(--mac-border); border-radius: 28px; box-shadow: 0 25px 50px rgba(0,0,0,0.3); text-align: center; }
        .alert { padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(255,255,255,0.1); }
        .alert-error { background: rgba(231, 76, 60, 0.2); color: #ff6b6b; border-color: rgba(231, 76, 60, 0.3); }
        .alert-success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border-color: rgba(46, 204, 113, 0.3); }
        .file-input { margin-bottom: 15px; text-align: left; }
        .file-input label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--mac-text-dim); }
    </style>
</head>
<body>
    <div class="register-card">
        <div style="font-size: 3rem; color: var(--mac-blue); margin-bottom: 20px;"><i class="fas fa-store-alt"></i></div>
        <h2 style="margin-bottom: 30px;">İşletme Kaydı</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" enctype="multipart/form-data">
            <h4 style="text-align: left; margin-bottom: 15px; font-weight: 500; font-size: 0.9rem; color: var(--mac-text-dim);">Kişisel Bilgiler</h4>
            <div class="form-group">
                <input type="text" name="name" class="form-control" placeholder="Ad Soyad" required autofocus>
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="İşletme E-posta Adresi" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Sistem Şifreniz" required>
            </div>
            
            <h4 style="text-align: left; margin: 25px 0 15px; font-weight: 500; font-size: 0.9rem; color: var(--mac-text-dim);">Salon & Yetkinlik</h4>
            <div class="form-group">
                <input type="text" name="salon_name" class="form-control" placeholder="Salon Adı" required>
            </div>
            <div class="form-group">
                <input type="text" name="city" class="form-control" placeholder="Şehir" required>
            </div>
            <div class="form-group" style="text-align: left; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; border: 1px solid var(--mac-border);">
                <label style="display: block; margin-bottom: 10px; font-size: 0.85rem; color: var(--mac-text-dim);">Berber Türü (Birden fazla seçilebilir)</label>
                <div style="display: flex; gap: 15px;">
                    <label style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="male"> Erkek
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="female"> Kadın
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="child"> Çocuk
                    </label>
                </div>
            </div>
            <div class="file-input">
                <label><i class="fas fa-id-card"></i> Yetkinlik Belgesi / Ruhsat (PDF, JPG, PNG)</label>
                <input type="file" name="certificate" class="form-control" required style="padding: 10px;">
            </div>
            <button type="submit" class="btn-primary" style="margin-top: 15px;">KAYIT TALEBİ GÖNDER</button>
        </form>
        <?php else: ?>
            <div style="margin: 40px 0; color: var(--mac-text-dim);">Talebiniz inceleniyor. Lütfen bekleyiniz.</div>
            <a href="../index.php" class="btn-primary" style="display: block; text-decoration: none;">ANA SAYFAYA DÖN</a>
        <?php endif; ?>

        <div style="margin-top: 30px; font-size: 0.85rem; color: var(--mac-text-dim);">
            Zaten hesabınız var mı? <a href="login.php" style="color: #fff; text-decoration: none; font-weight: 600;">Giriş Yap</a>
        </div>
    </div>
</body>
</html>
