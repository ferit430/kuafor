<?php
// index.php - Dynamic High Fidelity Site
require_once __DIR__ . '/backend/config/db.php';

// Site ayarlarını çek
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_title'] ?? 'KUAFÖR RANDEVU - Randevu Sistemi'); ?></title>
    <link rel="stylesheet" href="assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* High Fidelity Styles */
        html,
        body {
            overflow-x: hidden;
            height: auto;
            min-height: 100vh;
        }

        .overlay {
            min-height: 100vh;
            height: auto;
            padding: 40px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero {
            display: flex;
            align-items: stretch;
            justify-content: center;
            gap: 0;
            padding: 0;
            min-height: 600px;
            position: relative;
            border-radius: 32px;
            background: linear-gradient(90deg, rgba(255, 75, 145, 0.2) 0%, rgba(0, 0, 0, 0) 40%, rgba(0, 0, 0, 0) 60%, rgba(0, 122, 255, 0.2) 100%);
            border: 1px solid var(--mac-border);
            overflow: hidden;
        }

        .hero-side {
            flex: 1.5;
            overflow: hidden;
            position: relative;
            transition: 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            min-height: 600px;
        }

        .hero-side img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.7;
            transition: 0.8s;
            filter: saturate(1.2);
            display: block;
        }

        .hero-side:first-child img {
            -webkit-mask-image: linear-gradient(to right, black 60%, transparent 100%);
            mask-image: linear-gradient(to right, black 60%, transparent 100%);
        }

        .hero-side:last-child img {
            -webkit-mask-image: linear-gradient(to left, black 60%, transparent 100%);
            mask-image: linear-gradient(to left, black 60%, transparent 100%);
        }

        .hero-side:hover img {
            opacity: 1;
            transform: scale(1.08);
            filter: saturate(1.5);
        }

        .hero-text {
            flex: 1.8;
            z-index: 10;
            padding: 40px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero h1 {
            font-size: 3.8rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -2px;
            background: linear-gradient(to right, #fff, #4facfe, #8e44ad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--mac-text-dim);
            line-height: 1.5;
            margin-bottom: 30px;
            margin-left: auto;
            margin-right: auto;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            gap: 15px;
            flex-wrap: wrap;
            width: 100%;
            transition: 0.3s;
        }

        .cta-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-glass {
            padding: 8px 16px;
            background: var(--mac-glass);
            border: 1px solid var(--mac-border);
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            backdrop-filter: blur(10px);
            transition: 0.3s;
            font-size: 0.8rem;
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn-primary-mac {
            padding: 8px 18px;
            background: var(--mac-blue);
            border: none;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            font-size: 0.8rem;
        }

        .btn-primary-mac:hover {
            background: #0063CC;
            transform: translateY(-2px);
        }

        .btn-download {
            padding: 18px 35px;
            background: #fff;
            color: #000;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: 0.4s;
            font-size: 1.1rem;
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }

        .btn-download:hover {
            transform: scale(1.05);
            background: #f0f0f0;
        }

        .btn-download i {
            font-size: 1.3rem;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.02);
            padding: 25px;
            border-radius: 18px;
            border: 1px solid var(--mac-border);
            transition: 0.3s;
        }

        .feature-item i {
            font-size: 1.8rem;
            color: var(--mac-blue);
            margin-bottom: 12px;
            display: block;
        }

        .feature-item h3 {
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .feature-item p {
            color: var(--mac-text-dim);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        footer {
            margin-top: 40px;
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid var(--mac-border);
            color: var(--mac-text-dim);
            font-size: 0.8rem;
        }

        @media (max-width: 1100px) {
            nav {
                justify-content: center;
                gap: 20px;
            }

            .hero-text h1 {
                font-size: 3rem;
            }
        }

        @media (max-width: 768px) {

            html,
            body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
                display: block !important;
            }

            .overlay {
                padding: 20px 0;
                align-items: flex-start;
                display: block !important;
                height: auto;
                min-height: unset;
            }

            .hero {
                flex-direction: column;
                text-align: center;
                gap: 30px;
                padding: 10px 0;
                min-height: auto;
            }

            .hero-side {
                display: none;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            nav {
                flex-direction: column;
                gap: 20px;
                margin-bottom: 30px;
                text-align: center;
            }

            .cta-buttons {
                justify-content: center;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 15px;
                margin-top: 30px;
            }

            .btn-download {
                width: 100%;
                justify-content: center;
            }

            .container {
                width: 95%;
                padding: 20px;
                max-height: none !important;
                margin: 0 auto;
                overflow: visible !important;
            }
        }
    </style>
</head>

<body>
    <div class="overlay">
        <div class="container"
            style="background: none; border: none; backdrop-filter: none; box-shadow: none; max-height: none; overflow: visible; width: 95%; max-width: 1200px; margin: 0 auto; ">

            <nav>
                <div
                    style="font-size: 1.5rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 15px; text-transform: uppercase;">
                    <img src="<?php echo htmlspecialchars($settings['site_logo'] ?? 'assets/img/logo.png'); ?>" alt="Logo" style="height: <?php echo htmlspecialchars($settings['logo_height'] ?? 40); ?>px; width: auto;">
                    <?php echo htmlspecialchars($settings['site_title'] ?? 'KUAFÖR RANDEVU'); ?>
                </div>
                <div class="cta-buttons">
                    <a href="customer/login.php" class="btn-glass"><i class="fas fa-user"></i> Müşteri Girişi</a>
                    <a href="salon/login.php" class="btn-glass"><i class="fas fa-store"></i> İşletme Girişi</a>
                    <a href="customer/register.php" class="btn-primary-mac">Hemen Kaydol</a>
                </div>
            </nav>

            <section class="hero">
                <div class="hero-side">
                    <img src="assets/img/women_hairdresser.png" alt="Kadın Kuaförü">
                </div>
                <div class="hero-text">
                    <h1 class="text-gradient">Sıra Bekleme <br> Dönemi Kapandı.</h1>
                    <p>KUAFÖR RANDEVU ile dilediğiniz salondan, dilediğiniz personelden saniyeler içinde randevu alın.
                        İşletmenizi modern teknoloji ile büyütün.</p>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                        <a href="#" class="btn-download">
                            <i class="fab fa-apple"></i>
                            <i class="fab fa-google-play"></i>
                            <i class="fas fa-mobile-alt"></i>
                            UYGULAMAYI İNDİR
                        </a>
                        <a href="salon/register.php" class="btn-glass"
                            style="display: flex; align-items: center; justify-content: center; font-size: 1.1rem; padding-left: 30px; padding-right: 30px;">Salonunu
                            Kaydet</a>
                    </div>
                </div>
                <div class="hero-side">
                    <img src="assets/img/men_hairdresser.png" alt="Erkek Kuaförü">
                </div>
            </section>

            <section class="features">
                <div class="feature-item">
                    <i class="fas fa-bolt"></i>
                    <h3>Hızlı Randevu</h3>
                    <p>Saniyeler içinde dilediğiniz saatte randevunuzu oluşturun, zamanınız size kalsın.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Çakışma Önleme</h3>
                    <p>Gelişmiş algoritmamız sayesinde asla aynı saatte iki randevu oluşmaz.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-star"></i>
                    <h3>Gerçek Yorumlar</h3>
                    <p>Sadece hizmet alan müşterilerin yorumlarını inceleyerek seçiminizi yapın.</p>
                </div>
            </section>

            <footer>
                <div style="margin-bottom: 20px; display: flex; justify-content: center; gap: 20px; font-size: 1.5rem;">
                    <?php if (!empty($settings['instagram_url'])): ?>
                        <a href="<?php echo $settings['instagram_url']; ?>" target="_blank" style="color: #E1306C;"><i
                                class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?php echo $settings['facebook_url']; ?>" target="_blank" style="color: #1877F2;"><i
                                class="fab fa-facebook"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['twitter_url'])): ?>
                        <a href="<?php echo $settings['twitter_url']; ?>" target="_blank" style="color: #1DA1F2;">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?php echo $settings['youtube_url']; ?>" target="_blank" style="color: #FF0000;"><i
                                class="fab fa-youtube"></i></a>
                    <?php endif; ?>
                </div>
                <div style="margin-bottom: 10px;">
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['site_phone']); ?> |
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['site_email']); ?>
                </div>
                <div>
                    <?php echo htmlspecialchars($settings['footer_text'] ?? '© 2026 KUAFÖR RANDEVU Teknolojileri. Tüm hakları saklıdır.'); ?>
                </div>
            </footer>
        </div>
    </div>
</body>

</html>