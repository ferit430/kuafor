<?php
// salon/profile.php - Salon Profile & Gallery Management
session_start();
if (!isset($_SESSION['salon_owner_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

$owner_id = $_SESSION['salon_owner_id'];
$message = "";
$error = "";

// Salon bilgisini al
$stmt = $pdo->prepare("SELECT * FROM salons WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$salon = $stmt->fetch();

if (!$salon) {
    header("Location: index.php");
    exit;
}

$salon_id = $salon['id'];

// Profil Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    
    // Kapak Fotoğrafı
    $cover_image = $salon['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $uploadDir = '../uploads/salons/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'cover_' . $salon_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir . $fileName)) {
            $cover_image = 'uploads/salons/' . $fileName;
        }
    }

    $stmt = $pdo->prepare("UPDATE salons SET name = ?, phone = ?, address = ?, city = ?, latitude = ?, longitude = ?, cover_image = ? WHERE id = ?");
    if ($stmt->execute([$name, $phone, $address, $city, $lat, $lng, $cover_image, $salon_id])) {
        $message = "Profil başarıyla güncellendi.";
        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM salons WHERE id = ?");
        $stmt->execute([$salon_id]);
        $salon = $stmt->fetch();
    } else {
        $error = "Bir hata oluştu.";
    }
}

// Galeriye Fotoğraf Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery'])) {
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === 0) {
        $uploadDir = '../uploads/gallery/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'gallery_' . $salon_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $uploadDir . $fileName)) {
            $image_path = 'uploads/gallery/' . $fileName;
            $stmt = $pdo->prepare("INSERT INTO salon_gallery (salon_id, image_path) VALUES (?, ?)");
            $stmt->execute([$salon_id, $image_path]);
            $message = "Görsel galeriye eklendi.";
        }
    }
}

// Galeri Silme
if (isset($_GET['delete_image'])) {
    $img_id = $_GET['delete_image'];
    $stmt = $pdo->prepare("DELETE FROM salon_gallery WHERE id = ? AND salon_id = ?");
    $stmt->execute([$img_id, $salon_id]);
    header("Location: profile.php?msg=deleted");
    exit;
}

// Galeri görsellerini al
$stmt = $pdo->prepare("SELECT * FROM salon_gallery WHERE salon_id = ? ORDER BY created_at DESC");
$stmt->execute([$salon_id]);
$gallery = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Salon Profili - <?php echo htmlspecialchars($salon['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin-top: 20px; }
        .gallery-item { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--mac-border); height: 100px; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-item .delete-btn { position: absolute; top: 5px; right: 5px; background: rgba(255, 69, 58, 0.8); color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
    <div class="overlay" style="align-items: flex-start; overflow-y: auto; padding: 40px 0;">
        <div class="container" style="max-width: 900px;">
            <header>
                <h1>Salon Profil Yönetimi</h1>
            </header>

            <?php include 'navbar.php'; ?>

            <?php if ($message || isset($_GET['msg'])): ?>
                <div style="background: rgba(46, 204, 113, 0.2); padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(46, 204, 113, 0.3); color: #2ecc71;">
                    <?php echo $message ?: ($_GET['msg'] == 'deleted' ? 'Görsel silindi.' : ''); ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px;">
                <!-- Sol Kolon: Bilgiler -->
                <div>
                    <form method="POST" enctype="multipart/form-data">
                        <h3 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-info-circle"></i> Temel Bilgiler</h3>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Salon Adı</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($salon['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Salon Telefonu</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($salon['phone'] ?? ''); ?>" placeholder="Örn: 05xx xxx xx xx">
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Açık Adres</label>
                            <textarea name="address" class="form-control" rows="3" style="height: auto;"><?php echo htmlspecialchars($salon['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Şehir</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($salon['city'] ?? ''); ?>">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                            <div class="form-group">
                                <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Enlem (Latitude)</label>
                                <input type="text" name="latitude" class="form-control" value="<?php echo htmlspecialchars($salon['latitude'] ?? ''); ?>" placeholder="41.0082">
                            </div>
                            <div class="form-group">
                                <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Boylam (Longitude)</label>
                                <input type="text" name="longitude" class="form-control" value="<?php echo htmlspecialchars($salon['longitude'] ?? ''); ?>" placeholder="28.9784">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Kapak Fotoğrafı</label>
                            <?php if ($salon['cover_image']): ?>
                                <img src="../<?php echo $salon['cover_image']; ?>" style="width: 100%; height: 120px; object-fit: cover; border-radius: 12px; margin-bottom: 10px; border: 1px solid var(--mac-border);">
                            <?php endif; ?>
                            <input type="file" name="cover_image" class="form-control">
                        </div>

                        <button type="submit" name="update_profile" class="btn-primary" style="margin-top: 20px;">BİLGİLERİ GÜNCELLE</button>
                    </form>
                </div>

                <!-- Sağ Kolon: Galeri -->
                <div>
                    <h3 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-images"></i> Salon Galerisi</h3>
                    
                    <form method="POST" enctype="multipart/form-data" style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid var(--mac-border);">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:8px; font-size: 0.85rem; color: var(--mac-text-dim);">Görsel Ekle</label>
                            <input type="file" name="gallery_image" class="form-control" required>
                        </div>
                        <button type="submit" name="upload_gallery" class="btn-primary" style="font-size: 0.85rem; padding: 10px;">YÜKLE</button>
                    </form>

                    <div class="gallery-grid">
                        <?php foreach ($gallery as $img): ?>
                        <div class="gallery-item">
                            <img src="../<?php echo $img['image_path']; ?>" alt="Galeri">
                            <a href="?delete_image=<?php echo $img['id']; ?>" class="delete-btn" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-times"></i></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
