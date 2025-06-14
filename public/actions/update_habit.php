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
    $habit_id = filter_input(INPUT_POST, 'habit_id', FILTER_VALIDATE_INT);
    $habit_name = trim($_POST['habit_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');

    // Validasi input
    if (!$habit_id) {
        $response['message'] = 'ID kebiasaan tidak valid.';
        echo json_encode($response);
        exit();
    }
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
        // Query untuk memperbarui data di tabel habits
        $stmt = $pdo->prepare("UPDATE habits SET habit_name = :habit_name, description = :description, frequency = :frequency WHERE habit_id = :habit_id AND user_id = :user_id");

        $stmt->bindParam(':habit_name', $habit_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':frequency', $frequency, PDO::PARAM_STR);
        $stmt->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Periksa apakah ada baris yang terpengaruh (data benar-benar berubah)
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Kebiasaan berhasil diperbarui!';
            } else {
                $response['success'] = true; // Anggap sukses jika tidak ada perubahan, karena tujuan tercapai
                $response['message'] = 'Tidak ada perubahan yang terdeteksi pada kebiasaan.';
            }
        } else {
            $response['message'] = 'Gagal memperbarui kebiasaan.';
        }
    } catch (PDOException $e) {
        error_log("Error updating habit: " . $e->getMessage()); // Catat error ke log
        $response['message'] = 'Terjadi kesalahan database: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();
?>