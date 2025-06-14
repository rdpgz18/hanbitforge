<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // Sertakan file koneksi

// Hapus semua variabel sesi
session_unset();

// Hancurkan sesi
session_destroy();

// Hapus cookie "remember_me" jika ada
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Opsional: Hapus token dari database
// Ini memerlukan akses ke user_id, yang mungkin sudah tidak ada di session setelah session_unset/destroy
// Jadi, Anda mungkin perlu mengambil user_id dari cookie atau langsung dari session sebelum menghancurkannya.
// Jika Anda tidak ingin mengambil user_id dari cookie, Anda bisa menghapus semua token kedaluwarsa secara berkala
// menggunakan cron job, atau mengandalkan penghapusan saat login berikutnya jika "remember me" tidak dicentang.

// --- Solusi yang Lebih Baik untuk Logout ---
// Sebelum session_unset() dan session_destroy():
if (isset($_SESSION['user_id'])) {
    $user_id_to_logout = $_SESSION['user_id'];
    $stmt_delete_token = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
    $stmt_delete_token->execute([$user_id_to_logout]);
}
// ------------------------------------------

// Redirect ke halaman login
header('Location: login.php');
exit();
?>