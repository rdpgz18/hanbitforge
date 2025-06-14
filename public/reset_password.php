<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan timezone server atau gunakan 'UTC'

require_once __DIR__ . '/../app/config.php'; // Sertakan file koneksi database

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? ''; // Ambil token dari URL parameter
$user_id_from_url = $_GET['id'] ?? ''; // Ambil user_id dari URL parameter

// Pastikan $pdo tersedia
if (!isset($pdo)) {
    die("Database connection not established. Please check app/config.php");
}

// Initialize valid_token and user_id_from_token
$valid_token = false;
$user_id_from_db = null; // Ini akan diisi dari database setelah verifikasi token

// 1. Verifikasi Token saat halaman dimuat
if (!empty($token) && !empty($user_id_from_url) && is_numeric($user_id_from_url)) {
    try {
        // Query untuk memverifikasi token dan user_id dari URL
        $stmt = $pdo->prepare("
            SELECT user_id, expires_at
            FROM password_resets
            WHERE token = :token
            AND user_id = :user_id
            AND expires_at > NOW()
            FOR UPDATE
        ");
        $stmt->execute([
            ':token' => $token,
            ':user_id' => $user_id_from_url
        ]);
        $reset_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset_record) {
            $valid_token = true;
            $user_id_from_db = $reset_record['user_id']; // Dapatkan user_id dari record database
            // Debugging: echo "Token is valid for user ID: " . $user_id_from_db . "<br>";
            // Debugging: echo "Expires at: " . $reset_record['expires_at'] . "<br>";
            // Debugging: echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

        } else {
            // Log untuk debugging: Apa yang menyebabkan tidak valid/kedaluwarsa?
            $log_message = "Invalid or expired token access attempt: Token=" . $token . ", UserID_URL=" . $user_id_from_url;
            $check_stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = :token");
            $check_stmt->execute([':token' => $token]);
            $found_record = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($found_record) {
                if ($found_record['user_id'] != $user_id_from_url) {
                    $log_message .= " - Token found but user_id mismatch (DB:" . $found_record['user_id'] . ").";
                } elseif (strtotime($found_record['expires_at']) <= time()) {
                    $log_message .= " - Token found but expired (Expires:" . $found_record['expires_at'] . ").";
                } else {
                    $log_message .= " - Token found but other condition failed (e.g., deleted).";
                }
            } else {
                $log_message .= " - Token not found in database.";
            }
            error_log($log_message);

            $error_message = "Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan coba proses reset password lagi.";
        }
    } catch (PDOException $e) {
        error_log("Database error during token verification on page load: " . $e->getMessage());
        $error_message = "Terjadi kesalahan saat memverifikasi tautan. Silakan coba lagi nanti.";
    }
} else {
    $error_message = "Tautan reset password tidak lengkap atau tidak valid. Anda harus mengakses halaman ini dari tautan di email reset password.";
}

// 2. Proses Pengaturan Password Baru (saat form disubmit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_new_password'])) {
    // Pastikan token dan user_id yang disubmit sesuai dengan yang dimuat
    $submitted_token = $_POST['token'] ?? '';
    $submitted_user_id = $_POST['user_id'] ?? ''; // Ambil user_id dari hidden input
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Re-verifikasi token untuk memastikan tidak ada manipulasi selama pengiriman form
    $re_verified_token = false;
    if (!empty($submitted_token) && !empty($submitted_user_id) && is_numeric($submitted_user_id)) {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, expires_at
                FROM password_resets
                WHERE token = :token
                AND user_id = :user_id
                AND expires_at > NOW()
            ");
            $stmt->execute([
                ':token' => $submitted_token,
                ':user_id' => $submitted_user_id
            ]);
            $re_check_record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($re_check_record) { // Jika record ditemukan dan valid
                $re_verified_token = true;
                // user_id_from_db seharusnya sudah sesuai, tapi kita bisa pakai submitted_user_id
                $user_id_to_update = $submitted_user_id;
            }
        } catch (PDOException $e) {
            error_log("Database error during re-verification of token on POST: " . $e->getMessage());
            $error_message = "Terjadi kesalahan saat memverifikasi tautan Anda. Silakan coba lagi nanti.";
        }
    }

    if (!$re_verified_token) {
        // Jika token tidak lagi valid (misalnya, kedaluwarsa antara halaman dimuat dan pengiriman)
        // atau dimanipulasi, atur error.
        $error_message = "Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan mulai proses reset password dari awal.";
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error_message = "Password baru dan konfirmasi password harus diisi.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Password baru dan konfirmasi password tidak cocok.";
    } elseif (strlen($new_password) < 8) { // Contoh: Minimal 8 karakter
        $error_message = "Password baru harus memiliki minimal 8 karakter.";
    } else {
        try {
            // Mulai transaksi untuk memastikan atomicity
            $pdo->beginTransaction();

            // Hash password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Perbarui password di tabel users
            $stmt_update_password = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt_update_password->execute([$hashed_password, $user_id_to_update]);

            // Hapus token dari tabel password_resets agar tidak bisa digunakan lagi
            $stmt_delete_token = $pdo->prepare("DELETE FROM password_resets WHERE token = ? AND user_id = ?");
            $stmt_delete_token->execute([$submitted_token, $user_id_to_update]);

            // Reset percobaan login gagal (jika ada)
            $reset_login_attempts_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_login = NULL WHERE user_id = ?");
            $reset_login_attempts_stmt->execute([$user_id_to_update]);

            $pdo->commit(); // Commit transaksi

            $success_message = "Password Anda telah berhasil direset! Anda sekarang bisa <a href='login.php' class='text-indigo-600 hover:underline font-bold'>Login</a>.";
            $valid_token = false; // Nonaktifkan form setelah berhasil
            $token = ''; // Kosongkan token agar form tidak muncul lagi jika di-refresh
            $user_id_from_url = ''; // Kosongkan user_id juga
        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback saat terjadi error
            error_log("Database error during password update: " . $e->getMessage());
            $error_message = "Terjadi kesalahan saat memperbarui password Anda. Silakan coba lagi nanti.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - HabitForge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/theme.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Reset Password Anda</h2>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($valid_token && empty($success_message)): // Tampilkan formulir hanya jika token valid dan belum ada pesan sukses ?>
            <p class="text-gray-600 text-center mb-6">Masukkan password baru Anda.</p>
            <form action="reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_from_url); ?>">

                <div class="mb-4">
                    <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required autofocus>
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" name="set_new_password" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                        Set Password Baru
                    </button>
                </div>
            </form>
        <?php elseif (empty($success_message)): // Tampilkan pesan untuk token tidak valid jika belum ada pesan sukses ?>
            <p class="text-center text-gray-600 text-sm mt-6">
                Silakan kembali ke halaman <a href="forgot_password.php" class="text-indigo-600 hover:text-indigo-800 font-bold">Lupa Password</a> untuk memulai proses reset.
            </p>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <p class="text-center text-gray-600 text-sm mt-6">
                Anda dapat <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-bold">Login sekarang</a> dengan password baru Anda.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>