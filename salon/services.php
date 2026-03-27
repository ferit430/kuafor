<?php
// salon/services.php
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

// Hizmet Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $stmt = $pdo->prepare("INSERT INTO services (salon_id, name, price, duration_minutes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$salon_id, $name, $price, $duration]);
}

// Hizmet Silme
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND salon_id = ?");
    $stmt->execute([$_GET['delete'], $salon_id]);
}

$stmt = $pdo->prepare("SELECT * FROM services WHERE salon_id = ?");
$stmt->execute([$salon_id]);
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hizmet Yönetimi - Berberim</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header>
                <h1>Hizmet Yönetimi</h1>
            </header>

            <?php include 'navbar.php'; ?>

            <div style="background: rgba(255,255,255,0.05); padding: 25px; border-radius: 20px; margin-bottom: 30px; border: 1px solid var(--mac-border);">
                <h3 style="margin-top:0; margin-bottom: 20px; font-size: 1.1rem; font-weight: 500;">Yeni Hizmet Ekle</h3>
                <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:block; margin-bottom:8px; font-size:0.8rem; color: var(--mac-text-dim);">Hizmet Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:block; margin-bottom:8px; font-size:0.8rem; color: var(--mac-text-dim);">Fiyat (TL)</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:block; margin-bottom:8px; font-size:0.8rem; color: var(--mac-text-dim);">Süre (Dk)</label>
                        <input type="number" name="duration" class="form-control" required>
                    </div>
                    <button type="submit" name="add_service" class="btn-primary" style="width: auto; padding-left: 30px; padding-right: 30px;">EKLE</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Hizmet Adı</th>
                        <th>Fiyat</th>
                        <th>Süre</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo number_format($s['price'], 2); ?> TL</td>
                        <td><?php echo $s['duration_minutes']; ?> Dakika</td>
                        <td>
                            <a href="?delete=<?php echo $s['id']; ?>" class="btn-primary" style="background: #FF453A; padding: 6px 15px; font-size: 0.8rem; text-decoration: none; display: inline-block; width: auto;" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
