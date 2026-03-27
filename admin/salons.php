<?php
// admin/salons.php - Business Management Module
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../backend/config/db.php';

// Arama parametresini al (Varsayılan olarak 'Çankaya' gelsin)
$search = $_GET['search'] ?? 'Çankaya';

// Pagination Ayarları
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// --- Onay Bekleyen Salonlar (Filtreli ama pagination olmadan gösterelim çünkü genelde az olurlar, yada sadece arama ile) ---
$pending_sql = "SELECT * FROM salons WHERE status = 'pending'";
$pending_params = [];
if ($search) {
    $pending_sql .= " AND (name LIKE ? OR city LIKE ? OR district LIKE ? OR phone LIKE ? OR address LIKE ?)";
    $term = "%$search%";
    $pending_params = [$term, $term, $term, $term, $term];
}
$pending_sql .= " ORDER BY created_at DESC";
$stmt_pending = $pdo->prepare($pending_sql);
$stmt_pending->execute($pending_params);
$pendingSalons = $stmt_pending->fetchAll();

// --- Tüm Salonlar (Pagination ile) ---
// Toplam kayıt sayısını bul (Arama filtresi dahil)
$count_sql = "SELECT COUNT(*) FROM salons WHERE 1=1";
$count_params = [];
if ($search) {
    $count_sql .= " AND (name LIKE ? OR city LIKE ? OR district LIKE ? OR phone LIKE ? OR address LIKE ?)";
    $count_params = [$term, $term, $term, $term, $term];
}
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($count_params);
$totalRecords = $stmt_count->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Sayfalı veriyi çek
$all_sql = "SELECT * FROM salons WHERE 1=1";
$all_params = [];
if ($search) {
    $all_sql .= " AND (name LIKE ? OR city LIKE ? OR district LIKE ? OR phone LIKE ? OR address LIKE ?)";
    $all_params = [$term, $term, $term, $term, $term];
}
$all_sql .= " ORDER BY name ASC LIMIT $perPage OFFSET $offset";
$stmt_all = $pdo->prepare($all_sql);
$stmt_all->execute($all_params);
$allSalons = $stmt_all->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşletme Yönetimi - Berberim Admin</title>
    <link rel="stylesheet" href="../assets/css/macos.css">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">Berberim <span style="color: var(--admin-blue);">Admin</span></div>
        <a href="index.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="salons.php" class="sidebar-item active"><i class="fas fa-store"></i> İşletme Yönetimi</a>
        <a href="seo.php" class="sidebar-item"><i class="fas fa-search-dollar"></i> SEO Yardımcısı</a>
        <a href="settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Site Yönetimi</a>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--admin-border);">
            <a href="logout.php" class="sidebar-item" style="color: #FF453A;"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
        </div>
    </div>

    <div class="main-content">
        <header style="margin-bottom: 30px;">
            <h1>İşletme Yönetimi</h1>
            <p style="color: var(--admin-text-dim);">Sistemdeki tüm kayıtlı ve onay bekleyen işletmeleri denetleyin.</p>
        </header>

        <!-- Arama Paneli -->
        <div class="form-card" style="margin-bottom: 50px; padding: 25px; background: rgba(255,255,255,0.03);">
            <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                <div style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--admin-text-dim);"></i>
                    <input type="text" name="search" class="form-control" style="margin-top: 0; padding-left: 50px;" placeholder="İşletme adı, şehir, ilçe veya telefon ile ara..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-premium" style="padding: 12px 30px; box-shadow: none;">ARAMA YAP</button>
                <?php if ($search): ?>
                    <a href="salons.php" class="btn-premium" style="padding: 12px 20px; background: rgba(255,255,255,0.08); text-decoration: none; box-shadow: none;">TEMİZLE</a>
                <?php endif; ?>
            </form>
        </div>

        <section style="margin-bottom: 60px;">
            <h2 style="margin-bottom: 25px; font-size: 1.3rem; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-clock" style="color: #FF9500;"></i> Onay Bekleyen İşletmeler
            </h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Salon Adı</th>
                            <th>Şehir</th>
                            <th>Belge</th>
                            <th>Tarih</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingSalons as $salon): ?>
                        <tr>
                            <td><strong style="font-size: 1rem;"><?php echo htmlspecialchars($salon['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($salon['city']); ?></td>
                            <td>
                                <?php if ($salon['certificate_path']): ?>
                                    <a href="javascript:void(0)" onclick="openModal('../<?php echo $salon['certificate_path']; ?>')" style="color: var(--admin-blue); text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-file-image"></i> Görüntüle
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-dim);">Yok</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($salon['created_at'])); ?></td>
                            <td>
                                <a href="approve_salon.php?id=<?php echo $salon['id']; ?>&action=approve" class="btn-premium" style="padding: 8px 15px; background: #34C759; font-size: 0.8rem; text-decoration: none; box-shadow: none;">ONAYLA</a>
                                <a href="approve_salon.php?id=<?php echo $salon['id']; ?>&action=reject" class="btn-premium" style="padding: 8px 15px; background: #FF453A; font-size: 0.8rem; text-decoration: none; margin-left: 5px; box-shadow: none;">REDDET</a>
                                <a href="view_salon.php?id=<?php echo $salon['id']; ?>" class="btn-premium" style="padding: 8px 15px; background: rgba(255,255,255,0.08); font-size: 0.8rem; text-decoration: none; margin-left: 5px; box-shadow: none;">DETAY</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pendingSalons)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--admin-text-dim); padding: 50px;">Bekleyen işletme bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 style="margin-bottom: 25px; font-size: 1.3rem; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-list-ul" style="color: var(--admin-blue);"></i> Tüm İşletmeler
            </h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Salon Adı</th>
                            <th>Şehir & İlçe</th>
                            <th>Durum</th>
                            <th>Kayıt</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allSalons as $salon): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($salon['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($salon['city'] . ' / ' . ($salon['district'] ?? '-')); ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo $salon['status'] == 'approved' ? 'rgba(52, 199, 89, 0.2)' : ($salon['status'] == 'pending' ? 'rgba(255, 149, 0, 0.2)' : 'rgba(255, 59, 48, 0.2)'); ?>; color: <?php echo $salon['status'] == 'approved' ? '#34C759' : ($salon['status'] == 'pending' ? '#FF9500' : '#FF3B30'); ?>;">
                                    <?php echo strtoupper($salon['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($salon['created_at'])); ?></td>
                            <td>
                                <a href="view_salon.php?id=<?php echo $salon['id']; ?>" class="btn-premium" style="padding: 8px 18px; font-size: 0.8rem; text-decoration: none;">DETAYLARI GÖR</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination UI -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 40px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>" class="page-link" title="İlk Sayfa"><i class="fas fa-angle-double-left"></i></a>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="fas fa-angle-left"></i> Geri</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">İleri <i class="fas fa-angle-right"></i></a>
                        <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>" class="page-link" title="Son Sayfa"><i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 20px; color: var(--admin-text-dim); font-size: 0.85rem;">
                Toplam <strong><?php echo $totalRecords; ?></strong> işletme bulundu. Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?>
            </div>
        </section>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="img01">
    </div>

    <script>
    function openModal(src) {
        document.getElementById("imageModal").style.display = "flex";
        document.getElementById("img01").src = src;
    }
    function closeModal() {
        document.getElementById("imageModal").style.display = "none";
    }
    </script>
</body>
</html>
