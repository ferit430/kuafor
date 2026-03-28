<?php
// customer/login.php
session_start();
require_once dirname(__DIR__) . '/backend/config/db.php';

// Site ayarlarını çek
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();

if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND role = 'customer'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['name'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_title'] ?? 'Berberim'); ?> - Müşteri Girişi</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-card { width: 380px; max-width: 95%; padding: 40px; background: var(--mac-glass); backdrop-filter: blur(25px); border: 1px solid var(--mac-border); border-radius: 28px; box-shadow: 0 25px 50px rgba(0,0,0,0.3); text-align: center; }
        .alert { background: rgba(231, 76, 60, 0.2); color: #ff6b6b; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(231, 76, 60, 0.3); }
        .footer-links { margin-top: 30px; font-size: 13px; color: var(--mac-text-dim); }
        .footer-links a { color: #fff; text-decoration: none; font-weight: 600; margin: 0 10px; }

        @media (max-width: 576px) {
            .login-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="login-card">
            <div style="margin-bottom: 20px;"><img src="../<?php echo htmlspecialchars($settings['site_logo'] ?? 'assets/img/logo.png'); ?>" alt="Logo" style="height: <?php echo htmlspecialchars($settings['logo_height'] ?? 100); ?>px;"></div>
            <h2 style="margin-bottom: 30px;">Müşteri Girişi</h2>
            
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
                <button type="submit" class="btn-primary-mac" style="width: 100%;">Giriş Yap</button>
            </form>

            <div class="footer-links">
                <p>Hesabınız yok mu? <a href="register.php">Hemen Kaydol</a></p>
                <p style="margin-top: 15px;"><a href="../index.php" style="color: var(--mac-text-dim); font-weight: 400;"><i class="fas fa-arrow-left"></i> Vazgeç</a></p>
            </div>
        </div>
    </div>
</body>
</html>
