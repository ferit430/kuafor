<?php
// salon/login.php
session_start();
require_once '../backend/config/db.php';

if (isset($_SESSION['salon_owner_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND (role = 'salon_owner' OR role = 'admin')");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['salon_owner_id'] = $user['id'];
        $_SESSION['salon_owner_name'] = $user['name'];
        
        // Salon ID'sini de oturuma ekle
        $sStmt = $pdo->prepare("SELECT id FROM salons WHERE owner_id = ?");
        $sStmt->execute([$user['id']]);
        $salon = $sStmt->fetch();
        if ($salon) {
            $_SESSION['salon_id'] = $salon['id'];
        }

        header("Location: index.php");
        exit;
    } else {
        $error = "E-posta veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Salon Yönetimi Girişi - Berberim</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-card {
            width: 380px;
            padding: 40px;
            background: var(--mac-glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--mac-border);
            border-radius: 28px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            text-align: center;
        }
        .user-avatar {
            width: 85px; height: 85px;
            background: rgba(255,255,255,0.1);
            border: 1px solid var(--mac-border);
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex; align-items: center; justify-content: center;
            font-size: 35px; color: #fff;
        }
        h2 { color: #fff; font-size: 20px; font-weight: 500; margin-bottom: 30px; }
        .alert { background: rgba(231, 76, 60, 0.2); color: #ff6b6b; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(231, 76, 60, 0.3); }
        .footer-links { margin-top: 30px; font-size: 13px; color: rgba(255,255,255,0.5); }
        .footer-links a { color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="margin-bottom: 20px;"><img src="../<?php echo htmlspecialchars($settings['site_logo'] ?? 'assets/img/logo.png'); ?>" alt="Logo" style="height: <?php echo htmlspecialchars($settings['logo_height'] ?? 100); ?>px;"></div>
        <h2>İşletme Girişi</h2>
        
        <?php if ($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="E-posta Adresi" required autofocus>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Şifre" required>
            </div>
            <button type="submit" class="btn-primary">Giriş Yap</button>
        </form>

        <div class="footer-links">
            <p style="margin-bottom: 15px;">Hesabınız yok mu? <a href="register.php" style="font-weight: 600; color: #fff;">Hemen Kaydol</a></p>
            <a href="../index.php" style="font-size: 0.8rem; opacity: 0.7;"><i class="fas fa-arrow-left"></i> Siteye Dön</a>
        </div>
    </div>
</body>
</html>
