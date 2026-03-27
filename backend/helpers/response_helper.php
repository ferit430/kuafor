<?php
// backend/helpers/response_helper.php

function sendResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

function sendError($status, $message) {
    sendResponse($status, $message);
}
?>
