<?php
session_start();

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/functions.php'; // Include file fungsi kebiasaan


if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

// ambil data
if (isset($_SESSION['user_id'])) {
    // Memuat konfigurasi database
    try {
        // Ambil SEMUA data pengguna yang diperlukan dari tabel 'users'
        // Termasuk 'avatar_url' untuk gambar profil
        $stmt = $pdo->prepare("SELECT user_id, username,full_name, email, avatar_url FROM users WHERE user_id = :user_id"); //
        $stmt->execute([':user_id' => $_SESSION['user_id']]); //
        $loggedInUser = $stmt->fetch(PDO::FETCH_ASSOC); //

        // Jika pengguna tidak ditemukan di database (misalnya, akun dihapus)
        if (!$loggedInUser) {
            session_unset(); // Hapus semua variabel sesi
            session_destroy(); // Hancurkan sesi
            header("Location: /login.php"); // Arahkan kembali ke halaman login
            exit();
        }
    } catch (PDOException $e) {
        // Catat error dan arahkan kembali ke halaman login atau tampilkan pesan error
        error_log("Authentication failed: " . $e->getMessage());
        session_unset();
        session_destroy();
        header("Location: /login.php");
        exit();
    }
} else {
    // Jika user_id tidak ada di sesi, pengguna belum login
    // Arahkan ke halaman login
    header("Location: /login.php"); // Sesuaikan dengan path halaman login Anda
    exit();
}




$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$today = date('Y-m-d');
$currentMonth = date('Y-m');

// --- Panggil Fungsi Get Daily Habit Summary di sini ---
$habitSummary = getDailyHabitSummary($pdo, $user_id);
$caloriesBurnedToday = getTotalCaloriesBurnedToday($pdo, $user_id, $today);
$remainingBudget = getRemainingBudgetForMonth($pdo, $user_id, $currentMonth);
$dailyHabits = getDailyHabitsForUser($pdo, $user_id, 3);

// Ambil profile user login

$profileImageUrl = '';
$defaultImage = './assets/images/user_nophoto.png'; // Sesuaikan path ini!
// Jika file ini di public/pages/dashboard.php dan gambar di public/assets/images, maka "../assets/images/..."
// Jika file ini di public/includes/navbar.php (yang di-include dari public/pages/dashboard.php),
// dan gambar di public/assets/images, maka "../assets/images/..." juga benar.
// Sesuaikan dengan struktur folder proyek Anda.

// Cek apakah $loggedInUser ada dan memiliki avatar_url
if (isset($loggedInUser['avatar_url']) && !empty($loggedInUser['avatar_url'])) {
    // Jika avatar_url tidak kosong, gunakan itu
    // Asumsi avatar_url adalah path relatif dari root proyek atau URL lengkap
    $profileImageUrl = htmlspecialchars($loggedInUser['avatar_url']);
} else {
    // Jika avatar_url kosong atau tidak ada, gunakan gambar default
    $profileImageUrl = $defaultImage;
}


