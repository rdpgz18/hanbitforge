<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php"; // Sertakan fungsi-fungsi keuanga

$user_id = $_SESSION['user_id'];

// Default date range: Last 30 days
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Check if form submitted for custom date range
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $input_start_date = $_GET['start_date'];
    $input_end_date = $_GET['end_date'];

    // Basic validation to prevent SQL injection and ensure valid dates
    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $input_start_date) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $input_end_date)) {
        if (strtotime($input_start_date) && strtotime($input_end_date)) {
            $start_date = $input_start_date;
            $end_date = $input_end_date;
        }
    }
}

// Get transactions and totals for the selected date range
$transactions = getTransactionsByDateRange($pdo, $user_id, $start_date, $end_date);
$totals = getTotalsByDateRange($pdo, $user_id, $start_date, $end_date);

$totalIncome = $totals['income'];
$totalExpense = $totals['expense'];
$netBalance = $totalIncome - $totalExpense;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Riwayat Transaksi</title>
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
        <main class="flex-1 p-4 sm:p-6 md:p-10">
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                <div class="flex items-center w-full sm:w-auto justify-between mb-4 sm:mb-0">
                    <button id="open-menu-btn" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 text-center sm:text-left">Riwayat Transaksi</h2>
                </div>
            </header>

            <section class="bg-white p-4 sm:p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">Filter dan Unduh Riwayat</h3>
                <form id="filterForm" method="GET" action="transaction_history.php" class="flex flex-col md:flex-row items-stretch md:items-end gap-4">
                    <div class="w-full md:w-auto flex-1"> <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Dari Tanggal:</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="w-full md:w-auto flex-1"> <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Sampai Tanggal:</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="w-full md:w-auto mt-2 md:mt-0 flex flex-col sm:flex-row gap-3"> <button type="submit" class="w-full sm:w-auto bg-indigo-600 text-white py-2 px-4 rounded-lg text-base font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md"> Terapkan Filter
                        </button>
                        <button type="button" id="downloadPdfBtn" class="w-full sm:w-auto bg-purple-600 text-white py-2 px-4 rounded-lg text-base font-semibold hover:bg-purple-700 transition-colors duration-200 shadow-md flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H4a2 2 0 01-2-2V6a2 2 0 012-2h7l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2z"></path>
                            </svg>
                            Unduh PDF
                        </button>
                    </div>
                </form>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 mb-8">
                <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
                    <p class="text-gray-500 text-sm">Total Pemasukan</p>
                    <p class="text-2xl sm:text-3xl font-bold text-green-600 mt-2"><?= formatRupiah($totalIncome) ?></p>
                </div>
                <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
                    <p class="text-gray-500 text-sm">Total Pengeluaran</p>
                    <p class="text-2xl sm:text-3xl font-bold text-red-600 mt-2"><?= formatRupiah($totalExpense) ?></p>
                </div>
                <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
                    <p class="text-gray-500 text-sm">Saldo Bersih</p>
                    <p class="text-2xl sm:text-3xl font-bold <?= $netBalance >= 0 ? 'text-indigo-600' : 'text-red-600' ?> mt-2"><?= formatRupiah($netBalance) ?></p>
                </div>
            </section>

            <section>
                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Daftar Transaksi (<?= htmlspecialchars(date('d M Y', strtotime($start_date))) ?> - <?= htmlspecialchars(date('d M Y', strtotime($end_date))) ?>)</h3>
                <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md overflow-x-auto"> <?php if (empty($transactions)): ?>
                        <p class="text-gray-500 text-center py-8">Tidak ada transaksi yang ditemukan untuk periode ini.</p>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Kategori</th>
                                    <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Tipe</th>
                                    <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-3 py-2 sm:px-6 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                            <?= htmlspecialchars(date('d M Y', strtotime($transaction['transaction_date']))) ?>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                            <?= htmlspecialchars($transaction['description']) ?>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden sm:table-cell">
                                            <?= htmlspecialchars($transaction['category'] ?? '-') ?>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-4 whitespace-nowrap text-xs sm:text-sm hidden sm:table-cell">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $transaction['type'] == 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= ucfirst($transaction['type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-4 whitespace-nowrap text-right text-xs sm:text-sm font-medium <?= $transaction['type'] == 'expense' ? 'text-red-600' : 'text-green-600' ?>">
                                            <?= $transaction['type'] == 'expense' ? '-' : '+' ?> <?= formatRupiah($transaction['amount']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    <div id="notification-container"></div>

    <div id="page-loader" class="page-loader hidden opacity-0 transition-opacity duration-300">
        <div class="spinner"></div>
    </div>

    <script src="../src/js/main.js"></script>
    <script src="../src/js/theme_handler.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reusable Notification Function
            function showNotification(message, type = 'info', duration = 3000) {
                const container = document.getElementById('notification-container');
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;

                container.appendChild(notification);

                void notification.offsetWidth;
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

            // Sidebar toggle (if you have one)
            const openMenuBtn = document.getElementById('open-menu-btn');
            const sidebar = document.getElementById('sidebar');
            if (openMenuBtn && sidebar) {
                openMenuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                });
            }

            // Download PDF Button Logic
            const downloadPdfBtn = document.getElementById('downloadPdfBtn');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            downloadPdfBtn.addEventListener('click', function() {
                const start_date = startDateInput.value;
                const end_date = endDateInput.value;

                // Construct the URL for PDF generation
                const pdfUrl = `../actions/generate_transaction_pdf.php?start_date=${start_date}&end_date=${end_date}`;

                // Open in new tab or trigger download
                window.open(pdfUrl, '_blank');
                showNotification('Mulai mengunduh riwayat transaksi PDF...', 'info');
            });
        });
    </script>
</body>

</html>