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
    $workout_id = intval($_POST['workout_id'] ?? 0);
    $workout_name = trim($_POST['workout_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
    $calories_burned = intval($_POST['calories_burned'] ?? 0);
    $workout_date = trim($_POST['workout_date'] ?? '');

    if ($workout_id <= 0 || empty($workout_name) || $duration_minutes <= 0 || empty($workout_date)) {
        $response['message'] = 'Data latihan tidak valid atau tidak lengkap.';
        echo json_encode($response);
        exit();
    }

    if (editWorkout($pdo, $workout_id, $user_id, $workout_name, $description, $duration_minutes, $calories_burned, $workout_date)) {
        $response['success'] = true;
        $response['message'] = 'Latihan berhasil diperbarui!';
    } else {
        $response['message'] = 'Gagal memperbarui latihan. Pastikan Anda memiliki izin untuk mengeditnya.';
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();
?>