<?php
// customer/salon_detail.php - Expanded Salon Details for Customers
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

$customer_id = $_SESSION['customer_id'];
$salon_id = $_GET['id'] ?? null;

if (!$salon_id) {
    header("Location: index.php");
    exit;
}

// Salon bilgisini al
$stmt = $pdo->prepare("SELECT * FROM salons WHERE id = ?");
$stmt->execute([$salon_id]);
$salon = $stmt->fetch();

if (!$salon) {
    header("Location: index.php");
    exit;
}

// Favori Durumu
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND salon_id = ?");
$stmt->execute([$customer_id, $salon_id]);
$is_favorite = $stmt->fetch();

// Favori İşlemi (AJAX olabilirdi ama şimdilik hızlı PHP)
if (isset($_GET['toggle_favorite'])) {
    if ($is_favorite) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND salon_id = ?");
        $stmt->execute([$customer_id, $salon_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, salon_id) VALUES (?, ?)");
        $stmt->execute([$customer_id, $salon_id]);
    }
    header("Location: salon_detail.php?id=" . $salon_id);
    exit;
}

// Galeri görsellerini al
$stmt = $pdo->prepare("SELECT * FROM salon_gallery WHERE salon_id = ? ORDER BY created_at DESC");
$stmt->execute([$salon_id]);
$gallery = $stmt->fetchAll();

// Hizmetleri al (Randevu butonu için bilgi olsun)
$services = $pdo->query("SELECT * FROM services WHERE salon_id = $salon_id LIMIT 3")->fetchAll();

// Yorum ve Puan Bilgilerini Al
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE salon_id = ?");
$stmt->execute([$salon_id]);
$rating_info = $stmt->fetch();
$avg_rating = round($rating_info['avg_rating'] ?? 0, 1);
$total_reviews = $rating_info['total_reviews'];

