<?php
// actions/update_password.php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

header('Content-Type: application/json'); // Respons selalu dalam JSON

$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Anda harus login untuk memperbarui kata sandi.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = filter_input(INPUT_POST, 'current_password', FILTER_UNSAFE_RAW);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_UNSAFE_RAW);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_UNSAFE_RAW);

    // Validasi input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Semua kolom kata sandi harus diisi.';
        echo json_encode($response);
        exit();
    }

    if ($new_password !== $confirm_password) {
        $response['message'] = 'Kata sandi baru dan konfirmasi tidak cocok.';
        echo json_encode($response);
        exit();
    }

    // Anda bisa menambahkan validasi kekuatan kata sandi di sini (misal: min length, karakter khusus)
    if (strlen($new_password) < 8) {
        $response['message'] = 'Kata sandi baru minimal 8 karakter.';
        echo json_encode($response);
        exit();
    }

    // Panggil fungsi updatePassword
    $result = updatePassword($pdo, $user_id, $current_password, $new_password);

    $response['success'] = $result['success'];
    $response['message'] = $result['message'];

} else {
    $response['message'] = 'Metode request tidak diizinkan.';
}

echo json_encode($response);
exit();
?>