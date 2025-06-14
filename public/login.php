<?php
session_start();
require_once __DIR__ . '/../app/config.php'; // Sertakan file koneksi

$error_message = '';
$identifier_input = ''; // Variabel baru untuk menampung input dari user (bisa username, email, atau nomor HP)

// Pastikan $pdo tersedia
if (!isset($pdo)) {
    die("Database connection not established");
}

// --- KONSTANTA KEAMANAN ---
const MAX_LOGIN_ATTEMPTS = 5; // Batas percobaan login yang gagal
const COOLDOWN_TIME_MINUTES = 5; // Waktu cooldown (menit) setelah mencapai batas

// Bagian ini sebaiknya di file terpisah yang di-include (misal: auth_check.php)
// atau di awal semua halaman yang butuh otentikasi.
// Ini adalah "Auth Check" utama yang memastikan user_id ada di session.
// Jika ada session valid, redirect. Jika tidak, coba cookie.

// 1. Cek apakah user sudah login (ada session aktif)
if (isset($_SESSION['user_id'])) {
    // Pastikan user_id benar-benar ada di database
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userCheck = $stmt->fetch();
        if ($userCheck) {
            header('Location: dashboard.php'); // Redirect ke halaman dashboard jika session valid
            exit();
        } else {
            // user_id di session tidak valid (mungkin user dihapus), bersihkan sesi
            session_unset();
            session_destroy();
            // Lanjutkan ke bawah untuk mencoba cookie atau menampilkan form login
        }
    } catch (PDOException $e) {
        error_log("Database error during session check: " . $e->getMessage());
        session_unset();
        session_destroy();
        $error_message = "Terjadi kesalahan saat memeriksa sesi Anda.";
    }
}

