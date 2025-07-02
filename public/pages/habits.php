<?php

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

$user_id = $_SESSION['user_id'];
// Panggil fungsi auto-update status kebiasaan di sini
// Panggil fungsi autoUpdateHabitStatuses setiap kali user mengakses halaman ini
// Ini akan memastikan status kebiasaan dan streak diperbarui sesuai tanggal
autoUpdateHabitStatuses($pdo, $user_id);

// Ambil kebiasaan terbaru setelah update otomatis
$habits = getHabitsByUserId($pdo, $user_id);

// Tangani AJAX request untuk menyelesaikan kebiasaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_habit') {
    $habit_id = $_POST['habit_id'] ?? 0;
    if ($habit_id > 0) {
        if (markHabitAsCompleted($pdo, $habit_id, $user_id)) {
            echo json_encode(['success' => true, 'message' => 'Kebiasaan berhasil diselesaikan!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyelesaikan kebiasaan atau sudah selesai hari ini.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID kebiasaan tidak valid.']);
    }
    exit(); // Penting untuk menghentikan eksekusi setelah merespons AJAX
}


// --- START: Perubahan untuk Filter dan Search ---
$filter_status = $_GET['filter'] ?? 'Semua'; // Ambil nilai filter dari URL, default 'Semua'
$search_query = $_GET['search'] ?? '';       // Ambil nilai search dari URL, default kosong

// Sesuaikan query SQL berdasarkan filter dan search
$sql = "SELECT * FROM habits WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if ($filter_status !== 'Semua') {
    // Sesuaikan nilai filter dari HTML option ke nilai di database
    $db_filter_status = '';
    if ($filter_status === 'Aktif') {
        $db_filter_status = 'active';
    } elseif ($filter_status === 'Selesai') {
        $db_filter_status = 'completed';
    } elseif ($filter_status === 'Tertunda') {
        $db_filter_status = 'pending';
    }

    if (!empty($db_filter_status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $db_filter_status;
    }
}

if (!empty($search_query)) {
    $sql .= " AND (habit_name LIKE :search_query OR description LIKE :search_query)";
    $params[':search_query'] = '%' . $search_query . '%'; // Tambahkan wildcard untuk LIKE
}