// Tüm yorumları detaylı al
$stmt = $pdo->prepare("SELECT r.*, u.name as customer_name 
                       FROM reviews r JOIN users u ON r.customer_id = u.id 
                       WHERE r.salon_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$salon_id]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($salon['name']); ?> - Detaylar</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .detail-card { background: var(--mac-glass); backdrop-filter: blur(40px); border: 1px solid var(--mac-border); border-radius: 28px; overflow: hidden; }
        .detail-hero { height: 250px; position: relative; }
        .detail-hero img { width: 100%; height: 100%; object-fit: cover; }
        .detail-content { padding: 30px; }
        .action-bar { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 25px 0; }
        .action-btn { background: rgba(255,255,255,0.08); border: 1px solid var(--mac-border); padding: 15px; border-radius: 18px; text-align: center; text-decoration: none; color: #fff; transition: 0.3s; }
        .action-btn:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); }
        .action-btn i { font-size: 1.2rem; display: block; margin-bottom: 8px; color: var(--mac-blue); }
        .gallery-scroll { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .gallery-scroll::-webkit-scrollbar { display: none; }
        .gallery-thumb { flex: 0 0 140px; height: 100px; border-radius: 14px; overflow: hidden; border: 1px solid var(--mac-border); cursor: pointer; transition: 0.3s; }
        .gallery-thumb:hover { transform: scale(1.05); border-color: var(--mac-blue); }
        .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .favorite-btn { position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.4); backdrop-filter: blur(10px); color: #fff; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 1px solid rgba(255,255,255,0.2); z-index: 10; }
        .favorite-btn.active { color: #FF2D55; }

        /* Modal Styles */
        #imageModal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); backdrop-filter: blur(15px); z-index: 2000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        #imageModal.active { display: flex; opacity: 1; }
        .modal-content-card { position: relative; background: var(--mac-glass); border: 1px solid var(--mac-border); border-radius: 24px; padding: 10px; max-width: 90%; max-height: 90%; transform: scale(0.9); transition: transform 0.3s ease; }
        #imageModal.active .modal-content-card { transform: scale(1); }
        #modalImage { width: 100%; max-height: 80vh; border-radius: 18px; object-fit: contain; }
        .close-modal { position: absolute; top: -50px; right: 0; color: #fff; font-size: 1.5rem; cursor: pointer; padding: 10px; }

        /* Responsive Adjustments for Salon Detail */
        @media (max-width: 768px) {
            .detail-content { padding: 20px 15px; }
            .detail-hero { height: 180px; }
            .action-bar { grid-template-columns: 1fr; gap: 10px; }
            .action-btn { padding: 12px; border-radius: 14px; }
            .action-btn i { margin-bottom: 4px; font-size: 1rem; }
            .btn-primary { width: 100% !important; margin-top: 15px; text-align: center; }
            .detail-content > div:first-child { flex-direction: column !important; }
        }
    </style>
</head>
    <style>
        body { height: auto; min-height: 100vh; overflow-y: auto; display: block; }
        .overlay { background: none; height: auto; min-height: 100vh; display: flex; justify-content: center; padding: 40px 20px; }
        .container { max-height: none; overflow: visible; width: 100%; max-width: 800px; margin: 0; }
        /* Arka planın kaymasını engellemek için body::before'u sabitleyelim */
        body::before { position: fixed !important; transform: none !important; width: 100%; height: 100%; top: 0; left: 0; z-index: -1; }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header style="border-bottom: 1px solid var(--mac-border); padding-bottom: 15px; margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <a href="index.php" style="color: var(--mac-text-dim); text-decoration: none;"><i class="fas fa-arrow-left"></i> Geri Dön</a>
                    <h1 style="font-size: 1.4rem; margin: 0;"><?php echo htmlspecialchars($salon['name']); ?></h1>
                </div>
            </header>

            <div class="detail-card">
                <div class="detail-hero" style="cursor: pointer;" onclick="openModal(this.querySelector('img').src)">
                    <img src="../<?php echo $salon['cover_image'] ? $salon['cover_image'] : 'assets/img/default-salon.jpg'; ?>" alt="Kapak">
                    <a href="?id=<?php echo $salon_id; ?>&toggle_favorite=1" class="favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>" onclick="event.stopPropagation()">
                        <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                    </a>
                </div>

                <div class="detail-content">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <h2 style="font-size: 1.5rem; margin: 0;"><?php echo htmlspecialchars($salon['name']); ?></h2>
                                <?php if($total_reviews > 0): ?>
                                    <div style="background: rgba(255, 214, 10, 0.15); color: #FFD60A; padding: 4px 8px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                                        <i class="fas fa-star"></i> <?php echo $avg_rating; ?> (<?php echo $total_reviews; ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p style="color: var(--mac-text-dim); font-size: 0.9rem;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($salon['city']); ?></p>
                        </div>
                        <a href="book.php?salon_id=<?php echo $salon_id; ?>" class="btn-primary" style="width: auto; padding: 12px 25px;">RANDEVU AL</a>
                    </div>

                    <div class="action-bar">
                        <a href="tel:<?php echo $salon['phone']; ?>" class="action-btn">
                            <i class="fas fa-phone-alt"></i>
                            <span style="font-size: 0.85rem; font-weight: 500;">Ara</span>
                        </a>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $salon['latitude'] . ',' . $salon['longitude']; ?>" target="_blank" class="action-btn">
                            <i class="fas fa-directions"></i>
                            <span style="font-size: 0.85rem; font-weight: 500;">Konuma Git</span>
                        </a>
                        <a href="javascript:void(0)" onclick="document.getElementById('gallery').scrollIntoView({behavior: 'smooth'})" class="action-btn">
                            <i class="fas fa-images"></i>
                            <span style="font-size: 0.85rem; font-weight: 500;">Galeri</span>
                        </a>
                    </div>

                    <div style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; font-weight: 500;"><i class="fas fa-info-circle"></i> Salon Hakkında</h4>
                        <p style="font-size: 0.95rem; line-height: 1.6; color: rgba(255,255,255,0.8);"><?php echo nl2br(htmlspecialchars($salon['description'] ?? 'Bu salon için henüz açıklama girilmemiş.')); ?></p>
                        <p style="margin-top: 15px; font-size: 0.9rem;"><i class="fas fa-map-marked-alt"></i> <strong>Adres:</strong> <?php echo htmlspecialchars($salon['address'] ?? 'Belirtilmemiş'); ?></p>
                    </div>

                    <?php if (!empty($gallery)): ?>
                    <div id="gallery" style="margin-top: 40px;">
                        <h4 style="margin-bottom: 15px; font-weight: 500;"><i class="fas fa-camera-retro"></i> Görseller</h4>
                        <div class="gallery-scroll">
                            <?php foreach ($gallery as $img): ?>
                            <div class="gallery-thumb" onclick="openModal('../<?php echo $img['image_path']; ?>')">
                                <img src="../<?php echo $img['image_path']; ?>" alt="Galeri">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top: 50px; border-top: 1px solid var(--mac-border); padding-top: 30px;">
                        <h4 style="margin-bottom: 25px; font-weight: 500;"><i class="fas fa-comments"></i> Müşteri Yorumları (<?php echo $total_reviews; ?>)</h4>
                        
                        <?php if(empty($reviews)): ?>
                            <p style="text-align: center; color: var(--mac-text-dim); padding: 20px 0;">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                <?php foreach($reviews as $review): ?>
                                    <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 20px; border: 1px solid var(--mac-border);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                            <div style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                                            <div style="color: #FFD60A; font-size: 0.8rem;">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p style="font-size: 0.9rem; line-height: 1.5; color: rgba(255,255,255,0.7);"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        <div style="text-align: right; margin-top: 8px;">
                                            <small style="color: var(--mac-text-dim); font-size: 0.75rem;"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" onclick="closeModal()">
        <div class="modal-content-card" onclick="event.stopPropagation()">
            <span class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i> Kapat</span>
            <img id="modalImage" src="" alt="Full View">
        </div>
    </div>

    <script>
        function openModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = src;
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    </script>
</body>
</html>