// 2. Jika tidak ada session aktif, cek apakah ada cookie "remember_me"
if (isset($_COOKIE['remember_me'])) {
    // Dekripsi cookie value
    $cookie_data = json_decode(base64_decode($_COOKIE['remember_me']), true);

    // Pastikan data dalam cookie lengkap
    if (isset($cookie_data['user_id']) && isset($cookie_data['token'])) {
        try {
            // Verifikasi token dari database
            $stmt = $pdo->prepare("SELECT us.user_id FROM user_sessions us JOIN users u ON us.user_id = u.user_id WHERE us.user_id = :user_id AND us.token = :token AND us.expires_at > NOW()");
            $stmt->execute([
                ':user_id' => $cookie_data['user_id'],
                ':token' => $cookie_data['token']
            ]);
            $session_record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session_record) {
                // Token dan user_id valid, buat session baru
                $_SESSION['user_id'] = $session_record['user_id'];
                $user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                $user_stmt->execute([$session_record['user_id']]);
                $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['username'] = $user_data['username'] ?? 'User';

                // --- RESET ATTEMPTS ON SUCCESSFUL LOGIN (VIA REMEMBER ME) ---
                $reset_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_login = NULL WHERE user_id = ?");
                $reset_stmt->execute([$session_record['user_id']]);
                // --- END RESET ---

                // Opsional: Perbarui token dan tanggal kadaluarsa (rolling token)
                $new_token = bin2hex(random_bytes(32));
                $new_expires_timestamp = time() + (60 * 60 * 24 * 30);
                $new_expires_datetime = date('Y-m-d H:i:s', $new_expires_timestamp);

                $update_stmt = $pdo->prepare("UPDATE user_sessions SET token = ?, expires_at = ? WHERE user_id = ? AND token = ?");
                $update_stmt->execute([$new_token, $new_expires_datetime, $session_record['user_id'], $cookie_data['token']]);

                $new_cookie_value = base64_encode(json_encode([
                    'user_id' => $session_record['user_id'],
                    'token' => $new_token
                ]));
                setcookie('remember_me', $new_cookie_value, [
                    'expires' => $new_expires_timestamp,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                header('Location: dashboard.php');
                exit();
            } else {
                setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true); // Hapus cookie
            }
        } catch (PDOException $e) {
            error_log("Database error during remember_me check: " . $e->getMessage());
            setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            $error_message = "Terjadi kesalahan saat memverifikasi sesi otomatis Anda.";
        }
    } else {
        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $identifier_input = trim($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    if (empty($identifier_input) || empty($password)) {
        $error_message = 'Username/Email/Nomor HP dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, username, password, failed_login_attempts, last_failed_login
                FROM users
                WHERE username = :identifier
                   OR email = :identifier
                   OR phone_number = :identifier
            ");
            $stmt->execute([':identifier' => $identifier_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Cek jika user ditemukan
            if ($user) {
                // --- LOGIKA COOLDOWN/LOCKOUT ---
                $cooldown_end_time = null;
                if ($user['last_failed_login']) {
                    $last_fail_timestamp = strtotime($user['last_failed_login']);
                    $cooldown_end_time = $last_fail_timestamp + (COOLDOWN_TIME_MINUTES * 60);

                    if ($user['failed_login_attempts'] >= MAX_LOGIN_ATTEMPTS && time() < $cooldown_end_time) {
                        $remaining_time = $cooldown_end_time - time();
                        $minutes = floor($remaining_time / 60);
                        $seconds = $remaining_time % 60;
                        $error_message = "Akun Anda terkunci sementara karena terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam " . $minutes . " menit " . $seconds . " detik, atau <a href='forgot_password.php' class='text-indigo-600 hover:underline'>reset password</a> Anda.";
                        goto end_login_process; // Lompat ke bagian akhir untuk menampilkan form
                    }
                }
                // --- END LOGIKA COOLDOWN/LOCKOUT ---

                if (password_verify($password, $user['password'])) {
                    // Password benar, buat session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];

                    // --- RESET FAILED ATTEMPTS ON SUCCESSFUL LOGIN ---
                    $reset_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_login = NULL WHERE user_id = ?");
                    $reset_stmt->execute([$user['user_id']]);
                    // --- END RESET ---

                    if ($remember_me) {
                        $token = bin2hex(random_bytes(64));
                        $expires_timestamp = time() + (60 * 60 * 24 * 30);
                        $expires_datetime = date('Y-m-d H:i:s', $expires_timestamp);

                        $stmt_check_existing = $pdo->prepare("SELECT * FROM user_sessions WHERE user_id = ?");
                        $stmt_check_existing->execute([$user['user_id']]);
                        $existing_session = $stmt_check_existing->fetch();

                        if ($existing_session) {
                            $stmt_update = $pdo->prepare("UPDATE user_sessions SET token = ?, expires_at = ? WHERE user_id = ?");
                            $stmt_update->execute([$token, $expires_datetime, $user['user_id']]);
                        } else {
                            $stmt_insert = $pdo->prepare("INSERT INTO user_sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
                            $stmt_insert->execute([$user['user_id'], $token, $expires_datetime]);
                        }

                        $cookie_value = base64_encode(json_encode([
                            'user_id' => $user['user_id'],
                            'token' => $token
                        ]));

                        setcookie('remember_me', $cookie_value, [
                            'expires' => $expires_timestamp,
                            'path' => '/',
                            'domain' => '',
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    } else {
                        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                        $stmt_delete = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                        $stmt_delete->execute([$user['user_id']]);
                    }

                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Password salah
                    // --- INCREMENT FAILED ATTEMPTS ---
                    $new_attempts = $user['failed_login_attempts'] + 1;
                    $current_time = date('Y-m-d H:i:s');
                    $update_attempts_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, last_failed_login = ? WHERE user_id = ?");
                    $update_attempts_stmt->execute([$new_attempts, $current_time, $user['user_id']]);

                    if ($new_attempts >= MAX_LOGIN_ATTEMPTS) {
                        $error_message = "Terlalu banyak percobaan login yang gagal. Akun Anda telah terkunci sementara. Silakan coba lagi dalam " . COOLDOWN_TIME_MINUTES . " menit, atau <a href='forgot_password.php' class='text-indigo-600 hover:underline'>reset password</a> Anda.";
                    } else {
                        $remaining_attempts = MAX_LOGIN_ATTEMPTS - $new_attempts;
                        $error_message = "Username/Email/Nomor HP atau password salah! Anda memiliki " . $remaining_attempts . " percobaan tersisa sebelum akun terkunci.";
                    }
                }
            } else {
                // User tidak ditemukan (tidak ada perubahan pada failed_login_attempts untuk user yang tidak ada)
                $error_message = 'Username/Email/Nomor HP atau password salah!';
                // Pertimbangan: Hindari memberi tahu user bahwa username tidak ada, untuk mencegah enumerasi username
            }
        } catch (PDOException $e) {
            error_log("Database error during login attempt: " . $e->getMessage());
            $error_message = "Terjadi kesalahan saat mencoba login. Silakan coba lagi nanti.";
        }
    }
}
end_login_process: // Label untuk goto

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Login</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="./src/js/tailwind.config.js"></script>

    <link rel="shortcut icon" href="./assets/icon/pavicon.ico" type="image/x-icon">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            /* bg-gray-100 */
        }

        /* Styling untuk Page Loader Overlay */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            /* Latar belakang gelap transparan */
            display: flex;
            /* Untuk memusatkan spinner */
            justify-content: center;
            align-items: center;
            z-index: 9999;
            /* Pastikan di atas semua elemen lain */
            opacity: 0;
            visibility: hidden;
            /* Tambahkan visibility agar tidak interaktif saat tersembunyi */
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .page-loader.show {
            opacity: 1;
            visibility: visible;
        }

        /* Styling untuk Spinner (Contoh Sederhana) */
        .spinner {
            border: 8px solid rgba(255, 255, 255, 0.3);
            border-top: 8px solid #ffffff;
            /* Warna spinner */
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            /* Animasi berputar */
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Optional: Styling untuk menyembunyikan scrollbar saat loading */
        body.no-scroll {
            overflow: hidden;
        }

        /* Styling untuk notifikasi persetujuan cache */
        #consent-notification {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 50;
            /* Pastikan ini cukup tinggi agar terlihat di atas konten lain */
            transform: translateY(100%);
            opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            display: flex;
            /* Penting: agar transisi berfungsi */
            flex-direction: column;
            background-color: #1a202c;
            /* bg-gray-800 */
            color: white;
            padding: 1rem;
            /* p-4 */
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1), 0 -2px 4px -2px rgba(0, 0, 0, 0.06);
            /* shadow-lg */
        }

        #consent-notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Tambahan untuk responsivitas flex */
        @media (min-width: 768px) {

            /* md:flex-row */
            #consent-notification {
                flex-direction: row;
                align-items: center;
            }
        }

        #consent-notification p {
            text-align: center;
            margin-bottom: 0.5rem;
            /* mb-2 */
        }

        @media (min-width: 768px) {
            #consent-notification p {
                text-align: left;
                margin-bottom: 0;
                /* md:mb-0 */
            }
        }

        #consent-notification a {
            color: #81e6d9;
            /* text-indigo-400 */
            text-decoration: none;
        }

        #consent-notification a:hover {
            text-decoration: underline;
        }

        #consent-notification .flex.space-x-2 {
            display: flex;
            gap: 0.5rem;
            /* space-x-2 */
        }

        #consent-notification button {
            font-weight: 600;
            /* font-semibold */
            padding: 0.5rem 1rem;
            /* py-2 px-4 */
            border-radius: 0.375rem;
            /* rounded-md */
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
            /* transition-colors duration-200 */
            transition-duration: 200ms;
        }

        #accept-consent-btn {
            background-color: #4c51bf;
            /* bg-indigo-600 */
            color: white;
        }

        #accept-consent-btn:hover {
            background-color: #434190;
            /* hover:bg-indigo-700 */
        }

        #decline-consent-btn {
            background-color: #4a5568;
            /* bg-gray-600 */
            color: white;
        }

        #decline-consent-btn:hover {
            background-color: #2d3748;
            /* hover:bg-gray-700 */
        }

        /* Notifikasi Toast */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification-toast {
            padding: 10px 15px;
            border-radius: 8px;
            color: white;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .notification-toast.success {
            background-color: #4CAF50;
        }

        .notification-toast.error {
            background-color: #f44336;
        }

        .notification-toast.info {
            background-color: #2196F3;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Login ke HabitForge</h2>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username, Email, atau Nomor HP</label>
                <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($identifier_input); ?>" required autofocus>
            </div>
            <div class="mb-6 relative">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline pr-10" required>
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center top-7 cursor-pointer" id="togglePassword">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path id="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path id="eye-closed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5c-4.08 0-7.536 3.018-9 7.5S7.92 19.5 12 19.5s7.536-3.018 9-7.5-4.92-7.5-9-7.5zM12 14.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"></path>
                    </svg>
                </span>
            </div>
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember_me" name="remember_me" class="mr-2 leading-tight">
                    <label for="remember_me" class="text-sm text-gray-700">Ingat Saya</label>
                </div>
                <a href="forgot_password.php" class="inline-block align-baseline font-bold text-sm text-indigo-600 hover:text-indigo-800">Lupa Password?</a>
            </div>
            <button type="submit" name="login" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-indigo-700 focus:outline-none focus:shadow-outline">
                Login
            </button>
        </form>
        <p class="text-center text-gray-600 text-sm mt-4">
            Belum punya akun? <a href="./register.php" class="text-indigo-600 hover:text-indigo-800">Daftar Sekarang</a>
        </p>
    </div>

    <div id="consent-notification" class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white p-4 flex flex-col md:flex-row items-center justify-between shadow-lg z-50 transform translate-y-full transition-transform duration-300 ease-out">
        <p class="text-sm text-center md:text-left mb-2 md:mb-0">
            Situs web ini menggunakan cookie dan cache untuk meningkatkan pengalaman Anda. Dengan melanjutkan, Anda menyetujui penggunaan kami.
            <a href="/privacy-policy.php" class="text-indigo-400 hover:underline ml-1">Pelajari lebih lanjut.</a>
        </p>
        <div class="flex space-x-2">
            <button id="accept-consent-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                Terima
            </button>
            <button id="decline-consent-btn" class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                Tolak
            </button>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        // Sembunyikan ikon mata tertutup secara default
        eyeClosed.style.display = 'none';

        if (togglePassword && passwordInput && eyeOpen && eyeClosed) {
            togglePassword.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the eye icon
                if (type === 'password') {
                    eyeOpen.style.display = 'block';
                    eyeClosed.style.display = 'none';
                } else {
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'block';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const consentNotification = document.getElementById('consent-notification');
            const acceptConsentBtn = document.getElementById('accept-consent-btn');
            const declineConsentBtn = document.getElementById('decline-consent-btn');

            // Debugging: Periksa apakah elemen ditemukan
            console.log('DOMContentLoaded fired.');
            console.log('consentNotification element:', consentNotification);
            console.log('acceptConsentBtn element:', acceptConsentBtn);
            console.log('declineConsentBtn element:', declineConsentBtn);


            const CONSENT_STATUS_KEY = 'user_consent_status'; // Key for local storage
            const CONSENT_VERSION = 'v1.0'; // Versioning for consent policy - Ubah ini jika Anda ingin memaksa popup muncul lagi

            // Function to show the consent notification/popup
            function showConsentNotification() {
                if (consentNotification) {
                    // Karena kita sudah set display:flex di CSS, ini akan berfungsi
                    void consentNotification.offsetWidth; // Memicu reflow
                    consentNotification.classList.add('show');
                    console.log('Consent notification shown successfully by adding "show" class.');
                } else {
                    console.error('ERROR: consentNotification element is null when trying to show it!');
                }
            }
            // Function to hide the consent notification/popup
            function hideConsentNotification() {
                if (consentNotification) {
                    consentNotification.classList.remove('show');
                    // Opsional: Jika Anda ingin menyembunyikannya sepenuhnya setelah transisi,
                    // tambahkan 'hidden' atau ubah display:none setelah transisi selesai.
                    // Namun, untuk transisi opacity/transform, biasanya cukup dengan menghilangkan class 'show'.
                    consentNotification.addEventListener('transitionend', function handler() {
                        // Remove the event listener to prevent it from firing multiple times
                        consentNotification.removeEventListener('transitionend', handler);
                        // If you added 'hidden' class in HTML, you might add it back here
                        // consentNotification.classList.add('hidden');
                        console.log('Consent notification hidden after interaction.'); // Debugging log
                    }, {
                        once: true
                    }); // 'once: true' ensures the event listener is removed after it fires once
                } else {
                    console.error('ERROR: consentNotification element is null when trying to hide it!');
                }
            }

            // Function to record consent on the server
            function recordConsent(isAccepted) {
                // PERHATIKAN: PATH KE record_consent.php
                // Jika file JavaScript ini berada di 'public/js/nama_file.js'
                // dan record_consent.php berada di 'public/actions/record_consent.php'
                // Maka path relatif harusnya './actions/record_consent.php'
                // Atau jika dari root web server, '/actions/record_consent.php'
                // Coba ganti '../actions/record_consent.php' atau './actions/record_consent.php'
                // Atau path absolut dari root domain jika web server Anda dikonfigurasi demikian.
                // Contoh: '/actions/record_consent.php'
                const consentEndpoint = './actions/record_consent.php'; // Atau sesuaikan
                console.log('Attempting to send consent to:', consentEndpoint); // Debugging log

                fetch(consentEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `is_accepted=${isAccepted ? 'true' : 'false'}&consent_type=cache_cookie`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            console.log('Consent recorded successfully on server:', data.message);
                        } else {
                            console.error('Failed to record consent on server (server response success: false):', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error sending consent to server (fetch error):', error);
                        // Tampilkan pesan error ke user jika perlu
                    });
            }

            // --- Main Logic for Displaying Notification ---
            const storedConsentStatus = localStorage.getItem(CONSENT_STATUS_KEY);
            const storedConsentVersion = localStorage.getItem(CONSENT_STATUS_KEY + '_version');

            console.log('Local Storage - Stored Consent Status:', storedConsentStatus); // Debugging log
            console.log('Local Storage - Stored Consent Version:', storedConsentVersion); // Debugging log
            console.log('Current CONSENT_VERSION in JS:', CONSENT_VERSION); // Debugging log

            // Display the notification if consent hasn't been given or version changed
            if (!storedConsentStatus || storedConsentVersion !== CONSENT_VERSION) {
                console.log('Condition met: Showing consent notification (no stored consent or version mismatch).'); // Debugging log
                // Tambahkan delay agar elemen HTML sempat dirender sebelum animasi
                setTimeout(showConsentNotification, 500); // Tunda 0.5 detik
            } else {
                console.log('Condition NOT met: Consent already given or version matched. Not showing notification.'); // Debugging log
                // Jika notifikasi tidak perlu ditampilkan, pastikan juga disembunyikan jika secara tidak sengaja tampil
                // Ini penting jika CSS awal tidak sempurna menyembunyikannya
                setTimeout(hideConsentNotification, 10); // Sembunyikan segera jika tidak perlu ditampilkan
            }

            // Event listener for "Accept" button
            if (acceptConsentBtn) {
                acceptConsentBtn.addEventListener('click', function() {
                    localStorage.setItem(CONSENT_STATUS_KEY, 'accepted');
                    localStorage.setItem(CONSENT_STATUS_KEY + '_version', CONSENT_VERSION);
                    hideConsentNotification();
                    recordConsent(true); // Record acceptance to DB
                    showToastNotification('Anda telah menyetujui penggunaan cookie dan cache.', 'success');
                });
            } else {
                console.error('ERROR: acceptConsentBtn element not found!');
            }

            // Event listener for "Decline" button
            if (declineConsentBtn) {
                declineConsentBtn.addEventListener('click', function() {
                    localStorage.setItem(CONSENT_STATUS_KEY, 'declined'); // Record refusal
                    localStorage.setItem(CONSENT_STATUS_KEY + '_version', CONSENT_VERSION);
                    hideConsentNotification();
                    recordConsent(false); // Record decline to DB
                    showToastNotification('Anda menolak penggunaan cookie dan cache. Beberapa fitur mungkin terbatas.', 'info');
                });
            } else {
                console.error('ERROR: declineConsentBtn element not found!');
            }


            // Pastikan showToastNotification function is available (dari kode sebelumnya)
            function showToastNotification(message, type = 'info', duration = 3000) {
                const container = document.getElementById('notification-container');
                if (!container) {
                    console.warn('Notification container not found. Creating one.');
                    const body = document.querySelector('body');
                    const newContainer = document.createElement('div');
                    newContainer.id = 'notification-container';
                    newContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
                    body.appendChild(newContainer);
                    container = newContainer;
                }

                const notification = document.createElement('div');
                notification.style.cssText = `
            padding: 10px 15px;
            border-radius: 8px;
            color: white;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;
                if (type === 'success') {
                    notification.style.backgroundColor = '#4CAF50';
                } else if (type === 'error') {
                    notification.style.backgroundColor = '#f44336';
                } else { // info
                    notification.style.backgroundColor = '#2196F3';
                }

                notification.textContent = message;

                container.appendChild(notification);

                void notification.offsetWidth; // Trigger reflow for animation
                notification.classList.add('show'); // Apply 'show' class for fade-in effect

                setTimeout(() => {
                    notification.classList.remove('show');
                    notification.addEventListener('transitionend', () => {
                        notification.remove();
                    }, {
                        once: true
                    });
                }, duration);
            }
        });
    </script>
</body>

</html>