$sql .= " ORDER BY created_at DESC"; // Urutkan hasil

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        // Tentukan tipe data secara eksplisit untuk bindParam (penting untuk LIKE)
        if ($key === ':user_id') {
            $stmt->bindParam($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindParam($key, $val, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching habits with filter/search: " . $e->getMessage());
    $habits = []; // Kosongkan jika ada error
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Kebiasaan</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>


    <link href="../src/css/tailwind.css" rel="stylesheet">
    <link href="../src/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/theme.css">

    <script src="../src/js/tailwind.config.js"></script>
    <link rel="shortcut icon" href="../assets/icon/pavicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .card-enter {
            opacity: 0;
            transform: translateY(20px);
        }

        .card-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        /* Styling untuk Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s;
        }

        .modal.show {
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            background-color: white;
            padding: 2.5rem;
            /* 40px */
            border-radius: 0.75rem;
            /* 12px */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            transform: translateY(-20px);
            transition: transform 0.3s ease-out;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        /* Styling untuk Notifikasi Pop-up */
        #notification-container {
            position: fixed;
            top: 20px;
            /* Jarak dari atas */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            /* Pastikan di atas elemen lain */
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            /* Agar tidak menghalangi klik di bawahnya */
        }

        .notification {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
            pointer-events: auto;
            /* Aktifkan pointer-events untuk notifikasi itu sendiri */
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .notification.success {
            border-left: 5px solid #10B981;
            /* Tailwind green-500 */
        }

        .notification.error {
            border-left: 5px solid #EF4444;
            /* Tailwind red-500 */
        }

        .notification-icon {
            font-size: 1.5rem;
        }

        .notification-icon.success::before {
            content: '✓';
            /* Tanda centang */
            color: #10B981;
            font-weight: bold;
        }

        .notification-icon.error::before {
            content: '✕';
            /* Tanda silang */
            color: #EF4444;
            font-weight: bold;
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
                        <a href="../dashboard.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 010-10"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="./habits.php" class="flex items-center p-3 rounded-lg text-indigo-700 bg-indigo-100 font-semibold hover:bg-indigo-200 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Kebiasaan
                        </a>
                    </li>
                    <li>
                        <a href="./exercise.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Olahraga
                        </a>
                    </li>
                    <li>
                        <a href="./finance.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Keuangan
                        </a>
                    </li>
                    <li>
                        <a href="./nutrition.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Nutrisi
                        </a>
                    </li>
                    <li>
                        <a href="./settings.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
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
                <a href="../logout.php" class="block w-full bg-red-100 text-red-700 py-2 px-4 rounded-lg hover:bg-red-200 transition-colors duration-200 text-center">
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
                    <h2 class="text-4xl font-bold text-gray-900 ">Kebiasaanmu</h2>
                </div>
                <button id="add-habit-btn" class="bg-indigo-600 text-white py-2 px-5 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Kebiasaan Baru
                </button>
            </header>

            <section class="mb-8 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center space-x-2 w-full sm:w-auto">
                    <label for="filter" class="text-gray-600 text-sm">Filter:</label>
                    <select id="filter" class="p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:w-auto">
                        <option value="Semua" <?= $filter_status == 'Semua' ? 'selected' : '' ?>>Semua</option>
                        <option value="Aktif" <?= $filter_status == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Selesai" <?= $filter_status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="Tertunda" <?= $filter_status == 'Tertunda' ? 'selected' : '' ?>>Tertunda</option>
                    </select>
                </div>
                <div class="relative w-full sm:w-auto">
                    <input type="text" id="search-input" placeholder="Cari kebiasaan..." value="<?= htmlspecialchars($search_query) ?>"
                        class="w-full p-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($habits)): ?>
                    <?php foreach ($habits as $habit):
                        $progress = calculateProgress($habit['current_streak'], $habit['frequency']);
                        $status_class = $habit['status'] === 'completed' ? 'bg-green-100 border-green-400' : 'bg-white border-gray-200';
                        $status_text_class = $habit['status'] === 'completed' ? 'text-green-600' : 'text-indigo-600';
                    ?>
                        <div class="habit-card p-6 rounded-lg shadow-md border <?php echo $status_class; ?>"
                            data-status="<?php echo htmlspecialchars($habit['status']); ?>"
                            data-name="<?php echo htmlspecialchars(strtolower($habit['habit_name'])); ?>">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($habit['habit_name']); ?></h3>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_text_class; ?> bg-opacity-20"><?php echo htmlspecialchars(ucfirst($habit['status'])); ?></span>
                            </div>
                            <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($habit['description']); ?></p>
                            <p class="text-gray-600 text-sm mb-4">Frekuensi: <?php echo htmlspecialchars($habit['frequency']); ?></p>

                            <div class="mb-4">
                                <p class="text-gray-700 text-sm mb-1">Streak: <?php echo htmlspecialchars($habit['current_streak']); ?> Hari</p>
                                <?php if (isset($habit['best_streak'])): ?>
                                    <p class="text-gray-700 text-sm mb-1">Rekor Streak Terbaik: <?php echo htmlspecialchars($habit['best_streak']); ?> Hari</p>
                                <?php endif; ?>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <p class="text-gray-500 text-xs mt-1 text-right"><?php echo $progress; ?>% Progres</p>
                            </div>

                            <div class="flex justify-between items-center mt-4">
                                <button type="button" class="edit-button text-indigo-600 hover:underline text-sm font-semibold"
                                    data-habit-id="<?php echo $habit['habit_id']; ?>"
                                    data-habit-name="<?php echo htmlspecialchars($habit['habit_name']); ?>"
                                    data-description="<?php echo htmlspecialchars($habit['description']); ?>"
                                    data-frequency="<?php echo htmlspecialchars($habit['frequency']); ?>">
                                    Edit
                                </button>
                                <?php if ($habit['status'] !== 'completed'): ?>
                                    <button type="button" class="complete-button bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline text-sm"
                                        data-habit-id="<?php echo $habit['habit_id']; ?>">
                                        Selesai Hari Ini
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="bg-green-600 text-white font-bold py-2 px-4 rounded-md opacity-75 cursor-not-allowed text-sm" disabled>
                                        Sudah Selesai
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="col-span-full text-center text-gray-600 text-lg">Belum ada kebiasaan yang dibuat. Klik "Tambah Kebiasaan Baru" untuk memulai!</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <div id="add-habit-modal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Tambah Kebiasaan Baru</h3>
                <button id="close-modal-btn" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="add-habit-form" class="space-y-5">
                <div>
                    <label for="habit_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kebiasaan</label>
                    <input type="text" id="habit_name" name="habit_name" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="3"
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label for="frequency" class="block text-sm font-medium text-gray-700 mb-1">Frekuensi</label>
                    <select id="frequency" name="frequency" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Pilih Frekuensi</option>
                        <option value="Setiap Hari">Setiap Hari</option>
                        <option value="Setiap Minggu">Setiap Minggu</option>
                        <option value="Setiap Bulan">Setiap Bulan</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" id="cancel-add-habit-btn" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors duration-200">
                        Simpan Kebiasaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-habit-modal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Kebiasaan</h3>
                <button id="close-edit-modal-btn" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="edit-habit-form" class="space-y-5">
                <input type="hidden" id="edit_habit_id" name="habit_id">
                <div>
                    <label for="edit_habit_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kebiasaan</label>
                    <input type="text" id="edit_habit_name" name="habit_name" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="edit_description" name="description" rows="3"
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label for="edit_frequency" class="block text-sm font-medium text-gray-700 mb-1">Frekuensi</label>
                    <select id="edit_frequency" name="frequency" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Pilih Frekuensi</option>
                        <option value="Setiap Hari">Setiap Hari</option>
                        <option value="Setiap Minggu">Setiap Minggu</option>
                        <option value="Setiap Bulan">Setiap Bulan</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" id="cancel-edit-habit-btn" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors duration-200">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification-container">
    </div>


    <script src="../src/js/main.js"></script>
    <script src="../src/js/theme_handler.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addHabitBtn = document.getElementById('add-habit-btn');
            const addHabitModal = document.getElementById('add-habit-modal');
            const closeModalBtn = document.getElementById('close-modal-btn');
            const cancelAddHabitBtn = document.getElementById('cancel-add-habit-btn');
            const addHabitForm = document.getElementById('add-habit-form');
            const notificationContainer = document.getElementById('notification-container');

            // Fungsi untuk menampilkan modal
            function showModal() {
                addHabitModal.classList.add('show');
            }

            // Fungsi untuk menyembunyikan modal
            function hideModal() {
                addHabitModal.classList.remove('show');
                addHabitForm.reset(); // Reset form saat modal ditutup
            }

            // Fungsi untuk menampilkan notifikasi pop-up
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.classList.add('notification', type);
                notification.innerHTML = `
                    <span class="notification-icon">${type === 'success' ? '&#10003;' : '&#x2716;'}</span>
                    <span>${message}</span>
                `;
                notificationContainer.appendChild(notification);

                // Tampilkan notifikasi dengan animasi
                setTimeout(() => {
                    notification.classList.add('show');
                }, 10); // Sedikit delay untuk transisi

                // Sembunyikan notifikasi setelah 3 detik
                setTimeout(() => {
                    notification.classList.remove('show');
                    // Hapus elemen setelah transisi selesai
                    notification.addEventListener('transitionend', () => {
                        notification.remove();
                    });
                }, 3000);
            }

            // Event listener untuk tombol "Tambah Kebiasaan Baru"
            addHabitBtn.addEventListener('click', showModal);

            // Event listener untuk tombol tutup modal
            closeModalBtn.addEventListener('click', hideModal);
            cancelAddHabitBtn.addEventListener('click', hideModal);

            // Menutup modal saat klik di luar area modal content
            addHabitModal.addEventListener('click', function(event) {
                if (event.target === addHabitModal) {
                    hideModal();
                }
            });

            // Handle submit form
            addHabitForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Mencegah reload halaman

                const formData = new FormData(addHabitForm);

                fetch('../actions/add_habit.php', { // Sesuaikan path ke add_habit.php
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            hideModal();
                            // Opsional: Muat ulang kebiasaan atau tambahkan ke DOM secara dinamis
                            // Untuk saat ini, kita bisa refresh halaman untuk melihat perubahan
                            setTimeout(() => {
                                location.reload();
                            }, 1000); // Beri waktu notifikasi terlihat sebelum refresh
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Terjadi kesalahan jaringan atau server.', 'error');
                    });
            });

            // Fungsi untuk menampilkan notifikasi pop-up (pastikan ini ada, jika belum)
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.classList.add('notification', type);
                notification.innerHTML = `
                    <span class="notification-icon ${type}"></span>
                    <span>${message}</span>
                `;
                notificationContainer.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);

                setTimeout(() => {
                    notification.classList.remove('show');
                    notification.addEventListener('transitionend', () => {
                        notification.remove();
                    });
                }, 3000);
            }

            // Event listener untuk tombol "Selesai Hari Ini" / "Tandai Selesai"
            document.querySelectorAll('.complete-button').forEach(button => {
                button.addEventListener('click', function() {
                    const habitId = this.dataset.habitId;
                    const currentButton = this; // Simpan referensi ke tombol yang diklik

                    // Nonaktifkan tombol untuk mencegah klik ganda
                    currentButton.disabled = true;
                    currentButton.textContent = 'Memproses...';

                    const formData = new FormData();
                    formData.append('habit_id', habitId);
                    formData.append('action_type', 'complete'); // Atau 'mark_complete'

                    fetch('../actions/complete_habit.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message, 'success');
                                // Opsional: Perbarui UI tanpa reload halaman
                                // Temukan elemen kartu kebiasaan (parent dari tombol)
                                const habitCard = currentButton.closest('.bg-white.p-6');
                                if (habitCard) {
                                    // Perbarui status teks
                                    const statusSpan = habitCard.querySelector('.text-xs.font-semibold');
                                    if (statusSpan) {
                                        statusSpan.textContent = 'Completed'; // Atau data.new_status
                                        statusSpan.classList.remove('bg-indigo-100', 'text-indigo-700', 'bg-yellow-100', 'text-yellow-700');
                                        statusSpan.classList.add('bg-green-100', 'text-green-700');
                                    }

                                    // Perbarui streak
                                    const streakSpan = habitCard.querySelector('span:nth-child(2)'); // Sesuaikan selektor jika perlu
                                    if (streakSpan) {
                                        const oldText = streakSpan.textContent;
                                        const newText = oldText.replace(/Streak: \d+ Hari/, `Streak: ${data.new_streak} Hari`);
                                        streakSpan.textContent = newText + ' ✨'; // Tambahkan emoji streak
                                    }

                                    // Update border color
                                    habitCard.classList.remove('border-indigo-500', 'border-yellow-500');
                                    habitCard.classList.add('border-green-500');

                                    // Nonaktifkan tombol karena sudah selesai hari ini
                                    currentButton.disabled = true;
                                    currentButton.classList.remove('bg-indigo-500', 'hover:bg-indigo-600', 'bg-yellow-500', 'hover:bg-yellow-600');
                                    currentButton.classList.add('bg-gray-200', 'text-gray-600', 'cursor-not-allowed');
                                    currentButton.textContent = 'Selesai Hari Ini'; // Karena umumnya akan menjadi ini
                                }
                            } else {
                                showNotification(data.message, 'error');
                                // Aktifkan kembali tombol jika gagal
                                currentButton.disabled = false;
                                currentButton.textContent = currentButton.dataset.originalText || 'Gagal'; // Jika ada text original
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Terjadi kesalahan jaringan atau server.', 'error');
                            // Aktifkan kembali tombol jika error
                            currentButton.disabled = false;
                            currentButton.textContent = currentButton.dataset.originalText || 'Gagal';
                        });
                });
            });

            // Elemen modal edit
            const editHabitModal = document.getElementById('edit-habit-modal');
            const closeEditModalBtn = document.getElementById('close-edit-modal-btn');
            const cancelEditHabitBtn = document.getElementById('cancel-edit-habit-btn');
            const editHabitForm = document.getElementById('edit-habit-form');

            // Input fields di modal edit
            const editHabitIdInput = document.getElementById('edit_habit_id');
            const editHabitNameInput = document.getElementById('edit_habit_name');
            const editDescriptionInput = document.getElementById('edit_description');
            const editFrequencySelect = document.getElementById('edit_frequency');

            // Fungsi untuk menampilkan modal edit
            function showEditModal() {
                editHabitModal.classList.add('show');
            }

            // Fungsi untuk menyembunyikan modal edit
            function hideEditModal() {
                editHabitModal.classList.remove('show');
                editHabitForm.reset(); // Reset form saat modal ditutup
            }

            // Event listener untuk tombol tutup modal edit
            closeEditModalBtn.addEventListener('click', hideEditModal);
            cancelEditHabitBtn.addEventListener('click', hideEditModal);

            // Menutup modal edit saat klik di luar area modal content
            editHabitModal.addEventListener('click', function(event) {
                if (event.target === editHabitModal) {
                    hideEditModal();
                }
            });

            // Event listener untuk tombol "Edit" pada setiap kartu kebiasaan
            document.querySelectorAll('.edit-button').forEach(button => {
                button.addEventListener('click', function() {
                    const habitId = this.dataset.habitId; // Ambil ID kebiasaan dari data-attribute

                    // Mengambil detail kebiasaan dari server
                    fetch(`../actions/get_habit_details.php?habit_id=${habitId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.habit) {
                                // Isi formulir modal dengan data yang diterima
                                editHabitIdInput.value = data.habit.habit_id;
                                editHabitNameInput.value = data.habit.habit_name;
                                editDescriptionInput.value = data.habit.description;
                                editFrequencySelect.value = data.habit.frequency;

                                showEditModal(); // Tampilkan modal setelah data terisi
                            } else {
                                showNotification(data.message || 'Gagal mengambil detail kebiasaan.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching habit details:', error);
                            showNotification('Terjadi kesalahan jaringan saat mengambil detail kebiasaan.', 'error');
                        });
                });
            });

            // Handle submit form edit kebiasaan
            editHabitForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Mencegah reload halaman

                const formData = new FormData(editHabitForm);

                fetch('../actions/update_habit.php', { // Sesuaikan path ke update_habit.php
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            hideEditModal();
                            // Opsional: Perbarui UI pada kartu kebiasaan yang relevan tanpa reload halaman
                            // Ini akan lebih kompleks karena melibatkan update banyak elemen.
                            // Untuk saat ini, kita bisa refresh halaman untuk melihat perubahan
                            setTimeout(() => {
                                location.reload();
                            }, 1000); // Beri waktu notifikasi terlihat sebelum refresh
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Terjadi kesalahan jaringan atau server.', 'error');
                    });
            });

            // ... (Kode JavaScript sebelumnya untuk modal add/edit dan notifikasi) ...

            const filterSelect = document.getElementById('filter');
            const searchInput = document.getElementById('search-input');

            // Fungsi untuk mengaplikasikan filter dan pencarian
            function applyFilterAndSearch() {
                const selectedFilter = filterSelect.value;
                const currentSearchQuery = searchInput.value;

                // Buat URL baru dengan parameter filter dan search
                const url = new URL(window.location.href);
                url.searchParams.set('filter', selectedFilter);

                if (currentSearchQuery) {
                    url.searchParams.set('search', currentSearchQuery);
                } else {
                    url.searchParams.delete('search'); // Hapus parameter jika kosong
                }

                // Arahkan browser ke URL baru
                window.location.href = url.toString();
            }

            // Event listener untuk perubahan pada filter
            filterSelect.addEventListener('change', applyFilterAndSearch);

            // Event listener untuk input pencarian (misal, saat menekan Enter atau setelah delay)
            // Kita bisa menggunakan 'input' event dengan debounce untuk performa yang lebih baik
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout); // Hapus timeout sebelumnya
                searchTimeout = setTimeout(() => {
                    applyFilterAndSearch();
                }, 500); // Terapkan setelah 500ms tidak ada input
            });

            // Atau jika hanya ingin saat Enter:
            /*
            searchInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    applyFilterAndSearch();
                }
            });
            */

            // Event listener untuk tombol "Mulai Kebiasaan"
            document.querySelectorAll('.start-habit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const habitId = this.dataset.habitId;
                    const habitName = this.dataset.habitName;
                    const habitFrequency = this.dataset.habitFrequency;

                    // Alihkan ke halaman timer dengan parameter yang relevan
                    // Anda perlu menambahkan input durasi ke form 'Tambah Kebiasaan' dan 'Edit Kebiasaan'
                    // Untuk contoh ini, kita hardcode durasi 1 menit untuk 'Menit' dan 10 detik untuk 'Detik'
                    // Idealnya, durasi ini diambil dari database atau input user
                    let durationSeconds = 0;
                    if (habitFrequency === 'Menit') {
                        // Jika Anda memiliki input durasi di database/form, gunakan itu
                        // Misalnya, data-habit-duration="5" (untuk 5 menit)
                        // durationSeconds = parseInt(this.dataset.habitDuration) * 60;
                        durationSeconds = 1 * 60; // Contoh: 1 menit
                    } else if (habitFrequency === 'Detik') {
                        // durationSeconds = parseInt(this.dataset.habitDuration);
                        durationSeconds = 10; // Contoh: 10 detik
                    }

                    if (durationSeconds > 0) {
                        // EncodeURIComponent untuk memastikan nilai aman dalam URL
                        window.location.href = `timer.php?habit_id=${encodeURIComponent(habitId)}&name=${encodeURIComponent(habitName)}&duration=${encodeURIComponent(durationSeconds)}`;
                    } else {
                        showNotification('Kebiasaan ini tidak memiliki durasi timer yang ditentukan.', 'error');
                    }
                });
            });
        });
    </script>
</body>

</html>