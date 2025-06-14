<?php
session_start();
require_once __DIR__ . '/../app/config.php';

$message = '';
$error = '';

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

            if ($user) {
                // Di sini Anda akan mengimplementasikan logika pengiriman email reset password.
                // Ini melibatkan:
                // 1. Membuat token unik dan menyimpannya di database dengan user_id dan waktu kadaluarsa.
                // 2. Membuat URL reset password yang berisi token.
                // 3. Mengirim email ke alamat email pengguna yang berisi URL tersebut.
                // Contoh:
                // $token = bin2hex(random_bytes(32));
                // $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token berlaku 1 jam
                // $stmt_insert_token = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                // $stmt_insert_token->execute([$user['user_id'], $token, $expires]);

                // $reset_link = "http://localhost/path/to/reset_password.php?token=" . $token;
                // Kirim email (butuh konfigurasi SMTP/mail library)

                $message = "Jika email/username Anda terdaftar, instruksi reset password telah dikirimkan ke email Anda.";
                // Penting: Pesan ini harus generik untuk mencegah enumerasi email/username.
            } else {
                $message = "Jika email/username Anda terdaftar, instruksi reset password telah dikirimkan ke email Anda.";
                // Tetap berikan pesan generik bahkan jika user tidak ditemukan.
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