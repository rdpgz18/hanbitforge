<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";


header('Content-Type: application/json'); // Memberi tahu browser bahwa responsnya adalah JSON

$response = ['success' => false, 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan user_id tersedia dari sesi atau mekanisme autentikasi Anda
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Pengguna tidak terautentikasi.';
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Ambil data dari POST request
    $habit_name = trim($_POST['habit_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');

    // Validasi input
    if (empty($habit_name)) {
        $response['message'] = 'Nama kebiasaan tidak boleh kosong.';
        echo json_encode($response);
        exit();
    }
    if (empty($frequency)) {
        $response['message'] = 'Frekuensi tidak boleh kosong.';
        echo json_encode($response);
        exit();
    }

    try {
        // Query untuk menyisipkan data ke tabel habits
        $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, description, frequency, status) VALUES (:user_id, :habit_name, :description, :frequency, 'active')");

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':habit_name', $habit_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':frequency', $frequency, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Kebiasaan baru berhasil ditambahkan!';
        } else {
            $response['message'] = 'Gagal menambahkan kebiasaan baru.';
        }
    } catch (PDOException $e) {
        error_log("Error adding habit: " . $e->getMessage()); // Catat error ke log
        $response['message'] = 'Terjadi kesalahan database: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();
?>