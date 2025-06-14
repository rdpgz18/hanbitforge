<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

header('Content-Type: application/json'); // Memberi tahu browser bahwa responsnya adalah JSON

$response = ['success' => false, 'message' => 'Terjadi kesalahan yang tidak diketahui.', 'habit' => null];

if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Menggunakan GET karena ini permintaan data
    // Pastikan user_id tersedia dari sesi atau mekanisme autentikasi Anda
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Pengguna tidak terautentikasi.';
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $habit_id = filter_input(INPUT_GET, 'habit_id', FILTER_VALIDATE_INT);

    if (!$habit_id) {
        $response['message'] = 'ID kebiasaan tidak valid.';
        echo json_encode($response);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT habit_id, habit_name, description, frequency FROM habits WHERE habit_id = :habit_id AND user_id = :user_id");
        $stmt->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $habit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($habit) {
            $response['success'] = true;
            $response['message'] = 'Detail kebiasaan berhasil diambil.';
            $response['habit'] = $habit;
        } else {
            $response['message'] = 'Kebiasaan tidak ditemukan atau Anda tidak memiliki izin untuk melihatnya.';
        }
    } catch (PDOException $e) {
        error_log("Error fetching habit details: " . $e->getMessage());
        $response['message'] = 'Terjadi kesalahan database: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();
?>