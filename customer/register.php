<?php
// customer/register.php
session_start();
require_once dirname(__DIR__) . '/backend/config/db.php';

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $city = $_POST['city'] ?? '';
    $district = $_POST['district'] ?? '';
    
    // Basit doğrulama
    if (empty($name) || empty($email) || empty($password) || empty($city)) {
        $message = "Lütfen tüm zorunlu alanları (Ad, E-posta, Şifre, Şehir) doldurun.";
    } else {
        // E-posta kontrolü
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Bu e-posta adresi zaten kullanımda.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, city, district) VALUES (?, ?, ?, ?, 'customer', ?, ?)");
            if ($stmt->execute([$name, $email, $phone, $hashedPassword, $city, $district])) {
                $success = true;
                $message = "Kaydınız başarıyla oluşturuldu! Giriş yapabilirsiniz.";
            } else {
                $message = "Bir hata oluştu. Lütfen tekrar deneyin.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Kaydı - Berberim</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .register-card { width: 450px; max-width: 95%; padding: 40px; background: var(--mac-glass); backdrop-filter: blur(25px); border: 1px solid var(--mac-border); border-radius: 28px; box-shadow: 0 25px 50px rgba(0,0,0,0.3); text-align: center; }
        .alert { padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(255,255,255,0.1); }
        .alert-error { background: rgba(231, 76, 60, 0.2); color: #ff6b6b; border-color: rgba(231, 76, 60, 0.3); }
        .alert-success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border-color: rgba(46, 204, 113, 0.3); }
        select.form-control { appearance: none; cursor: pointer; }

        @media (max-width: 576px) {
            .register-card { padding: 30px 20px; }
            .location-grid { grid-template-columns: 1fr !important; gap: 10px !important; }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div style="font-size: 3rem; color: var(--mac-blue); margin-bottom: 20px;"><i class="fas fa-user-plus"></i></div>
        <h2 style="margin-bottom: 30px;">Müşteri Kaydı</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="name" class="form-control" placeholder="Ad Soyad" required autofocus>
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="E-posta Adresi" required>
            </div>
            <div class="location-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <select name="city" id="citySelect" class="form-control" required onchange="updateDistricts()">
                    <option value="">İl Seçin</option>
                    <option value="İstanbul">İstanbul</option>
                    <option value="Ankara">Ankara</option>
                    <option value="İzmir">İzmir</option>
                    <option value="Bursa">Bursa</option>
                    <option value="Antalya">Antalya</option>
                </select>
                <select name="district" id="districtSelect" class="form-control">
                    <option value="">İlçe Seçin</option>
                </select>
            </div>
            <div class="form-group">
                <input type="text" name="phone" class="form-control" placeholder="Telefon Numarası">
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Şifre" required>
            </div>
            <button type="submit" class="btn-primary">KAYIT OL</button>
        </form>
        <?php else: ?>
            <a href="login.php" class="btn-primary" style="display: block; text-decoration: none;">GİRİŞ YAP</a>
        <?php endif; ?>

        <script src="../assets/js/turkey_data.js"></script>
        <script>
        const citySelect = document.getElementById('citySelect');
        const districtSelect = document.getElementById('districtSelect');

        function initRegisterData() {
            if (typeof turkeyData !== 'undefined' && citySelect) {
                for (let city in turkeyData) {
                    const opt = document.createElement('option');
                    opt.value = city;
                    opt.textContent = city;
                    citySelect.appendChild(opt);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', initRegisterData);

        function updateDistricts() {
            const city = citySelect.value;
            districtSelect.innerHTML = '<option value="">İlçe Seçin</option>';
            
            if (city && typeof turkeyData !== 'undefined' && turkeyData[city]) {
                turkeyData[city].forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.textContent = d;
                    districtSelect.appendChild(opt);
                });
            }
        }
        </script>

        <div style="margin-top: 30px; font-size: 0.85rem; color: var(--mac-text-dim);">
            Zaten hesabınız var mı? <a href="login.php" style="color: #fff; text-decoration: none; font-weight: 600;">Giriş Yap</a>
        </div>
        <div style="margin-top: 15px;">
            <a href="../index.php" style="color: var(--mac-text-dim); text-decoration: none; font-size: 0.8rem;"><i class="fas fa-arrow-left"></i> Vazgeç</a>
        </div>
    </div>
</body>
</html>
