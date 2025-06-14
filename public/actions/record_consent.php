<?php
// public/actions/record_consent.php

session_start();
require_once dirname(__DIR__) . '/../../app/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_accepted = filter_input(INPUT_POST, 'is_accepted', FILTER_VALIDATE_BOOLEAN);
    $consent_type = filter_input(INPUT_POST, 'consent_type', FILTER_SANITIZE_STRING);

    if ($consent_type === null) {
        $consent_type = 'cache_cookie';
    }

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_consents (user_id, session_id, ip_address, consent_given_at, consent_type, is_accepted)
            VALUES (?, ?, ?, NOW(), ?, ?)
            -- Jika Anda menggunakan UNIQUE INDEX pada (session_id, consent_type)
            ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address),
                consent_given_at = NOW(),
                is_accepted = VALUES(is_accepted)
        ");

        $stmt->execute([$user_id, $session_id, $ip_address, $consent_type, $is_accepted]);

        $response['success'] = true;
        $response['message'] = 'Persetujuan berhasil dicatat.';

    } catch (PDOException $e) {
        error_log("Error recording consent: " . $e->getMessage());
        $response['message'] = 'Gagal mencatat persetujuan: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Metode permintaan tidak valid.';
}

echo json_encode($response);
?>