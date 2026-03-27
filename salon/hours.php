<?php
// salon/hours.php - Salon Working Hours Management
session_start();
if (!isset($_SESSION['salon_owner_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

$owner_id = $_SESSION['salon_owner_id'];
$message = "";

// Salon bilgisini al
$stmt = $pdo->prepare("SELECT * FROM salons WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$salon = $stmt->fetch();

if (!$salon) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $opening = $_POST['opening_time'];
    $closing = $_POST['closing_time'];
    $days = isset($_POST['days']) ? implode(',', $_POST['days']) : '';
    
    $stmt = $pdo->prepare("UPDATE salons SET opening_time = ?, closing_time = ?, working_days = ? WHERE id = ?");
    if ($stmt->execute([$opening, $closing, $days, $salon['id']])) {
        $message = "Çalışma saatleri başarıyla güncellendi.";
        $salon['opening_time'] = $opening;
        $salon['closing_time'] = $closing;
        $salon['working_days'] = $days;
    } else {
        $message = "Bir hata oluştu.";
    }
}

$current_days = explode(',', $salon['working_days'] ?? '1,2,3,4,5,6');
$day_names = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 0 => 'Pazar'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çalışma Saatleri - <?php echo htmlspecialchars($salon['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="overlay">
        <div class="container" style="max-width: 600px;">
            <header>
                <h1>Çalışma Saatleri</h1>
            </header>

            <?php include 'navbar.php'; ?>

            <?php if ($message): ?>
                <div style="background: rgba(46, 204, 113, 0.2); padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(46, 204, 113, 0.3); color: #2ecc71;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label style="display:block; margin-bottom:10px; font-weight: 500;">Açılış Saati</label>
                        <input type="time" name="opening_time" class="form-control" value="<?php echo substr($salon['opening_time'] ?? '09:00:00', 0, 5); ?>" required>
                    </div>
                    <div class="form-group">
                        <label style="display:block; margin-bottom:10px; font-weight: 500;">Kapanış Saati</label>
                        <input type="time" name="closing_time" class="form-control" value="<?php echo substr($salon['closing_time'] ?? '20:00:00', 0, 5); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:block; margin-bottom:15px; font-weight: 500;">Çalışma Günleri</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <?php foreach ($day_names as $num => $name): ?>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: rgba(255,255,255,0.05); padding: 10px; border-radius: 10px; border: 1px solid var(--mac-border);">
                            <input type="checkbox" name="days[]" value="<?php echo $num; ?>" <?php echo in_array($num, $current_days) ? 'checked' : ''; ?>>
                            <?php echo $name; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 30px;">AYARLARI KAYDET</button>
            </form>
        </div>
    </div>
</body>
</html>
