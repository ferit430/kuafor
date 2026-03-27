<?php
// backend/middleware/auth_token.php
require_once __DIR__ . '/../helpers/jwt_helper.php';
require_once __DIR__ . '/../helpers/response_helper.php';

function authenticate() {
    $authHeader = null;
    
    if (isset($_SERVER['Authorization'])) {
        $authHeader = $_SERVER['Authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } else {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }

    if (!$authHeader) {
        sendError(401, "Authorization header missing");
    }
    $t = explode(" ", $authHeader);
    if ($t[0] !== 'Bearer' || !isset($t[1])) {
        sendError(401, "Invalid token format");
    }

    $token = $t[1];
    $userData = JWTHelper::validate($token);

    if (!$userData) {
        sendError(401, "Invalid or expired token");
    }

    return $userData;
}
?>
