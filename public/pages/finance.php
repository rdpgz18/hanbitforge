<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

$user_id = $_SESSION['user_id'];

// Ambil data ringkasan bulanan
$summary = getMonthlyFinancialSummary($pdo, $user_id);
$totalIncome = $summary['total_income'];
$totalExpense = $summary['total_expense'];
$balance = $summary['balance'];

// Ambil riwayat transaksi terbaru
$recentTransactions = getRecentTransactions($pdo, $user_id, 5); // Ambil 5 transaksi terakhir

// Ambil data pengeluaran per kategori
$rawCategoryExpenses = getMonthlyCategoryExpenses($pdo, $user_id); // Ganti nama variabel agar lebih jelas
$totalMonthExpense = $totalExpense; // Total pengeluaran bulan ini dari summary

// Siapkan data untuk chart/progress bar kategori
$categoryPercentages = [];
if ($totalMonthExpense > 0) {
    foreach ($rawCategoryExpenses as $cat) {
        $percentage = ($cat['total_amount'] / $totalMonthExpense) * 100;
        $categoryPercentages[] = [
            'category' => $cat['category'],
            'percentage' => round($percentage),
            'amount' => $cat['total_amount'] // Pastikan 'amount' selalu ada di sini
        ];
    }
}

// Contoh kategori default jika tidak ada transaksi (untuk tampilan konsisten)
$defaultCategoriesConfig = [
    ['category' => 'Makanan', 'color_class' => 'bg-red'],
    ['category' => 'Transportasi', 'color_class' => 'bg-blue'],
    ['category' => 'Hiburan', 'color_class' => 'bg-yellow'],
    ['category' => 'Tagihan', 'color_class' => 'bg-green'],
    ['category' => 'Lainnya', 'color_class' => 'bg-gray'],
];

// Gabungkan/timpa default dengan data asli jika ada
$displayCategories = [];
$actualCategoriesData = []; // Map untuk akses cepat data aktual
foreach ($categoryPercentages as $cat) {
    $actualCategoriesData[$cat['category']] = $cat;
}

foreach ($defaultCategoriesConfig as $defCat) {
    if (isset($actualCategoriesData[$defCat['category']])) {
        // Gunakan data dari DB jika ada
        $displayCategories[] = $actualCategoriesData[$defCat['category']];
        // Tambahkan color_class dari default config jika tidak ada di DB (misal dari input kategori)
        if (!isset($displayCategories[count($displayCategories) - 1]['color_class'])) {
            $displayCategories[count($displayCategories) - 1]['color_class'] = $defCat['color_class'];
        }
    } else {
        // Gunakan default dengan persentase 0 dan amount 0 jika tidak ada transaksi untuk kategori tersebut
        $displayCategories[] = [
            'category' => $defCat['category'],
            'percentage' => 0,
            'amount' => 0, // Penting: tambahkan amount 0 di sini
            'color_class' => $defCat['color_class']
        ];
    }
}

// Tambahkan kategori lain dari DB yang tidak ada di defaultCategoriesConfig
foreach ($categoryPercentages as $cat) {
    $found = false;
    foreach ($displayCategories as $dc) {
        if ($dc['category'] == $cat['category']) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        // Tambahkan kategori baru dari DB. Beri warna default abu-abu.
        $cat['color_class'] = 'bg-gray'; // Default color for new categories
        $displayCategories[] = $cat;
    }
}


