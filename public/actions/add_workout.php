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

$user_id =$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workout_name = trim($_POST['workout_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
    $calories_burned = intval($_POST['calories_burned'] ?? 0);
    $workout_date = trim($_POST['workout_date'] ?? '');

    if (empty($workout_name) || $duration_minutes <= 0 || empty($workout_date)) {
        $response['message'] = 'Nama latihan, durasi, dan tanggal tidak boleh kosong dan durasi harus lebih dari 0.';
        echo json_encode($response);
        exit();
    }

    if (addWorkout($pdo, $user_id, $workout_name, $description, $duration_minutes, $calories_burned, $workout_date)) {
        $response['success'] = true;
        $response['message'] = 'Latihan berhasil dicatat!';
    } else {
        $response['message'] = 'Gagal mencatat latihan. Silakan coba lagi.';
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();


?>