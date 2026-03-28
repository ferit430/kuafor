<?php
// customer/index.php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
require_once dirname(__DIR__) . '/backend/config/db.php';

$customer_id = $_SESSION['customer_id'];

// Kullanıcı bilgilerini al (Konum dahil)
$stmt = $pdo->prepare("SELECT city, district FROM users WHERE id = ?");
$stmt->execute([$customer_id]);
$currentUser = $stmt->fetch();
$userCity = $currentUser['city'] ?? '';
$userDistrict = $currentUser['district'] ?? '';

// Konum Güncelleme İşlemi
if (isset($_POST['update_location'])) {
    $newCity = $_POST['city'] ?? '';
    $newDistrict = $_POST['district'] ?? '';
    $stmt = $pdo->prepare("UPDATE users SET city = ?, district = ? WHERE id = ?");
    $stmt->execute([$newCity, $newDistrict, $customer_id]);
    header("Location: index.php?location_updated=1");
    exit;
}

// Randevu İptal Etme
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    // Güvenlik kontrolü: Randevu bu müşteriye mi ait ve iptal edilebilir durumda mı?
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'");
    $stmt->execute([$cancel_id, $customer_id]);
    header("Location: index.php?cancelled=1");
    exit;
}

// Mevcut randevuları al (Çoklu hizmet destekli - Yorum kontrolü eklendi)
$stmt = $pdo->prepare("SELECT a.*, s.name as salon_name, st.name as staff_name,
                             GROUP_CONCAT(sv.name SEPARATOR ', ') as service_names,
                             r.id as review_id
                       FROM appointments a 
                       JOIN salons s ON a.salon_id = s.id 
                       JOIN staff st ON a.staff_id = st.id 
                       LEFT JOIN appointment_services aps ON a.id = aps.appointment_id
                       LEFT JOIN services sv ON aps.service_id = sv.id 
                       LEFT JOIN reviews r ON a.id = r.appointment_id
                       WHERE a.customer_id = ? 
                       GROUP BY a.id
                       ORDER BY a.appointment_date DESC, a.start_time DESC");
$stmt->execute([$customer_id]);
$myAppointments = $stmt->fetchAll();

// Salonları al (Kullanıcının konumuna göre öncelikli - Puanlar eklendi)
$salons_query = "SELECT s.*, 
                    (SELECT GROUP_CONCAT(name SEPARATOR ' ') FROM staff WHERE salon_id = s.id) as staff_names,
                    (SELECT GROUP_CONCAT(name SEPARATOR ' ') FROM services WHERE salon_id = s.id) as service_names,
                    (SELECT AVG(rating) FROM reviews WHERE salon_id = s.id) as avg_rating,
                    (SELECT COUNT(id) FROM reviews WHERE salon_id = s.id) as review_count
                 FROM salons s 
                 WHERE s.status = 'approved' ";

if (!empty($userCity)) {
    if (!empty($userDistrict)) {
        // Aynı ilçe ve ildeki salonları getir
        $salons_query .= " AND (s.city = '$userCity' AND s.district = '$userDistrict') ";
    } else {
        // Sadece il bazlı
        $salons_query .= " AND s.city = '$userCity' ";
    }
}

$salons_query .= " ORDER BY s.name ASC";
$salons = $pdo->query($salons_query)->fetchAll();

// Eğer konum bazlı sonuç çıkmazsa tümünü göster (Opsiyonel: Kullanıcı bilgilendirilebilir)
if (empty($salons)) {
    $salons = $pdo->query("SELECT s.*, 
                                (SELECT GROUP_CONCAT(name SEPARATOR ' ') FROM staff WHERE salon_id = s.id) as staff_names,
                                (SELECT GROUP_CONCAT(name SEPARATOR ' ') FROM services WHERE salon_id = s.id) as service_names,
                                (SELECT AVG(rating) FROM reviews WHERE salon_id = s.id) as avg_rating,
                                (SELECT COUNT(id) FROM reviews WHERE salon_id = s.id) as review_count
                         FROM salons s 
                         WHERE s.status = 'approved' 
                         ORDER BY s.name ASC")->fetchAll();
}

// Favori salonları al (Puanlar eklendi)
$stmt = $pdo->prepare("SELECT s.*, 
                             (SELECT AVG(rating) FROM reviews WHERE salon_id = s.id) as avg_rating,
                             (SELECT COUNT(id) FROM reviews WHERE salon_id = s.id) as review_count
                       FROM salons s JOIN favorites f ON s.id = f.salon_id WHERE f.user_id = ?");
$stmt->execute([$customer_id]);
$favorites = $stmt->fetchAll();

// Site ayarlarını çek
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_title'] ?? 'Berberim'); ?> - Randevularım</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/turkey_data.js"></script>
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-card { background: var(--mac-glass); border: 1px solid var(--mac-border); border-radius: 24px; padding: 30px; width: 400px; text-align: center; }
        /* Review Modal Styles */
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; margin: 20px 0; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5rem; color: rgba(255,255,255,0.1); cursor: pointer; transition: 0.2s; }
        .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #FFD60A; }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header>
                <div>
                    <h1>Hoş geldin, <?php echo htmlspecialchars($_SESSION['customer_name']); ?></h1>
                    <div style="font-size: 0.85rem; color: var(--mac-text-dim); margin-top: 5px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--mac-blue);"></i> 
                        Konum: <?php echo $userCity ? htmlspecialchars($userCity . ($userDistrict ? ' / ' . $userDistrict : '')) : 'Belirlenmedi'; ?> 
                        <a href="javascript:void(0)" onclick="openLocationModal()" style="color: var(--mac-blue); text-decoration: none; margin-left: 10px; font-weight: 500;">[Değiştir]</a>
                    </div>
                </div>
                <a href="logout.php" style="color: #FF453A; text-decoration: none; font-weight: 500;">Çıkış Yap</a>
            </header>

            <?php if (isset($_GET['cancelled'])): ?>
                <div style="background: rgba(255, 69, 58, 0.1); color: #FF453A; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255, 69, 58, 0.2);">
                    <i class="fas fa-info-circle"></i> Randevunuz başarıyla iptal edildi.
                </div>
            <?php endif; ?>

            <!-- Review Modal -->
            <div id="reviewModal" class="modal">
                <div class="modal-card" style="width: 450px; max-width: 95%;">
                    <h3 id="reviewTitle" style="margin-bottom: 5px;">Salon Değerlendir</h3>
                    <p style="color: var(--mac-text-dim); font-size: 0.9rem; margin-bottom: 20px;">Hizmet kalitesini puanlayın ve deneyiminizi paylaşın.</p>
                    
                    <form id="reviewForm">
                        <input type="hidden" name="appointment_id" id="rev_app_id">
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5"><label for="star5" class="fas fa-star"></label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4" class="fas fa-star"></label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3" class="fas fa-star"></label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2" class="fas fa-star"></label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1" class="fas fa-star"></label>
                        </div>
                        <textarea name="comment" class="form-control" rows="4" placeholder="Deneyiminizi buraya yazın..." style="background: rgba(255,255,255,0.05); margin-bottom: 20px;"></textarea>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="button" onclick="closeReviewModal()" class="btn-glass" style="flex:1; border:none; background: rgba(255,255,255,0.05);">Vazgeç</button>
                            <button type="submit" class="btn-primary" style="flex:2;">GÖNDER</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- 1. Sütun: Randevularım -->
                <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 20px; border: 1px solid var(--mac-border);">
                    <h2 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-calendar-alt"></i> Randevularım</h2>
                    <?php if (empty($myAppointments)): ?>
                        <div style="background: rgba(255,255,255,0.03); padding: 40px 20px; border-radius: 20px; text-align: center; color: var(--mac-text-dim);">
                            <i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 20px; display: block; opacity: 0.3;"></i>
                            Henüz bir randevunuz bulunmuyor.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Salon</th>
                                        <th>Hizmetler</th>
                                        <th>Tarih & Saat</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myAppointments as $app): 
                                        $status_color = 'rgba(255,255,255,0.1)';
                                        if ($app['status'] == 'completed') $status_color = 'rgba(52, 199, 89, 0.2)';
                                        if ($app['status'] == 'cancelled') $status_color = 'rgba(255, 69, 58, 0.2)';
                                        if ($app['status'] == 'pending') $status_color = 'rgba(255, 159, 10, 0.2)';
                                        if ($app['status'] == 'confirmed') $status_color = 'rgba(10, 132, 255, 0.2)';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($app['salon_name']); ?></strong></td>
                                        <td style="font-size: 0.8rem;"><?php echo htmlspecialchars($app['service_names'] ?: 'Hizmet Bilgisi Yok'); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($app['appointment_date'])); ?> <?php echo substr($app['start_time'], 0, 5); ?></td>
                                        <td>
                                            <?php if ($app['status'] == 'pending'): ?>
                                                <a href="?cancel_id=<?php echo $app['id']; ?>" class="btn-primary" style="background: #FF453A; padding: 4px 8px; font-size: 0.7rem; text-decoration: none; width: auto;" onclick="return confirm('Randevuyu iptal etmek istediğinize emin misiniz?')">İPTAL</a>
                                            <?php elseif ($app['status'] == 'completed' && !$app['review_id']): ?>
                                                <button onclick="openReviewModal(<?php echo $app['id']; ?>, '<?php echo addslashes($app['salon_name']); ?>')" 
                                                        style="background: #34C759; color: #fff; border:none; padding: 4px 8px; border-radius: 8px; font-size: 0.7rem; cursor: pointer; font-weight: 600;">DEĞERLENDİR</button>
                                            <?php else: ?>
                                                <span style="background: <?php echo $status_color; ?>; padding: 4px 8px; border-radius: 8px; font-size: 0.7rem;">
                                                    <?php 
                                                        $st_labels = ['pending' => 'Bekliyor', 'confirmed' => 'Onaylandı', 'completed' => 'Bitti', 'cancelled' => 'İptal'];
                                                        echo $st_labels[$app['status']] ?? $app['status'];
                                                        if ($app['review_id']) echo ' <i class="fas fa-star" style="color: #FFD60A; font-size: 0.6rem;"></i>';
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Sütun: Favoriler -->
                <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 20px; border: 1px solid var(--mac-border);">
                    <h2 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-heart" style="color: #FF2D55;"></i> Favorilerim</h2>
                    <?php if (empty($favorites)): ?>
                        <p style="text-align: center; color: var(--mac-text-dim); padding: 40px 0;">Henüz favoriniz yok.</p>
                    <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($favorites as $fav): ?>
                        <div class="card" style="padding: 15px; border-left: 4px solid #FF2D55; background: rgba(255,255,255,0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <h4 style="margin-bottom: 5px; font-size: 0.95rem;"><?php echo htmlspecialchars($fav['name']); ?></h4>
                                <?php if($fav['review_count'] > 0): ?>
                                    <div style="color: #FFD60A; font-size: 0.75rem;"><i class="fas fa-star"></i> <?php echo round($fav['avg_rating'], 1); ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <small style="color: var(--mac-text-dim);"><?php echo htmlspecialchars($fav['city']); ?></small>
                                <a href="salon_detail.php?id=<?php echo $fav['id']; ?>" class="btn-primary" style="width: auto; padding: 5px 12px; font-size: 0.75rem; text-decoration: none;">GİT</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 3. Sütun: Tüm Salonlar & Arama -->
                <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 20px; border: 1px solid var(--mac-border);">
                    <h2 style="margin-bottom: 15px; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-store"></i> Salon Keşfet</h2>
                    
                    <div style="position: relative; margin-bottom: 20px;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 12px; color: var(--mac-text-dim);"></i>
                        <input type="text" id="salonSearch" placeholder="Salon, personel, hizmet ara..." 
                               style="width: 100%; padding: 10px 15px 10px 40px; background: rgba(255,255,255,0.05); border: 1px solid var(--mac-border); border-radius: 12px; color: #fff; outline: none; transition: 0.3s; font-size: 0.9rem;">
                    </div>

                    <div id="salonList" style="display: flex; flex-direction: column; gap: 12px; max-height: 500px; overflow-y: auto;">
                        <?php foreach ($salons as $salon): 
                            $search_data = strtolower(htmlspecialchars($salon['name'] . ' ' . $salon['city'] . ' ' . $salon['address'] . ' ' . $salon['staff_names'] . ' ' . $salon['service_names']));
                        ?>
                        <div class="card salon-card" data-search="<?php echo $search_data; ?>" style="padding: 15px; background: rgba(255,255,255,0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <h4 style="margin-bottom: 5px; font-size: 0.95rem;"><?php echo htmlspecialchars($salon['name']); ?></h4>
                                <?php if($salon['review_count'] > 0): ?>
                                    <div style="color: #FFD60A; font-size: 0.75rem;"><i class="fas fa-star"></i> <?php echo round($salon['avg_rating'], 1); ?> (<?php echo $salon['review_count']; ?>)</div>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <small style="color: var(--mac-text-dim);"><?php echo htmlspecialchars($salon['city']); ?> • 
                                    <span style="color: var(--mac-blue); font-size: 0.7rem; font-weight: 500;">
                                        <?php 
                                            $type_labels = ['male' => 'Erkek', 'female' => 'Kadın', 'child' => 'Çocuk'];
                                            echo $type_labels[$salon['type']] ?? $salon['type'];
                                        ?>
                                    </span>
                                </small>
                                <a href="salon_detail.php?id=<?php echo $salon['id']; ?>" class="btn-primary" style="width: auto; padding: 5px 12px; font-size: 0.75rem; text-decoration: none;">DETAY</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <script>
            function openReviewModal(appId, salonName) {
                document.getElementById('rev_app_id').value = appId;
                document.getElementById('reviewTitle').innerText = salonName + ' Değerlendir';
                document.getElementById('reviewModal').classList.add('active');
            }

            function closeReviewModal() {
                document.getElementById('reviewModal').classList.remove('active');
                document.getElementById('reviewForm').reset();
            }

            document.getElementById('reviewForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('add_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Yorumunuz için teşekkürler!');
                        location.reload();
                    } else {
                        alert(data.message || 'Bir hata oluştu.');
                    }
                });
            });

            document.getElementById('salonSearch').addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.salon-card');
                
                cards.forEach(card => {
                    const searchContent = card.getAttribute('data-search');
                    if (searchContent.includes(query)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            const cityUpdateSelect = document.getElementById('cityUpdate');
            const districtUpdateSelect = document.getElementById('districtUpdate');

            function initLocationData() {
                if (cityUpdateSelect && cityUpdateSelect.options.length <= 1) {
                    if (typeof turkeyData !== 'undefined') {
                        console.log("Berberim: Konum verileri yüklendi, dropdown dolduruluyor.");
                        for (let city in turkeyData) {
                            const opt = document.createElement('option');
                            opt.value = city;
                            opt.textContent = city;
                            if (city === "<?php echo $userCity; ?>") opt.selected = true;
                            cityUpdateSelect.appendChild(opt);
                        }
                    } else {
                        console.error("Berberim: turkey_data.js dosyasına erişilemedi veya yüklenemedi!");
                        // Fallback: Temel şehirleri ekle (Kritik hata anında boş kalmasın)
                        const fallback = ["Ankara", "İstanbul", "İzmir"];
                        fallback.forEach(city => {
                            const opt = document.createElement('option');
                            opt.value = city;
                            opt.textContent = city;
                            cityUpdateSelect.appendChild(opt);
                        });
                    }
                    
                    if (cityUpdateSelect.value) {
                        updateDistrictsDashboard();
                        districtUpdateSelect.value = "<?php echo $userDistrict; ?>";
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', initLocationData);

            function updateDistrictsDashboard() {
                const city = cityUpdateSelect.value;
                districtUpdateSelect.innerHTML = '<option value="">Tüm İlçeler</option>';
                
                if (city && typeof turkeyData !== 'undefined' && turkeyData[city]) {
                    turkeyData[city].forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d;
                        opt.textContent = d;
                        districtUpdateSelect.appendChild(opt);
                    });
                }
            }

            function openLocationModal() {
                initLocationData(); // Modal açılırken tekrar dene (garanti olsun)
                document.getElementById('locationModal').classList.add('active');
            }

            function closeLocationModal() {
                document.getElementById('locationModal').classList.remove('active');
            }
            </script>
        </div>
    </div>

    <!-- Location Modal -->
    <div id="locationModal" class="modal">
        <div class="modal-card">
            <h3 style="margin-bottom: 20px;">Konum Seçin</h3>
            <form method="POST">
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <select name="city" id="cityUpdate" class="form-control" onchange="updateDistrictsDashboard()" required>
                        <option value="">İl Seçin</option>
                    </select>
                    <select name="district" id="districtUpdate" class="form-control">
                        <option value="">Tüm İlçeler</option>
                    </select>
                    <button type="submit" name="update_location" class="btn-primary">GÜNCELLE</button>
                    <button type="button" onclick="closeLocationModal()" style="background: none; border: none; color: var(--mac-text-dim); cursor: pointer; font-size: 0.85rem;">Vazgeç</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
