<?php
// tmp/test_api.php
$_GET['date'] = '2026-03-30';
$_GET['salon_id'] = '1';
$_GET['staff_id'] = '1';
$_GET['service_ids'] = '1,2';

require 'backend/api/available_slots.php';
?>
