<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

header('Content-Type: application/json'); // Penting untuk respons JSON

$response = ['success' => false, 'message' => 'Terjadi kesalahan.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Anda harus login untuk mencatat makanan.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_name = filter_input(INPUT_POST, 'food_name', FILTER_SANITIZE_STRING);
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_INT);
    $protein = filter_input(INPUT_POST, 'protein', FILTER_VALIDATE_FLOAT);
    $carbohydrates = filter_input(INPUT_POST, 'carbohydrates', FILTER_VALIDATE_FLOAT);
    $fats = filter_input(INPUT_POST, 'fats', FILTER_VALIDATE_FLOAT);
    $entry_date = filter_input(INPUT_POST, 'entry_date', FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($food_name) || $calories === false || $protein === false || $carbohydrates === false || $fats === false || empty($entry_date)) {
        $response['message'] = 'Semua bidang wajib diisi dan harus berupa angka yang valid.';
        echo json_encode($response);
        exit();
    }

    if ($calories < 0 || $protein < 0 || $carbohydrates < 0 || $fats < 0) {
        $response['message'] = 'Nilai nutrisi tidak boleh negatif.';
        echo json_encode($response);
        exit();
    }

    if (addFoodEntry($pdo, $user_id, $food_name, $calories, $protein, $carbohydrates, $fats, $entry_date)) {
        $response['success'] = true;
        $response['message'] = 'Makanan berhasil dicatat!';
    } else {
        $response['message'] = 'Gagal mencatat makanan. Silakan coba lagi.';
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();
?>