// Target tabungan (hardcoded atau ambil dari DB jika ada tabel target)
$savingsTarget = 10000000; // Contoh

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Keuanganmu</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="src/css/tailwind.css" rel="stylesheet">
    <link href="src/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/theme.css">

    <script src="../src/js/tailwind.config.js"></script>
    <link rel="shortcut icon" href="../assets/icon/pavicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1000;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
            justify-content: center;
            align-items: center;
            padding: 20px;
            /* Padding for small screens */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            /* Center modal */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            /* Max width for larger screens */
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Notifikasi */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
        }

        .notification {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            color: white;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            margin-bottom: 10px;
            /* Space between multiple notifications */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .notification.success {
            background-color: #28a745;
            /* green-600 */
        }

        .notification.error {
            background-color: #dc3545;
            /* red-600 */
        }

        .notification.info {
            background-color: #007bff;
            /* blue-600 */
        }
    </style>
</head>

<body class="bg-gray-100 flex min-h-screen">
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
                    <a href="./habits.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
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
                    <a href="./finance.php" class="flex items-center p-3 rounded-lg text-indigo-700 bg-indigo-100 font-semibold hover:bg-indigo-200 transition-colors duration-200">
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

    <div class="flex-1 flex flex-col">

        <main class="flex-1 p-6 md:p-10">
            <header class="flex flex-col sm:flex-row justify-between items-center mb-8">
                <div class="flex items-center w-full sm:w-auto justify-between mb-4 sm:mb-0">
                    <button id="open-menu-btn" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="text-4xl font-bold text-gray-900 ">Keuanganmu</h2>
                </div>
                <button id="open-transaction-modal" class="bg-indigo-600 text-white py-2 px-5 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Catat Transaksi Baru
                </button>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center card-enter card-enter-active">
                    <p class="text-gray-500 text-sm">Pendapatan Bulan Ini</p>
                    <p class="text-3xl font-bold text-green-600 mt-2"><?= formatRupiah($totalIncome) ?></p>
                    <svg class="w-12 h-12 text-green-400 mt-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center card-enter card-enter-active">
                    <p class="text-gray-500 text-sm">Pengeluaran Bulan Ini</p>
                    <p class="text-3xl font-bold text-red-600 mt-2"><?= formatRupiah($totalExpense) ?></p>
                    <svg class="w-12 h-12 text-red-400 mt-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5 5 0 006.009 2.537l3 1m0 0l3-9a5 5 0 00-6.009-2.537L3 6z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center card-enter card-enter-active">
                    <p class="text-gray-500 text-sm">Sisa Anggaran</p>
                    <p class="text-3xl font-bold <?= $balance >= 0 ? 'text-indigo-600' : 'text-red-600' ?> mt-2"><?= formatRupiah($balance) ?></p>
                    <svg class="w-12 h-12 text-indigo-400 mt-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center card-enter card-enter-active">
                    <p class="text-gray-500 text-sm">Target Tabungan</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2"><?= formatRupiah($savingsTarget) ?></p>
                    <svg class="w-12 h-12 text-purple-400 mt-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
            </section>

            <section class="mb-10">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Analisis Pengeluaran</h3>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h4 class="text-xl font-semibold text-gray-800 mb-4">Pengeluaran Berdasarkan Kategori Bulan Ini</h4>
                    <div class="space-y-4">
                        <?php if (empty($displayCategories) || $totalMonthExpense == 0): ?>
                            <p class="text-gray-500 text-center">Belum ada pengeluaran bulan ini.</p>
                        <?php else: ?>
                            <?php
                            $colors = [
                                'bg-red' => 'bg-red-500',
                                'bg-blue' => 'bg-blue-500',
                                'bg-yellow' => 'bg-yellow-500',
                                'bg-green' => 'bg-green-500',
                                'bg-purple' => 'bg-purple-500', // Tambahan warna jika diperlukan
                                'bg-indigo' => 'bg-indigo-500',
                                'bg-pink' => 'bg-pink-500',
                                'bg-gray' => 'bg-gray-500',
                            ];
                            $light_colors = [
                                'bg-red' => 'bg-red-100',
                                'bg-blue' => 'bg-blue-100',
                                'bg-yellow' => 'bg-yellow-100',
                                'bg-green' => 'bg-green-100',
                                'bg-purple' => 'bg-purple-100',
                                'bg-indigo' => 'bg-indigo-100',
                                'bg-pink' => 'bg-pink-100',
                                'bg-gray' => 'bg-gray-100',
                            ];

                            foreach ($displayCategories as $cat):
                                // Pastikan $cat['amount'] ada sebelum digunakan
                                $actual_amount = $cat['amount'] ?? 0; // Menggunakan null coalescing operator untuk default 0
                                $actual_percentage = ($totalMonthExpense > 0) ? ($actual_amount / $totalMonthExpense) * 100 : 0;
                                $display_percentage = min(100, round($actual_percentage));

                                $bar_color = $colors[$cat['color_class']] ?? 'bg-gray-500';
                                $light_bar_color = $light_colors[$cat['color_class']] ?? 'bg-gray-100';
                            ?>
                                <div class="flex items-center">
                                    <span class="w-24 text-gray-700"><?= htmlspecialchars($cat['category']) ?>:</span>
                                    <div class="flex-1 <?= $light_bar_color ?> rounded-full h-4">
                                        <div class="`<?= $bar_color ?>` h-4 rounded-full" style="width: <?= $display_percentage ?>%;"></div>
                                    </div>
                                    <span class="ml-4 font-semibold text-gray-800"><?= $display_percentage ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Riwayat Transaksi</h3>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <ul class="divide-y divide-gray-200" id="transactionList">
                        <?php if (empty($recentTransactions)): ?>
                            <li class="py-4 text-center text-gray-500">Belum ada transaksi tercatat.</li>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <li class="py-4 flex justify-between items-center">
                                    <div>
                                        <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($transaction['description']) ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars(date('d F Y', strtotime($transaction['transaction_date']))) ?> -
                                            <span class="<?= $transaction['type'] == 'expense' ? 'text-red-500' : 'text-green-500' ?>">
                                                <?= ucfirst($transaction['type']) ?>
                                            </span>
                                            <?php if (!empty($transaction['category'])): ?>
                                                <span class="text-gray-400 ml-2">(<?= htmlspecialchars($transaction['category']) ?>)</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="text-lg font-bold <?= $transaction['type'] == 'expense' ? 'text-red-600' : 'text-green-600' ?>">
                                        <?= $transaction['type'] == 'expense' ? '-' : '+' ?> <?= formatRupiah($transaction['amount']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <button class="mt-6 w-full bg-gray-100 text-indigo-700 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-semibold">
                        <a href="./transaction_history.php">
                            Lihat Lebih Banyak Transaksi

                        </a>
                    </button>
                </div>
            </section>
        </main>
    </div>

    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="modalTitle" class="text-2xl font-bold text-gray-900 mb-6">Catat Transaksi Baru</h3>
            <form id="transactionForm" method="POST" action="actions/add_transaction.php">
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
                    <input type="text" id="description" name="description" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="amount" class="block text-gray-700 text-sm font-bold mb-2">Jumlah (Rp):</label>
                    <input type="number" id="amount" name="amount" required min="1" step="0.01"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Tipe Transaksi:</label>
                    <select id="type" name="type" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pendapatan</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Kategori (Opsional):</label>
                    <input type="text" id="category" name="category"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label for="transactionDate" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Transaksi:</label>
                    <input type="date" id="transactionDate" name="transaction_date" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Transaksi
                    </button>
                    <button type="button" id="cancelTransactionBtn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification-container"></div>

    <div id="page-loader" class="page-loader hidden opacity-0 transition-opacity duration-300">
        <div class="spinner"></div>
    </div>

    <script src="../src/js/main.js"></script>
    <script src="../src/js/theme_handler.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reusable Notification Function (sama seperti di habits.php dan physical_activity.php)
            function showNotification(message, type = 'info', duration = 3000) {
                const container = document.getElementById('notification-container');
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;

                container.appendChild(notification);

                // Force reflow to enable transition
                void notification.offsetWidth; // Trigger reflow
                notification.classList.add('show');

                setTimeout(() => {
                    notification.classList.remove('show');
                    notification.addEventListener('transitionend', () => {
                        notification.remove();
                    }, {
                        once: true
                    });
                }, duration);
            }

            // --- Modal Logic for Transactions ---
            const transactionModal = document.getElementById('transactionModal');
            const openTransactionModalBtn = document.getElementById('open-transaction-modal');
            const closeBtn = transactionModal.querySelector('.close-btn'); // Select close button within this modal
            const cancelTransactionBtn = document.getElementById('cancelTransactionBtn');
            const transactionForm = document.getElementById('transactionForm');
            const modalTitle = transactionModal.querySelector('#modalTitle'); // Ensure correct modal title

            // Form fields for transactions
            const transactionDescriptionInput = document.getElementById('description');
            const transactionAmountInput = document.getElementById('amount');
            const transactionTypeSelect = document.getElementById('type');
            const transactionCategoryInput = document.getElementById('category');
            const transactionDateInput = document.getElementById('transactionDate');

            function openTransactionModal() {
                transactionModal.style.display = 'flex';
                transactionForm.reset();
                modalTitle.textContent = 'Catat Transaksi Baru';
                // Set default date to today for new entry
                transactionDateInput.value = new Date().toISOString().split('T')[0];
            }

            function closeTransactionModal() {
                transactionModal.style.display = 'none';
            }

            openTransactionModalBtn.addEventListener('click', openTransactionModal);
            closeBtn.addEventListener('click', closeTransactionModal);
            cancelTransactionBtn.addEventListener('click', closeTransactionModal);

            // Close modal when clicking outside of it
            window.addEventListener('click', (event) => {
                if (event.target === transactionModal) {
                    closeTransactionModal();
                }
            });

            // --- Form Submission (AJAX) for Transactions ---
            transactionForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);
                const actionUrl = '../actions/add_transaction.php'; // Hanya ada tambah transaksi untuk saat ini

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showNotification(data.message, 'success');
                        closeTransactionModal();
                        setTimeout(() => location.reload(), 1000); // Reload page to show new data
                    } else {
                        showNotification(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error submitting transaction form:', error);
                    showNotification('Terjadi kesalahan jaringan atau server.', 'error');
                }
            });

            // Sidebar toggle (if you have one)
            const openMenuBtn = document.getElementById('open-menu-btn');
            const sidebar = document.getElementById('sidebar'); // Make sure your sidebar has this ID
            if (openMenuBtn && sidebar) {
                openMenuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                });
            }
        });
    </script>
</body>

</html>