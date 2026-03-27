<?php
require_once 'c:/xampp/htdocs/Berberim/Berberim/backend/config/db.php';

try {
    // Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_title VARCHAR(255) DEFAULT 'Berberim - Randevu Sistemi',
        site_description TEXT,
        site_email VARCHAR(100),
        site_phone VARCHAR(20),
        site_address TEXT,
        seo_keywords TEXT,
        seo_author VARCHAR(100) DEFAULT 'Berberim',
        google_analytics TEXT,
        footer_text TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insert default settings if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO settings (site_title, site_description) VALUES ('Berberim', 'Profesyonel Berber ve Kuaför Randevu Sistemi')");
    }

    echo "Settings table created/verified successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
