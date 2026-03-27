<?php
// admin/login.php
session_start();

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Şifre: "1"
    if ($password === '1') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Hatalı şifre!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Girişi - Berberim</title>
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
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .user-avatar {
            width: 85px; height: 85px;
            background: rgba(255,255,255,0.1);
            border: 1px solid var(--mac-border);
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex; align-items: center; justify-content: center;
            font-size: 35px; color: #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        h2 { color: #fff; font-size: 20px; font-weight: 500; margin-bottom: 30px; letter-spacing: -0.5px; opacity: 0.9; }
        .alert {
            background: rgba(231, 76, 60, 0.2);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        .footer-links { margin-top: 30px; font-size: 13px; color: rgba(255,255,255,0.5); }
        .footer-links a { color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="user-avatar">
            <i class="fas fa-user-lock"></i>
        </div>
        <h2>Admin Girişi</h2>
        
        <?php if ($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Şifre" required autofocus>
            </div>
            <button type="submit" class="btn-primary">Giriş Yap</button>
        </form>

        <div class="footer-links">
            <a href="../index.php"><i class="fas fa-arrow-left mr-1"></i> Siteye Dön</a>
        </div>
    </div>
</body>
</html>
