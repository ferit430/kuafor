<?php
// customer/book.php - Advanced Multi-Service & Staff Hours Implementation
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

// AJAX İsteği Kontrolü (Slotları Yenile)
if (isset($_GET['ajax_slots'])) {
    header('Content-Type: application/json');
    $s_id = $_GET['salon_id'];
    $staff_id = $_GET['staff_id'];
    $date = $_GET['date'];
    $service_ids = !empty($_GET['services']) ? array_filter(explode(',', $_GET['services'])) : [];
    
    if (!$staff_id || empty($service_ids)) {
        echo json_encode(['error' => 'Geçersiz parametre']);
        exit;
    }

    // Seçili personelin çalışma saatleri
    $stmt = $pdo->prepare("SELECT opening_time, closing_time FROM staff WHERE id = ?");
    $stmt->execute([$staff_id]);
    $st_info = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT opening_time, closing_time FROM salons WHERE id = ?");
    $stmt->execute([$s_id]);
    $sl_info = $stmt->fetch();

    $opening = ($st_info && $st_info['opening_time']) ? $st_info['opening_time'] : ($sl_info['opening_time'] ?? '09:00:00');
    $closing = ($st_info && $st_info['closing_time']) ? $st_info['closing_time'] : ($sl_info['closing_time'] ?? '20:00:00');

    // Randevuları al
    $stmt = $pdo->prepare("SELECT start_time, end_time FROM appointments WHERE staff_id = ? AND appointment_date = ? AND status != 'cancelled'");
    $stmt->execute([$staff_id, $date]);
    $booked = $stmt->fetchAll();

    // İzin Günü Kontrolü
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff_off_days WHERE staff_id = ? AND off_date = ?");
    $stmt->execute([$staff_id, $date]);
    $is_off = $stmt->fetchColumn() > 0;

    if ($is_off) {
        echo json_encode(['slots' => [], 'message' => 'Personel bu tarihte izinlidir.']);
        exit;
    }

    // Toplam süre
    $in = str_repeat('?,', count($service_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT SUM(duration_minutes) FROM services WHERE id IN ($in)");
    $stmt->execute($service_ids);
    $total_min = $stmt->fetchColumn() ?: 30;

    function generateSlotsAJAX($opening, $closing, $booked, $total_duration, $selected_date) {
        $slots = [];
        $start = strtotime($opening);
        $end = strtotime($closing);
        $interval = 30 * 60;
        $duration_sec = $total_duration * 60;

        for ($i = $start; $i + $duration_sec <= $end; $i += $interval) {
            $slot_start = date('H:i:s', $i);
            $slot_end = date('H:i:s', $i + $duration_sec);
            $is_booked = false;
            foreach ($booked as $b) {
                if ($slot_start < $b['end_time'] && $slot_end > $b['start_time']) {
                    $is_booked = true; break;
                }
            }
            $is_past = ($selected_date == date('Y-m-d') && $i < time());
            if (!$is_booked && !$is_past) {
                $slots[] = ['time' => date('H:i', $i), 'full' => $slot_start];
            }
        }
        return $slots;
    }

    echo json_encode(['slots' => generateSlotsAJAX($opening, $closing, $booked, $total_min, $date)]);
    exit;
}

$salon_id = $_GET['salon_id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_staff = $_GET['staff_id'] ?? null;
$selected_services = !empty($_GET['services']) ? array_filter(explode(',', $_GET['services'])) : [];

if (!$salon_id) { header("Location: index.php"); exit; }

// Salon bilgisi
$stmt = $pdo->prepare("SELECT * FROM salons WHERE id = ?");
$stmt->execute([$salon_id]);
$salon = $stmt->fetch();

// Personel Listesi
$all_staff = $pdo->query("SELECT * FROM staff WHERE salon_id = $salon_id")->fetchAll();
$services = $pdo->query("SELECT * FROM services WHERE salon_id = $salon_id")->fetchAll();

$message = ""; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $post_services = $_POST['services'] ?? [];
    $staff_id = $_POST['staff_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    if (empty($post_services)) {
        $message = "Lütfen en az bir hizmet seçiniz.";
    } else {
        $in = str_repeat('?,', count($post_services) - 1) . '?';
        $stmt = $pdo->prepare("SELECT SUM(duration_minutes) as total_duration, SUM(price) as total_price FROM services WHERE id IN ($in)");
        $stmt->execute($post_services);
        $totals = $stmt->fetch();
        
        $start_time = $time;
        $end_time = date('H:i:s', strtotime("+".$totals['total_duration']." minutes", strtotime($start_time)));
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO appointments (customer_id, salon_id, staff_id, service_id, appointment_date, start_time, end_time, total_price) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['customer_id'], $salon_id, $staff_id, $post_services[0], $date, $start_time, $end_time, $totals['total_price']]);
            $appointment_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)");
            foreach ($post_services as $s_id) { $stmt->execute([$appointment_id, $s_id]); }
            
            $pdo->commit();
            $success = true; $message = "Randevunuz başarıyla oluşturuldu!";
        } catch (Exception $e) { $pdo->rollBack(); $message = "Bir hata oluştu: " . $e->getMessage(); }
    }
}

