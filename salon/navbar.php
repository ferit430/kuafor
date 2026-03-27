<?php
// salon/navbar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav style="margin-bottom: 30px; display: flex; gap: 20px; border-bottom: 1px solid var(--mac-border); padding-bottom: 5px; flex-wrap: wrap;">
    <a href="index.php" style="color: <?php echo $current_page == 'index.php' ? 'var(--mac-blue)' : 'var(--mac-text)'; ?>; text-decoration: none; font-weight: <?php echo $current_page == 'index.php' ? '600' : '400'; ?>; border-bottom: <?php echo $current_page == 'index.php' ? '2px solid var(--mac-blue)' : 'none'; ?>; padding-bottom: 10px; opacity: <?php echo $current_page == 'index.php' ? '1' : '0.7'; ?>;">Dashboard</a>
    
    <a href="appointments.php" style="color: <?php echo $current_page == 'appointments.php' ? 'var(--mac-blue)' : 'var(--mac-text)'; ?>; text-decoration: none; font-weight: <?php echo $current_page == 'appointments.php' ? '600' : '400'; ?>; border-bottom: <?php echo $current_page == 'appointments.php' ? '2px solid var(--mac-blue)' : 'none'; ?>; padding-bottom: 10px; opacity: <?php echo $current_page == 'appointments.php' ? '1' : '0.7'; ?>;">Randevular</a>
    
    <a href="profile.php" style="color: <?php echo $current_page == 'profile.php' ? 'var(--mac-blue)' : 'var(--mac-text)'; ?>; text-decoration: none; font-weight: <?php echo $current_page == 'profile.php' ? '600' : '400'; ?>; border-bottom: <?php echo $current_page == 'profile.php' ? '2px solid var(--mac-blue)' : 'none'; ?>; padding-bottom: 10px; opacity: <?php echo $current_page == 'profile.php' ? '1' : '0.7'; ?>;">Salon Profili</a>
    
    <a href="hours.php" style="color: <?php echo $current_page == 'hours.php' ? 'var(--mac-blue)' : 'var(--mac-text)'; ?>; text-decoration: none; font-weight: <?php echo $current_page == 'hours.php' ? '600' : '400'; ?>; border-bottom: <?php echo $current_page == 'hours.php' ? '2px solid var(--mac-blue)' : 'none'; ?>; padding-bottom: 10px; opacity: <?php echo $current_page == 'hours.php' ? '1' : '0.7'; ?>;">Çalışma Saatleri</a>
    
    <a href="services.php" style="color: <?php echo $current_page == 'services.php' ? 'var(--mac-blue)' : 'var(--mac-text)'; ?>; text-decoration: none; font-weight: <?php echo $current_page == 'services.php' ? '600' : '400'; ?>; border-bottom: <?php echo $current_page == 'services.php' ? '2px solid var(--mac-blue)' : 'none'; ?>; padding-bottom: 10px; opacity: <?php echo $current_page == 'services.php' ? '1' : '0.7'; ?>;">Hizmetler</a>
    
    <a href="staff.php" style="color: <?php echo $current_page == 'staff.php' ? 'var(--mac-blue)' : 'var(--mac-text)'; ?>; text-decoration: none; font-weight: <?php echo $current_page == 'staff.php' ? '600' : '400'; ?>; border-bottom: <?php echo $current_page == 'staff.php' ? '2px solid var(--mac-blue)' : 'none'; ?>; padding-bottom: 10px; opacity: <?php echo $current_page == 'staff.php' ? '1' : '0.7'; ?>;">Personel</a>
</nav>

<!-- Randevu Alarm Sistemi -->
<audio id="appointmentAlarm" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const salonId = <?php echo $_SESSION['salon_id'] ?? $salon_id ?? 'null'; ?>;
    if (!salonId) return;

    // Son görülen randevu ID'sini sakla (İlk açılışta veritabanındakini baz alır)
    let lastSeenId = localStorage.getItem('last_seen_appointment_id_' + salonId);

    function checkNewAppointments() {
        fetch(`../backend/api/salon_new_appointments.php?salon_id=${salonId}&last_id=${lastSeenId || 0}`)
            .then(response => response.json())
            .then(data => {
                if (data.new_booking) {
                    // Alarm Çal
                    const alarm = document.getElementById('appointmentAlarm');
                    alarm.play().catch(e => console.log("Ses çalmak için etkileşim gerekiyor: ", e));
                    
                    // Bildirim Göster
                    if ("Notification" in window && Notification.permission === "granted") {
                        new Notification("Yeni Randevu!", {
                            body: "Yeni bir randevu talebi aldınız.",
                            icon: "../assets/img/logo.png"
                        });
                    }

                    // ID Güncelle
                    lastSeenId = data.latest_id;
                    localStorage.setItem('last_seen_appointment_id_' + salonId, lastSeenId);
                    
                    // Sayfayı yenileme uyarısı veya dinamik güncelleme yapılabilir
                    console.log("Yeni randevu geldi! ID: " + lastSeenId);
                } else {
                    // İlk kez çalışıyorsa mevcut ID'yi kaydet ki eskilere alarm çalmasın
                    if (!lastSeenId) {
                        lastSeenId = data.latest_id;
                        localStorage.setItem('last_seen_appointment_id_' + salonId, lastSeenId);
                    }
                }
            });
    }

    // İzin iste
    if ("Notification" in window && Notification.permission !== "granted") {
        Notification.requestPermission();
    }

    // Her 30 saniyede bir kontrol et
    setInterval(checkNewAppointments, 30000);
    
    // İlk kontrolü yap
    setTimeout(checkNewAppointments, 2000);
});
</script>
