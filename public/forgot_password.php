<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan timezone server atau gunakan 'UTC'

require_once __DIR__ . '/../app/config.php'; // Sertakan file koneksi database
require_once __DIR__ . '/../vendor/autoload.php'; // Sertakan autoloader Composer untuk PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // Opsional, untuk debug

$message = '';
$error = '';

// --- KONFIGURASI EMAIL (SESUAIKAN DENGAN DETAIL SERVER SMTP ANDA) ---
// Sebaiknya disimpan di file konfigurasi terpisah (misalnya, app/email_config.php)
// atau sebagai environment variables, tapi untuk contoh ini kita taruh di sini.
$emailConfig = [
    'host'      => 'smtp.gmail.com', // Contoh untuk Gmail. Ganti dengan SMTP host Anda.
    'username'  => 'ridwankecil473@gmail.com', // Email pengirim Anda
    'password'  => 'ygpk wrqa pzxx pyug', // Password aplikasi Gmail (jika 2FA aktif) atau password email Anda
    'port'      => 587, // Port SMTP (587 untuk TLS, 465 untuk SSL)
    'encryption' => PHPMailer::ENCRYPTION_STARTTLS, // Atau PHPMailer::ENCRYPTION_SMTPS untuk port 465
    'from_email' => 'no-reply@habitForge.app', // Email yang akan terlihat sebagai pengirim
    'from_name' => 'HabitForge Support', // Nama pengirim
    // Sesuaikan dengan URL publik aplikasi Anda, pastikan path ke reset_password.php benar
    'reset_link_base_url' => 'http://localhost/habitforge.app/public/reset_password.php'
];
// --- AKHIR KONFIGURASI EMAIL ---


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_forgot'])) {
    $identifier = trim($_POST['identifier']); // Bisa email atau username 

    if (empty($identifier)) {
        $error = "Mohon masukkan Email atau Username Anda.";
    } else {
        try {
            // Cari user berdasarkan email atau username 
            $stmt = $pdo->prepare("SELECT user_id, email, username FROM users WHERE email = :identifier OR username = :identifier LIMIT 1");
            $stmt->execute([':identifier' => $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Pesan harus generik untuk mencegah enumerasi email/username, terlepas dari apakah user ditemukan atau tidak.
            // Ini adalah praktik keamanan terbaik.
            $message_to_user = "Jika email/username Anda terdaftar, instruksi reset password telah dikirimkan ke email Anda.";

            if ($user) {
                $user_email = $user['email'];
                $user_id = $user['user_id'];

                // 1. Buat token unik
                $token = bin2hex(random_bytes(32)); // Token yang kuat
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token berlaku 1 jam dari sekarang

                // 2. Simpan token di database
                // Hapus token lama jika ada untuk user ini (opsional, tapi bagus untuk kebersihan)
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user_id]);

                $stmt_insert_token = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt_insert_token->execute([$user_id, $token, $expires_at]);

                // 3. Buat URL reset password
                // Penting: Sertakan user_id juga di URL untuk verifikasi tambahan di reset_password.php
                // Meskipun token sudah unik per user_id, ini bisa jadi lapisan keamanan ekstra
                $reset_link = $emailConfig['reset_link_base_url'] . '?token=' . urlencode($token) . '&id=' . urlencode($user_id);

                // 4. Kirim email menggunakan PHPMailer
                $mail = new PHPMailer(true); // true enables exceptions

                try {
                    // Pengaturan Server
                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output (for testing)
                    $mail->isSMTP(); // Send using SMTP
                    $mail->Host = $emailConfig['host']; // Set the SMTP server to send through
                    $mail->SMTPAuth = true; // Enable SMTP authentication
                    $mail->Username = $emailConfig['username']; // SMTP username
                    $mail->Password = $emailConfig['password']; // SMTP password
                    $mail->SMTPSecure = $emailConfig['encryption']; // Enable implicit TLS encryption
                    $mail->Port = $emailConfig['port']; // TCP port to connect to; use 587 if you added `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                    // Recipients
                    $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
                    $mail->addAddress($user_email, $user['username']); // Add a recipient

                    // Content
                    $mail->isHTML(true); // Set email format to HTML
                    $mail->Subject = 'Reset Password Akun HabitForge Anda';
                    $mail->Body    = 'Halo ' . htmlspecialchars($user['username']) . ',<br><br>'
                        . 'Kami menerima permintaan untuk mereset password akun HabitForge Anda. '
                        . 'Untuk melanjutkan, silakan klik tautan di bawah ini:<br><br>'
                        . '<a href="' . htmlspecialchars($reset_link) . '">' . htmlspecialchars($reset_link) . '</a><br><br>'
                        . 'Tautan ini akan kedaluwarsa dalam 1 jam.<br>'
                        . 'Jika Anda tidak meminta reset password ini, abaikan email ini.<br><br>'
                        . 'Terima kasih,<br>'
                        . 'Tim HabitForge';
                    $mail->AltBody = 'Halo ' . htmlspecialchars($user['username']) . ',\n\n'
                        . 'Kami menerima permintaan untuk mereset password akun HabitForge Anda. '
                        . 'Untuk melanjutkan, silakan salin dan tempel tautan di browser Anda:\n\n'
                        . htmlspecialchars($reset_link) . '\n\n'
                        . 'Tautan ini akan kedaluwarsa dalam 1 jam.\n'
                        . 'Jika Anda tidak meminta reset password ini, abaikan email ini.\n\n'
                        . 'Terima kasih,\n'
                        . 'Tim HabitForge';

                    $mail->send();
                    // Setelah email berhasil dikirim, set pesan sukses generik
                    $message = $message_to_user;
                } catch (Exception $e) {
                    // Log error PHPMailer, tapi jangan tampilkan ke pengguna untuk keamanan
                    error_log("PHPMailer Error: " . $e->getMessage());
                    // Tetap berikan pesan generik kepada pengguna
                    $message = $message_to_user; // Tetap informatif tetapi tidak mengungkapkan internal
                }
            } else {
                // User tidak ditemukan, tetapi tetap berikan pesan generik yang sama
                $message = $message_to_user;
            }
        } catch (PDOException $e) {
            error_log("Database error on forgot password: " . $e->getMessage());
            $error = "Terjadi kesalahan. Silakan coba lagi nanti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - HabitForge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="./assets/icon/pavicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../src/css/theme.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Lupa Password?</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Informasi:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <p class="text-gray-600 text-center mb-6">Masukkan email atau username yang terdaftar untuk mereset password Anda.</p>

        <form action="forgot_password.php" method="POST">
            <div class="mb-4">
                <label for="identifier" class="block text-gray-700 text-sm font-bold mb-2">Email atau Username</label>
                <input type="text" id="identifier" name="identifier" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required autofocus>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" name="submit_forgot" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Kirim Link Reset Password
                </button>
            </div>
        </form>
        <p class="text-center text-gray-600 text-sm mt-6">
            Ingat password Anda? <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-bold">Login di sini.</a>
        </p>
    </div>
</body>

</html>