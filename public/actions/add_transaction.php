<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Terjadi kesalahan.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Pengguna tidak terautentikasi.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT); // Validasi sebagai float
    $type = trim($_POST['type'] ?? '');
    $category = trim($_POST['category'] ?? null); // Boleh kosong
    $transaction_date = trim($_POST['transaction_date'] ?? '');

    // Validasi input
    if (empty($description) || $amount <= 0 || !in_array($type, ['income', 'expense']) || empty($transaction_date)) {
        $response['message'] = 'Deskripsi, jumlah, tipe, dan tanggal transaksi tidak boleh kosong dan jumlah harus lebih dari 0.';
        echo json_encode($response);
        exit();
    }

    if (addTransaction($pdo, $user_id, $description, $amount, $type, $category, $transaction_date)) {
        $response['success'] = true;
        $response['message'] = 'Transaksi berhasil dicatat!';
    } else {
        $response['message'] = 'Gagal mencatat transaksi. Silakan coba lagi.';
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();
?>