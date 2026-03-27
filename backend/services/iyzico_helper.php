<?php
// backend/services/iyzico_helper.php

class IyzicoHelper {
    public static function createPaymentTicket($appointmentId, $amount, $user) {
        // This is a placeholder for Iyzico API integration.
        // In a real scenario, you'd use the Iyzico PHP SDK here.
        return "iyz-test-token-" . bin2hex(random_bytes(8));
    }
}
?>