?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Dashboard</title>

    <!-- Tailwindcss -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link href="./src/css/tailwind.css" rel="stylesheet">
    <link href="./src/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="./src/css/theme.css">

    <script src="./src/js/tailwind.config.js"></script>
    
    <link rel="shortcut icon" href="./assets/icon/pavicon.ico" type="image/x-icon">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Tambahkan overflow-hidden saat menu mobile aktif untuk mencegah scroll body */
        }

        /* Animasi sederhana untuk kartu (bisa dipindahkan ke main.css) */
        .card-enter {
            opacity: 0;
            transform: translateY(20px);
        }

        .card-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        /* Kelas untuk overlay saat menu mobile aktif */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
            /* Di bawah sidebar, di atas konten lain */
            display: none;
            /* Disembunyikan secara default */
        }

        .overlay.active {
            display: block;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="flex flex-col md:flex-row min-h-screen relative">
        <div id="mobile-menu-overlay" class="overlay"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out
                                w-64 bg-white shadow-lg p-6 md:p-8 flex flex-col items-center md:items-start border-b md:border-r border-gray-200 z-50">
            <div class="mb-8 text-center md:text-left w-full flex justify-between items-center md:block">
                <h1 class="text-3xl font-bold text-indigo-700">HabitForge</h1>
                <button id="close-menu-btn" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <p class="text-sm text-gray-500 md:block hidden">Tempa Kebiasaan Baikmu</p>
            </div>
            <nav class="w-full">
                <ul class="space-y-4">
                    <li>
                        <a href="./dashboard.php" class="flex items-center p-3 rounded-lg text-indigo-700 bg-indigo-100 font-semibold hover:bg-indigo-200 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 010-10"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="./pages/habits.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Kebiasaan
                        </a>
                    </li>
                    <li>
                        <a href="./pages/exercise.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Olahraga
                        </a>
                    </li>
                    <li>
                        <a href="./pages/finance.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Keuangan
                        </a>
                    </li>
                    <li>
                        <a href="./pages/nutrition.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 10h.01M12 12h.01M12 3c-1.396 0-2.392 0-3.149 0-.756.756-1.042 1.543-1.042 3.149v6.702c0 1.606.286 2.393 1.042 3.149.757.757 1.543 1.042 3.149 1.042h6.702c1.606 0 2.393-.285 3.149-1.042.757-.756 1.042-1.543 1.042-3.149V8.851c0-1.606-.285-2.392-1.042-3.149-.756-.757-1.543-1.042-3.149-1.042H9z"></path>
                            </svg>
                            Nutrisi
                        </a>
                    </li>
                    <li>
                        <a href="./pages/settings.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Pengaturan
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto pt-8 w-full text-center md:text-left">
                <a href="logout.php" class="block w-full bg-red-100 text-red-700 py-2 px-4 rounded-lg hover:bg-red-200 transition-colors duration-200 text-center">
                    Keluar
                </a>
            </div>
        </aside>

        <main class="flex-1 p-6 md:p-10">
            <header class="flex flex-col sm:flex-row justify-between items-center mb-8">
                <div class="flex items-center w-full sm:w-auto justify-between mb-4 sm:mb-0">
                    <button id="open-menu-btn" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="text-4xl font-bold text-gray-900 ">Halo,&nbsp;<?php echo ($loggedInUser['full_name']); ?>!</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <img src="./<?= $profileImageUrl ?>" alt="Avatar Pengguna" class="w-10 h-10 rounded-full border-2 border-indigo-500 cursor-pointer">
                    </div>
                </div>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between transition-transform transform hover:scale-105 card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Kebiasaan Selesai Hari Ini</p>
                        <p class="text-3xl font-bold text-indigo-600">
                            <?= htmlspecialchars($habitSummary['completed_today']) ?>/<?= htmlspecialchars($habitSummary['total_active_habits']) ?>
                        </p>
                    </div>
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between transition-transform transform hover:scale-105 card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Kalori Terbakar Hari Ini</p>
                        <p class="text-3xl font-bold text-green-600"><?= htmlspecialchars($caloriesBurnedToday) ?> <span class="text-xl font-normal">kcal</span></p>
                    </div>
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between transition-transform transform hover:scale-105 card-enter card-enter-active">
                    <div>
                        <div class="flex items-center mb-1">
                            <p class="text-gray-500 text-sm mr-2">Sisa Anggaran Bulan Ini</p>
                            <button id="toggleBudgetVisibility" class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200 p-1 -mt-0.5 rounded-full hover:bg-gray-100">
                                <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="eyeSlashIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064-7 9.542-7 1.65 0 3.218.342 4.675.975M14.004 17.978a10.05 10.05 0 002.928-.795"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064-7 9.542-7 1.65 0 3.218.342 4.675.975M14.004 17.978a10.05 10.05 0 002.928-.795M19.542 12A10.05 10.05 0 0122 13c-1.274 4.057-5.064 7-9.542 7-1.65 0-3.218-.342-4.675-.975M9.996 5.022a10.05 10.05 0 00-2.928.795M5.042 12c1.274-4.057 5.064-7 9.542-7 1.65 0 3.218.342 4.675.975M12 5v0"></path>
                                </svg>
                            </button>
                        </div>

                        <?php
                        $textColorClass = 'text-green-600'; // Default untuk sisa positif
                        if ($remainingBudget < 0) {
                            $textColorClass = 'text-red-600'; // Merah jika defisit
                        } elseif ($remainingBudget == 0) {
                            $textColorClass = 'text-blue-600'; // Biru atau netral jika pas
                        }
                        ?>
                        <p class="text-3xl font-bold <?= $textColorClass ?>">
                            <span id="actualBudget" class="budget-value">Rp <?= number_format($remainingBudget, 0, ',', '.') ?></span>
                            <span id="hiddenBudget" class="budget-value hidden">*****</span>
                        </p>
                    </div>
                    <svg class="w-10 h-10 <?= $textColorClass ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between transition-transform transform hover:scale-105 card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Rekomendasi Menu</p>
                        <p class="text-3xl font-bold text-purple-600">Siap!</p>
                    </div>
                    <svg class="w-10 h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </section>

            <section class="mb-10">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Kebiasaanmu Hari Ini</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($dailyHabits)): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md col-span-full text-center text-gray-600">
                            Belum ada kebiasaan harian yang diatur atau tidak ada kebiasaan 'Setiap Hari' / 'Setiap Malam'.
                        </div>
                    <?php else: ?>
                        <?php foreach ($dailyHabits as $habit):
                            // Logika untuk menentukan apakah kebiasaan selesai hari ini
                            // Menggunakan last_completed_date dan status dari tabel habits Anda
                            $isCompletedToday = ($habit['status'] == 'completed' && $habit['last_completed_date'] == $today);

                            $progressText = "Belum dimulai";
                            $borderColor = 'border-yellow-500'; // Default: Belum dimulai
                            $buttonBgColor = 'bg-yellow-100';
                            $buttonTextColor = 'text-yellow-700';
                            $buttonRingColor = 'focus:ring-yellow-500';
                            $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>'; // Ikon jam (belum selesai)

                            if ($isCompletedToday) {
                                $progressText = "Selesai!";
                                $borderColor = 'border-green-500'; // Selesai: Hijau
                                $buttonBgColor = 'bg-green-100';
                                $buttonTextColor = 'text-green-700';
                                $buttonRingColor = 'focus:ring-green-500';
                                $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'; // Ikon centang (selesai)
                            } else {
                                // Jika ada kolom `description` yang berisi target, Anda bisa parsing di sini
                                // Contoh: "Minum 8 gelas air setiap hari." -> ambil "8 gelas"
                                if (!empty($habit['description']) && strpos(strtolower($habit['description']), 'target') !== false) {
                                    // Ini adalah parsing string yang sangat dasar.
                                    // Idealnya, target dan unit harus di kolom terpisah di DB.
                                    $progressText = "Target: " . htmlspecialchars($habit['description']); // Gunakan description sebagai target sementara
                                    $borderColor = 'border-indigo-500'; // Sedang berjalan: Indigo
                                    $buttonBgColor = 'bg-indigo-100';
                                    $buttonTextColor = 'text-indigo-700';
                                    $buttonRingColor = 'focus:ring-indigo-500';
                                    $iconPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'; // Ikon centang (bisa berarti "mark as complete")
                                }
                            }

                            // Jika status kebiasaan secara umum adalah 'active' dan belum selesai hari ini
                            // Tapi jika sudah completed di DB, itu harusnya jadi hijau.
                            // Logika di atas sudah menangani isCompletedToday.
                            // Jika kebiasaan belum completed hari ini, tapi statusnya 'active' atau 'pending'
                            // maka dia tetap default ke yellow/indigo.
                        ?>
                            <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between border-l-4 <?= $borderColor ?> hover:shadow-lg transition-shadow duration-200">
                                <div>
                                    <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($habit['habit_name']) ?></p>
                                    <p class="text-sm text-gray-500"><?= $progressText ?></p>
                                </div>
                                <button class="<?= $buttonBgColor ?> <?= $buttonTextColor ?> p-2 rounded-full hover:<?= str_replace('100', '200', $buttonBgColor) ?> focus:outline-none focus:ring-2 <?= $buttonRingColor ?> mark-habit-btn" data-habit-id="<?= $habit['habit_id'] ?>" data-is-completed-today="<?= $isCompletedToday ? 'true' : 'false' ?>">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <?= $iconPath ?>
                                    </svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section>
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Rekomendasi AI untukmu</h3>
                <div id="recommendations-container" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 flex flex-col justify-between" id="lunch-recommendation">
                        <h4 class="text-xl font-semibold text-purple-700 mb-3">Memuat Rekomendasi Menu Makan Siang...</h4>
                        <p class="text-gray-600 text-sm">Harap tunggu sebentar.</p>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 flex flex-col justify-between" id="exercise-recommendation">
                        <h4 class="text-xl font-semibold text-green-700 mb-3">Memuat Rekomendasi Latihan Fisik...</h4>
                        <p class="text-gray-600 text-sm">Harap tunggu sebentar.</p>
                    </div>
                </div>
                <div class="text-center mt-8">
                    <button id="refresh-button" class="bg-blue-500 text-white py-3 px-6 rounded-lg text-lg hover:bg-blue-600 transition-colors duration-200">
                        Refresh Rekomendasi
                    </button>
                    <input type="text" id="user-mood" placeholder="Masukkan mood Anda (misal: energic)" class="mt-4 p-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
            </section>
        </main>
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

        <div id="page-loader" class="page-loader hidden opacity-0 transition-opacity duration-300">
            <div class="spinner"></div>
        </div>
    </div>



    <script src="./src/js/main.js"></script>
    <script src="./src/js/theme_handler.js"></script>

    <script>
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



            const toggleButton = document.getElementById('toggleBudgetVisibility');
            const actualBudget = document.getElementById('actualBudget');
            const hiddenBudget = document.getElementById('hiddenBudget');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeSlashIcon = document.getElementById('eyeSlashIcon');

            // Key untuk localStorage
            const BUDGET_VISIBILITY_KEY = 'budget_visibility';

            // Inisialisasi status visibilitas dari localStorage
            // Default: 'visible' jika belum ada di localStorage
            let isBudgetHidden = localStorage.getItem(BUDGET_VISIBILITY_KEY) === 'hidden';

            function updateVisibility() {
                if (isBudgetHidden) {
                    actualBudget.classList.add('hidden');
                    hiddenBudget.classList.remove('hidden');
                    eyeIcon.classList.add('hidden');
                    eyeSlashIcon.classList.remove('hidden');
                    localStorage.setItem(BUDGET_VISIBILITY_KEY, 'hidden');
                } else {
                    actualBudget.classList.remove('hidden');
                    hiddenBudget.classList.add('hidden');
                    eyeIcon.classList.remove('hidden');
                    eyeSlashIcon.classList.add('hidden');
                    localStorage.setItem(BUDGET_VISIBILITY_KEY, 'visible');
                }
            }

            // Panggil saat halaman dimuat untuk menerapkan status awal
            updateVisibility();

            // Tambahkan event listener ke tombol
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    isBudgetHidden = !isBudgetHidden; // Toggle status
                    updateVisibility(); // Perbarui tampilan
                });
            }

        });
    </script>
</body>

</html>