$day_map = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 0 => 'Pazar'];
$readable_days = [];
$current_working_days = explode(',', $salon['working_days'] ?? '1,2,3,4,5,6');
foreach($current_working_days as $d) if(isset($day_map[$d])) $readable_days[] = $day_map[$d];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelişmiş Randevu - <?php echo htmlspecialchars($salon['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 15px; }
        .slot-btn { padding: 10px; border-radius: 10px; border: 1px solid var(--mac-border); background: rgba(255,255,255,0.05); color: #fff; cursor: pointer; text-align: center; font-size: 0.9rem; transition: 0.3s; }
        .slot-btn:hover { background: var(--mac-blue); border-color: var(--mac-blue); }
        .slot-btn.selected { background: var(--mac-blue); border-color: var(--mac-blue); font-weight: 600; box-shadow: 0 0 15px rgba(10, 132, 255, 0.5); }
        
        .service-list { display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding-right: 10px; }
        .service-item { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 12px; border-radius: 12px; border: 1px solid var(--mac-border); cursor: pointer; transition: 0.2s; }
        .service-item:hover { background: rgba(255,255,255,0.06); }
        .service-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .service-item.checked { border-color: var(--mac-blue); background: rgba(10, 132, 255, 0.05); }

        .summary-card { background: linear-gradient(135deg, rgba(10, 132, 255, 0.1), rgba(48, 209, 88, 0.1)); border: 1px solid var(--mac-border); padding: 20px; border-radius: 20px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="overlay" style="align-items: flex-start; overflow-y: auto; padding: 40px 0;">
        <div class="container" style="max-width: 1000px;">
            <header>
                <h1>Gelişmiş Randevu Sistemi</h1>
                <a href="index.php" style="color: var(--mac-blue); text-decoration: none;">Geri Dön</a>
            </header>

            <?php if ($message): ?>
                <div style="background: <?php echo $success ? 'rgba(46, 204, 113, 0.2)' : 'rgba(231, 76, 60, 0.2)'; ?>; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.1); color: <?php echo $success ? '#2ecc71' : '#ff6b6b'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; min-height: 600px;">
                <!-- Sol Kolon: Seçimler -->
                <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid var(--mac-border); align-self: start;">
                    <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 20px;"><i class="fas fa-cut"></i> 1. Hizmetleri Seçin</h3>
                    <div class="service-list">
                        <?php foreach ($services as $s): ?>
                        <label class="service-item <?php echo in_array($s['id'], $selected_services) ? 'checked' : ''; ?>">
                            <input type="checkbox" class="svc-check" value="<?php echo $s['id']; ?>" 
                                   data-duration="<?php echo $s['duration_minutes']; ?>" 
                                   data-price="<?php echo $s['price']; ?>"
                                   <?php echo in_array($s['id'], $selected_services) ? 'checked' : ''; ?>
                                   onchange="updateSelection()">
                            <div style="flex-grow: 1;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($s['name']); ?></div>
                                <small style="color: var(--mac-text-dim);"><?php echo $s['duration_minutes']; ?> dk | <?php echo $s['price']; ?> TL</small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-card">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Toplam Süre:</span>
                            <span id="totalDuration" style="font-weight: 600; color: var(--mac-blue);">0 dk</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Toplam Tutar:</span>
                            <span id="totalPrice" style="font-weight: 600; color: #34C759;">0.00 TL</span>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 20px;"><i class="fas fa-calendar-day"></i> 2. Personel ve Tarih</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <select id="staffSelect" class="form-control" onchange="updateSelection()">
                                <option value="">Personel Seçin</option>
                                <?php foreach ($all_staff as $st): ?>
                                    <option value="<?php echo $st['id']; ?>" <?php echo $selected_staff == $st['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($st['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" id="dateInput" class="form-control" value="<?php echo $selected_date; ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" onchange="updateSelection()">
                        </div>

                        <!-- Personel Profil Kartı -->
                        <div id="staffProfile" style="display: none; margin-top: 20px; align-items: center; gap: 15px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 15px; border: 1px solid var(--mac-border);">
                            <img id="staffProfileImg" src="" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--mac-blue);">
                            <div>
                                <div id="staffProfileName" style="font-weight: 600; font-size: 1rem;"></div>
                                <div id="staffProfileExpertise" style="font-size: 0.85rem; color: var(--mac-text-dim);"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon: Slotlar -->
                <div id="slotsContainer" style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid var(--mac-border); align-self: start; min-height: 400px;">
                    <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 20px;"><i class="fas fa-clock"></i> 3. Uygun Saatler</h3>
                    
                    <div id="slotsContent">
                        <div style="padding: 60px 20px; text-align: center; color: var(--mac-text-dim);">
                            <i class="fas fa-info-circle" style="font-size: 2.5rem; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                            Lütfen hizmet, personel ve tarih seçiniz.
                        </div>
                    </div>

                    <form method="POST" id="bookingForm" style="margin-top: 40px; display: none; background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid var(--mac-blue);">
                        <input type="hidden" name="confirm_booking" value="1">
                        <input type="hidden" name="staff_id" id="hidden_staff_id">
                        <input type="hidden" name="date" id="hidden_date">
                        <input type="hidden" name="time" id="selectedTime">
                        <div id="hidden_services"></div>

                        <div style="text-align: center;">
                            <p style="margin-bottom: 20px; font-weight: 500;">
                                <span id="selectedTimeDisplay" style="color: var(--mac-blue); font-size: 1.2rem; font-weight: 600;"></span> 
                                saatine randevunuzu onaylıyor musunuz?
                            </p>
                            <button type="submit" class="btn-primary" style="width: 100%; padding: 15px;">RANDEVUYU ONAYLA</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 100px 20px;">
                    <i class="fas fa-check-circle" style="font-size: 6rem; color: #34C759; margin-bottom: 30px; display: block;"></i>
                    <h2 style="margin-bottom: 20px; font-size: 2.5rem;">Harika! Randevunuz Alındı.</h2>
                    <p style="margin-bottom: 40px; color: var(--mac-text-dim);">Randevu detaylarınıza panelinizden ulaşabilirsiniz.</p>
                    <a href="index.php" class="btn-primary" style="display: inline-block; text-decoration: none; width: auto; padding: 18px 50px; font-size: 1.1rem;">GİRİŞ YAP / RANDEVULARIM</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const staffData = <?php 
        $staffArr = [];
        foreach ($all_staff as $st) {
            $staffArr[$st['id']] = [
                'name' => $st['name'],
                'expertise' => $st['expertise'] ?: 'Uzman',
                'image' => $st['image_path'] ? '../' . $st['image_path'] : '../assets/img/default-avatar.png'
            ];
        }
        echo json_encode($staffArr);
    ?>;

    async function updateSelection() {
        const checks = document.querySelectorAll('.svc-check:checked');
        const services = Array.from(checks).map(c => c.value);
        const staff = document.getElementById('staffSelect').value;
        const date = document.getElementById('dateInput').value;
        const slotsContent = document.getElementById('slotsContent');
        const bookingForm = document.getElementById('bookingForm');
        
        // Özet Güncelleme
        let totalDur = 0; let totalPr = 0;
        checks.forEach(c => {
            totalDur += parseInt(c.dataset.duration);
            totalPr += parseFloat(c.dataset.price);
            c.closest('.service-item').classList.add('checked');
        });
        document.querySelectorAll('.svc-check:not(:checked)').forEach(c => c.closest('.service-item').classList.remove('checked'));

        document.getElementById('totalDuration').innerText = totalDur + ' dk';
        document.getElementById('totalPrice').innerText = totalPr.toFixed(2) + ' TL';

        // Personel Profili Güncelleme
        const staffProfile = document.getElementById('staffProfile');
        if (staff && staffData[staff]) {
            document.getElementById('staffProfileImg').src = staffData[staff].image;
            document.getElementById('staffProfileName').innerText = staffData[staff].name;
            document.getElementById('staffProfileExpertise').innerText = staffData[staff].expertise;
            staffProfile.style.display = 'flex';
        } else {
            staffProfile.style.display = 'none';
        }

        if (!staff || services.length === 0 || !date) {
            slotsContent.innerHTML = `<div style="padding: 60px 20px; text-align: center; color: var(--mac-text-dim);"><i class="fas fa-info-circle" style="font-size: 2.5rem; margin-bottom: 15px; display: block; opacity: 0.5;"></i>Lütfen hizmet, personel ve tarih seçiniz.</div>`;
            bookingForm.style.display = 'none';
            return;
        }

        // Slotları AJAX ile getir
        slotsContent.innerHTML = `<div style="padding: 60px 20px; text-align: center;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--mac-blue);"></i><p style="margin-top:10px;">Saatler yükleniyor...</p></div>`;
        
        try {
            const resp = await fetch(`?ajax_slots=1&salon_id=<?php echo $salon_id; ?>&staff_id=${staff}&date=${date}&services=${services.join(',')}`);
            const data = await resp.json();

            if (data.message) {
                slotsContent.innerHTML = `<div style="padding: 60px 20px; text-align: center; color: #ff9f0a; border: 1px dashed #ff9f0a; border-radius: 20px;"><i class="fas fa-calendar-minus" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i>${data.message}</div>`;
                return;
            }

            if (data.slots && data.slots.length > 0) {
                let html = `<p style="font-size: 0.9rem; color: var(--mac-text-dim); margin-bottom: 15px;">Toplam <strong>${totalDur} dakikalık</strong> boşluklar:</p><div class="slot-grid">`;
                data.slots.forEach(s => {
                    html += `<button type="button" class="slot-btn" onclick="selectSlot(this, '${s.full}')">${s.time}</button>`;
                });
                html += `</div>`;
                slotsContent.innerHTML = html;
            } else {
                slotsContent.innerHTML = `<div style="padding: 60px 20px; text-align: center; color: #ff6b6b; border: 1px dashed #ff6b6b; border-radius: 20px;"><i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 15px; display: block;"></i>Uygun boşluk bulunamadı.</div>`;
            }
        } catch (err) {
            slotsContent.innerHTML = `<div style="padding: 20px; color: #ff6b6b;">Bir hata oluştu: ${err}</div>`;
        }

        // Hidden form güncelleme
        document.getElementById('hidden_staff_id').value = staff;
        document.getElementById('hidden_date').value = date;
        const hiddenServices = document.getElementById('hidden_services');
        hiddenServices.innerHTML = '';
        services.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'services[]';
            input.value = id;
            hiddenServices.appendChild(input);
        });
    }

    function selectSlot(btn, time) {
        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('selectedTime').value = time;
        document.getElementById('selectedTimeDisplay').innerText = time.substring(0, 5);
        document.getElementById('bookingForm').style.display = 'block';
    }

    window.onload = () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('services') || urlParams.has('staff_id')) {
            updateSelection();
        }
    };
    </script>
</body>
</html>
