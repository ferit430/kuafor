<?php
// tmp/migrate_notifications.php
require 'backend/config/db.php';
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT, 
    title VARCHAR(100), 
    message TEXT, 
    type VARCHAR(20) DEFAULT 'info', 
    is_read TINYINT DEFAULT 0, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);
echo "Notifications table created successfully.\n";
?>
