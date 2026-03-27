<?php
// salon/staff.php
session_start();
if (!isset($_SESSION['salon_owner_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

$owner_id = $_SESSION['salon_owner_id'];
$stmt = $pdo->prepare("SELECT id FROM salons WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$salon = $stmt->fetch();
$salon_id = $salon['id'];

// Personel Ekleme (Fotoğraf Destekli)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = $_POST['name'];
    $expertise = $_POST['expertise'];
    $opening = !empty($_POST['opening_time']) ? $_POST['opening_time'] : null;
    $closing = !empty($_POST['closing_time']) ? $_POST['closing_time'] : null;
    
    $image_path = null;
    if (isset($_FILES['staff_image']) && $_FILES['staff_image']['error'] === 0) {
        $upload_dir = '../assets/img/staff/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['staff_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('staff_') . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $target_file)) {
            $image_path = 'assets/img/staff/' . $file_name;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO staff (salon_id, name, expertise, image_path, opening_time, closing_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$salon_id, $name, $expertise, $image_path, $opening, $closing]);
}

// Personel Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $s_id = $_POST['staff_id'];
    $name = $_POST['name'];
    $expertise = $_POST['expertise'];
    $opening = !empty($_POST['opening_time']) ? $_POST['opening_time'] : null;
    $closing = !empty($_POST['closing_time']) ? $_POST['closing_time'] : null;
    
    // Mevcut bilgileri al (Eski fotoğrafı silmek için)
    $stmt = $pdo->prepare("SELECT image_path FROM staff WHERE id = ?");
    $stmt->execute([$s_id]);
    $old_img = $stmt->fetchColumn();

    $image_path = $old_img;
    if (isset($_FILES['staff_image']) && $_FILES['staff_image']['error'] === 0) {
        $upload_dir = '../assets/img/staff/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['staff_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('staff_') . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $target_file)) {
            $image_path = 'assets/img/staff/' . $file_name;
            // Eski fotoğrafı sil
            if ($old_img && file_exists('../' . $old_img)) unlink('../' . $old_img);
        }
    }
    
    $stmt = $pdo->prepare("UPDATE staff SET name=?, expertise=?, image_path=?, opening_time=?, closing_time=? WHERE id=?");
    $stmt->execute([$name, $expertise, $image_path, $opening, $closing, $s_id]);
}

// İzin Günü Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_off_day'])) {
    $s_id = $_POST['staff_id'];
    $date = $_POST['off_date'];
    $desc = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO staff_off_days (staff_id, off_date, description) VALUES (?, ?, ?)");
    $stmt->execute([$s_id, $date, $desc]);
}

// İzin Günü Silme
if (isset($_GET['delete_off_day'])) {
    $stmt = $pdo->prepare("DELETE FROM staff_off_days WHERE id = ?");
    $stmt->execute([$_GET['delete_off_day']]);
}

// Personel Silme
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ? AND salon_id = ?");
    $stmt->execute([$_GET['delete'], $salon_id]);
}

