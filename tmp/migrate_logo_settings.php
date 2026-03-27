<?php
require_once '../backend/config/db.php';

try {
    // Check if columns exist first to be safe
    $columns = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('site_logo', $columns)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN site_logo VARCHAR(255) DEFAULT 'assets/img/logo.png'");
        echo "site_logo column added.\n";
    }
    
    if (!in_array('logo_height', $columns)) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN logo_height INT DEFAULT 40");
        echo "logo_height column added.\n";
    }

    echo "Migration completed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
