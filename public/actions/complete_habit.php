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
    $habit_id = filter_input(INPUT_POST, 'habit_id', FILTER_VALIDATE_INT);
    $action_type = filter_input(INPUT_POST, 'action_type', FILTER_SANITIZE_STRING); // 'complete' atau 'reset' (jika nanti ada)

    if (!$habit_id) {
        $response['message'] = 'ID kebiasaan tidak valid.';
        echo json_encode($response);
        exit();
    }

    try {
        // Ambil data kebiasaan saat ini untuk validasi dan pembaruan streak
        $stmt_select = $pdo->prepare("SELECT user_id, status, current_streak, last_completed_date FROM habits WHERE habit_id = :habit_id");
        $stmt_select->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
        $stmt_select->execute();
        $habit = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$habit) {
            $response['message'] = 'Kebiasaan tidak ditemukan.';
            echo json_encode($response);
            exit();
        }

        // Pastikan kebiasaan ini milik user yang sedang login
        if ($habit['user_id'] != $user_id) {
            $response['message'] = 'Anda tidak memiliki izin untuk memperbarui kebiasaan ini.';
            echo json_encode($response);
            exit();
        }

        $today = date('Y-m-d');
        $last_completed_date = $habit['last_completed_date'];
        $current_streak = $habit['current_streak'];
        $new_status = 'active'; // Default status setelah selesai hari ini

    
        // Logika untuk memperbarui streak dan status
            if ($action_type === 'complete') {
                // Cek apakah kebiasaan sudah diselesaikan hari ini
                if ($habit['status'] == 'completed' && $habit['last_completed_date'] === $today) {
                    $response['message'] = 'Kebiasaan ini sudah diselesaikan hari ini.';
                    echo json_encode($response);
                    exit();
                }

                $new_streak = $current_streak;
                $new_last_completed_date = $today;

                // Periksa apakah terakhir diselesaikan adalah kemarin
                if ($last_completed_date === date('Y-m-d', strtotime('-1 day'))) {
                    $new_streak++; // Lanjutkan streak
                } elseif ($last_completed_date !== $today) {
                    // Jika last_completed_date bukan hari ini, dan bukan kemarin (atau null)
                    // Maka mulai streak baru (karena hari ini adalah awal yang baru atau ada jeda)
                    $new_streak = 1;
                }
                // Jika last_completed_date sudah hari ini, streak tidak berubah, hanya status diperbarui (jika perlu)

                $new_status = 'completed'; // Selalu tandai sebagai selesai untuk hari ini

                $stmt_update = $pdo->prepare("UPDATE habits SET status = :status, current_streak = :current_streak, last_completed_date = :last_completed_date WHERE habit_id = :habit_id AND user_id = :user_id");
                $stmt_update->bindParam(':status', $new_status, PDO::PARAM_STR);
                $stmt_update->bindParam(':current_streak', $new_streak, PDO::PARAM_INT); // Gunakan $new_streak
                $stmt_update->bindParam(':last_completed_date', $new_last_completed_date, PDO::PARAM_STR); // Gunakan $new_last_completed_date
                $stmt_update->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
                $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                $response['success'] = true;
                $response['message'] = 'Kebiasaan berhasil ditandai selesai untuk hari ini!';
                $response['new_streak'] = $current_streak;
                $response['new_status'] = $new_status;
            } else {
                $response['message'] = 'Gagal menandai kebiasaan selesai.';
            }
        } else {
            $response['message'] = 'Tipe aksi tidak valid.';
        }

    } catch (PDOException $e) {
        error_log("Error completing habit: " . $e->getMessage());
        $response['message'] = 'Terjadi kesalahan database: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
exit();

?>