$stmt = $pdo->prepare("SELECT * FROM staff WHERE salon_id = ?");
$stmt->execute([$salon_id]);
$staff = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Personel Yönetimi - Berberim</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header>
                <h1>Personel Yönetimi</h1>
            </header>
            <?php include 'navbar.php'; ?>

            <div class="card" style="margin-top: 25px; margin-bottom: 35px;">
                <h3 style="margin-top:0; margin-bottom: 25px; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-user-plus"></i> Yeni Personel Ekle</h3>
                <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:block; margin-bottom:8px; font-size:0.85rem; color: var(--mac-text-dim);">Personel Adı</label>
                        <input type="text" name="name" class="form-control" placeholder="Ad Soyad" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:block; margin-bottom:8px; font-size:0.85rem; color: var(--mac-text-dim);">Uzmanlık Alanı</label>
                        <input type="text" name="expertise" class="form-control" placeholder="Örn: Saç Kesimi">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="display:block; margin-bottom:8px; font-size:0.85rem; color: var(--mac-text-dim);">Açılış (Mesai)</label>
                            <input type="time" name="opening_time" class="form-control">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="display:block; margin-bottom:8px; font-size:0.85rem; color: var(--mac-text-dim);">Kapanış (Mesai)</label>
                            <input type="time" name="closing_time" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:block; margin-bottom:8px; font-size:0.85rem; color: var(--mac-text-dim);">Personel Fotoğrafı</label>
                        <input type="file" name="staff_image" class="form-control" accept="image/*" style="padding: 8px;">
                    </div>

                    <div style="display: flex; align-items: flex-end; grid-column: span 2;">
                        <button type="submit" name="add_staff" class="btn-primary" style="width: 100%; padding: 12px;">PERSONELİ EKLE</button>
                    </div>
                </form>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--mac-border);">
                    <h3 style="margin:0; font-size: 1.1rem; font-weight: 500;"><i class="fas fa-users"></i> Mevcut Personeller</h3>
                </div>

            <table>
                <thead>
                    <tr>
                        <th width="80">Foto</th>
                        <th>Personel Adı</th>
                        <th>Uzmanlık</th>
                        <th>Mesai Saatleri</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $st): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo $st['image_path'] ?? 'assets/img/default-avatar.png'; ?>" 
                                 style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--mac-border);">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($st['name']); ?></strong><br>
                            <small style="opacity:0.6;"><?php echo date('d.m.Y', strtotime($st['created_at'])); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($st['expertise'] ?? 'Uzman'); ?></td>
                        <td>
                            <?php if ($st['opening_time'] && $st['closing_time']): ?>
                                <span style="background: rgba(10, 132, 255, 0.1); color: var(--mac-blue); padding: 4px 8px; border-radius: 6px; font-size: 0.8rem;">
                                    <?php echo substr($st['opening_time'], 0, 5); ?> - <?php echo substr($st['closing_time'], 0, 5); ?>
                                </span>
                            <?php else: ?>
                                <span style="opacity: 0.5; font-size: 0.8rem;">İşletme Saati</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?manage_staff=<?php echo $st['id']; ?>" class="btn-primary" style="background: var(--mac-blue); padding: 6px 12px; font-size: 0.8rem; text-decoration: none; display: inline-block; width: auto; margin-right: 5px;"><i class="fas fa-edit"></i> Düzenle</a>
                            <a href="?manage_off_days=<?php echo $st['id']; ?>" class="btn-primary" style="background: rgba(255,255,255,0.1); border: 1px solid var(--mac-border); padding: 6px 12px; font-size: 0.8rem; text-decoration: none; display: inline-block; width: auto; margin-right: 5px;"><i class="fas fa-calendar-alt"></i> İzinler</a>
                            <a href="?delete=<?php echo $st['id']; ?>" class="btn-primary" style="background: #FF453A; padding: 6px 12px; font-size: 0.8rem; text-decoration: none; display: inline-block; width: auto;" onclick="return confirm('Silmek istediğinize emin misiniz?')">Kaldır</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['manage_staff'])): 
        $s_id = $_GET['manage_staff'];
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$s_id]);
        $st_data = $stmt->fetch();
    ?>
    <!-- Personel Düzenleme Overlay -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; display: flex; align-items: center; justify-content: center;">
        <div class="card" style="width: 500px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-size: 1.3rem;">Personel Düzenle</h2>
                <a href="staff.php" style="color: var(--mac-text-dim); text-decoration: none;"><i class="fas fa-times"></i> Kapat</a>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="staff_id" value="<?php echo $s_id; ?>">
                
                <div style="text-align: center; margin-bottom: 25px;">
                    <img src="../<?php echo $st_data['image_path'] ?? 'assets/img/default-avatar.png'; ?>" 
                         style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--mac-blue); margin-bottom: 10px;">
                    <p style="font-size: 0.8rem; opacity: 0.6;">Profil Fotoğrafını Değiştir</p>
                    <input type="file" name="staff_image" class="form-control" accept="image/*" style="padding: 10px;">
                </div>

                <div class="form-group">
                    <label>Ad Soyad</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($st_data['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Uzmanlık Alanı</label>
                    <input type="text" name="expertise" class="form-control" value="<?php echo htmlspecialchars($st_data['expertise']); ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Açılış</label>
                        <input type="time" name="opening_time" class="form-control" value="<?php echo $st_data['opening_time']; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Kapanış</label>
                        <input type="time" name="closing_time" class="form-control" value="<?php echo $st_data['closing_time']; ?>">
                    </div>
                </div>

                <button type="submit" name="update_staff" class="btn-primary" style="padding: 12px;">Güncellemeleri Kaydet</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['manage_off_days'])): 
        $s_id = $_GET['manage_off_days'];
        $stmt = $pdo->prepare("SELECT name FROM staff WHERE id = ?");
        $stmt->execute([$s_id]);
        $st_name = $stmt->fetchColumn();
        
        $off_days = $pdo->query("SELECT * FROM staff_off_days WHERE staff_id = $s_id ORDER BY off_date DESC")->fetchAll();
    ?>
    <!-- İzin Günü Yönetimi Overlay -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; display: flex; align-items: center; justify-content: center;">
        <div class="card" style="width: 500px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-size: 1.3rem;"><?php echo htmlspecialchars($st_name); ?> - İzin Günleri</h2>
                <a href="staff.php" style="color: var(--mac-text-dim); text-decoration: none;"><i class="fas fa-times"></i> Kapat</a>
            </div>
            
            <form method="POST" style="margin-bottom: 30px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px;">
                <input type="hidden" name="staff_id" value="<?php echo $s_id; ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.8rem;">Tarih</label>
                        <input type="date" name="off_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size: 0.8rem;">Açıklama</label>
                        <input type="text" name="description" class="form-control" placeholder="Örn: Özel İzin">
                    </div>
                </div>
                <button type="submit" name="add_off_day" class="btn-primary" style="padding: 10px;">İzin Günü Ekle</button>
            </form>

            <h3 style="font-size: 1rem; margin-bottom: 15px;">Tanımlı İzinler</h3>
            <table style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Açıklama</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($off_days)): ?>
                        <tr><td colspan="3" style="text-align: center; opacity: 0.5;">İzin günü bulunmuyor.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($off_days as $od): ?>
                    <tr>
                        <td><?php echo date('d.m.Y', strtotime($od['off_date'])); ?></td>
                        <td><?php echo htmlspecialchars($od['description'] ?: '-'); ?></td>
                        <td>
                            <a href="?manage_off_days=<?php echo $s_id; ?>&delete_off_day=<?php echo $od['id']; ?>" style="color: #FF453A;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
