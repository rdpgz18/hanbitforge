<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";


$user_id = $_SESSION['user_id'];

// Ambil tanggal saat ini atau dari parameter URL jika ada
$currentDate = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d');

// --- DEBUGGING ---
echo "\n";
echo "\n";
echo "\n";

// Ambil total nutrisi harian
// Pastikan $pdo sudah terinisialisasi dengan benar dari config.php
if (isset($pdo) && $pdo instanceof PDO) {
    $dailyNutrientTotals = getDailyNutrientTotals($pdo, $user_id, $currentDate);
    $dailyFoodEntries = getDailyFoodEntries($pdo, $user_id, $currentDate);

    echo "\n";

    echo "\n";
} else {
    // Jika $pdo tidak terinisialisasi
    $dailyNutrientTotals = [
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbohydrates' => 0,
        'total_fats' => 0
    ];
    $dailyFoodEntries = [];
    echo "\n";
}
// --- END DEBUGGING ---


// Rekomendasi menu harian (masih statis, bisa dari database juga di masa depan)
$recommendedMenus = [
    [
        'meal' => 'Sarapan',
        'name' => 'Oatmeal Buah Berry',
        'description' => 'Sumber karbohidrat kompleks dan antioksidan.',
        'calories' => 250,
        'protein' => 8,
        'carbs' => 40,
        'fats' => 6,
        'color_class' => 'border-indigo-500',
        'button_color' => 'bg-indigo-500'
    ],
    [
        'meal' => 'Makan Siang',
        'name' => 'Salad Quinoa & Ayam Panggang',
        'description' => 'Penuh protein dan serat untuk energi tahan lama.',
        'calories' => 400,
        'protein' => 30,
        'carbs' => 35,
        'fats' => 15,
        'color_class' => 'border-green-500',
        'button_color' => 'bg-green-500'
    ],
    [
        'meal' => 'Makan Malam',
        'name' => 'Sup Ikan Sayuran',
        'description' => 'Makanan ringan dan kaya nutrisi untuk malam hari.',
        'calories' => 300,
        'protein' => 25,
        'carbs' => 20,
        'fats' => 12,
        'color_class' => 'border-purple-500',
        'button_color' => 'bg-purple-500'
    ],
    [
        'meal' => 'Camilan',
        'name' => 'Yogurt & Kacang Almond',
        'description' => 'Camilan sehat yang mengenyangkan dan kaya protein.',
        'calories' => 150,
        'protein' => 10,
        'carbs' => 15,
        'fats' => 8,
        'color_class' => 'border-yellow-500',
        'button_color' => 'bg-yellow-500'
    ],
];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Nutrisi Harian</title>
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

        .modal.show {
            display: flex;
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
                        <a href="./finance.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Keuangan
                        </a>
                    </li>
                    <li>
                        <a href="./nutrition.php" class="flex items-center p-3 rounded-lg text-indigo-700 bg-indigo-100 font-semibold hover:bg-indigo-200 transition-colors duration-200">
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
                    <h2 class="text-4xl font-bold text-gray-900 ">Nutrisi Harian</h2>
                </div>
                <button id="open-food-modal-btn" class="bg-indigo-600 text-white py-2 px-5 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Catat Makanan
                </button>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Kalori Tercapai</p>
                        <p class="text-3xl font-bold text-green-600"><?= $dailyNutrientTotals['total_calories'] ?? 0 ?> <span class="text-xl font-normal">kcal</span></p>
                    </div>
                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11h2.5V14H7m-2.5 0H7V11h-2.5M7 11v2.5H9.5V11M9.5 11v2.5h2.5V11"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Protein</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $dailyNutrientTotals['total_protein'] ?? 0 ?> <span class="text-xl font-normal">g</span></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Karbohidrat</p>
                        <p class="text-3xl font-bold text-yellow-600"><?= $dailyNutrientTotals['total_carbohydrates'] ?? 0 ?> <span class="text-xl font-normal">g</span></p>
                    </div>
                    <svg class="w-12 h-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Lemak</p>
                        <p class="text-3xl font-bold text-red-600"><?= $dailyNutrientTotals['total_fats'] ?? 0 ?> <span class="text-xl font-normal">g</span></p>
                    </div>
                    <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </section>

            <section class="mb-10">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Rekomendasi Menu Harian</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($recommendedMenus as $menu): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 <?= $menu['color_class'] ?> hover:shadow-lg transition-shadow duration-200">
                            <h4 class="text-xl font-semibold text-gray-900 mb-2"><?= htmlspecialchars($menu['meal']) ?>: <?= htmlspecialchars($menu['name']) ?></h4>
                            <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars($menu['description']) ?></p>
                            <div class="flex items-center text-sm text-gray-500 mb-4">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1L21 12h-3m-6 0h-1.01M12 16c-1.11 0-2.08-.402-2.592-1L3 12h3m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <?= $menu['calories'] ?> kcal | Protein: <?= $menu['protein'] ?>g | Karbo: <?= $menu['carbs'] ?>g | Lemak: <?= $menu['fats'] ?>g
                            </div>
                            <button class="<?= $menu['button_color'] ?> text-white py-2 px-4 rounded-lg text-sm hover:opacity-90 transition-opacity duration-200">Lihat Resep</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="mb-10">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Asupan Hari Ini (<?= htmlspecialchars(date('d F Y', strtotime($currentDate))) ?>)</h3>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <?php if (empty($dailyFoodEntries)): ?>
                        <p class="text-gray-500 text-center py-8">Belum ada makanan dicatat hari ini.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Makanan</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kalori (kcal)</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Protein (g)</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Karbo (g)</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lemak (g)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dailyFoodEntries as $entry): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($entry['food_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($entry['calories']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($entry['protein']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($entry['carbohydrates']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($entry['fats']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="foodModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Catat Makanan Baru</h3>
            <form id="foodForm" method="POST" action="../actions/add_food_entry.php">
                <div class="mb-4">
                    <label for="food_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Makanan:</label>
                    <input type="text" id="food_name" name="food_name" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="calories" class="block text-gray-700 text-sm font-bold mb-2">Kalori (kcal):</label>
                    <input type="number" id="calories" name="calories" required min="0" step="1" value="0"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="protein" class="block text-gray-700 text-sm font-bold mb-2">Protein (g):</label>
                        <input type="number" id="protein" name="protein" required min="0" step="0.1" value="0"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label for="carbs" class="block text-gray-700 text-sm font-bold mb-2">Karbohidrat (g):</label>
                        <input type="number" id="carbs" name="carbohydrates" required min="0" step="0.1" value="0"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div>
                        <label for="fats" class="block text-gray-700 text-sm font-bold mb-2">Lemak (g):</label>
                        <input type="number" id="fats" name="fats" required min="0" step="0.1" value="0"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
                <div class="mb-6">
                    <label for="entry_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal:</label>
                    <input type="date" id="entry_date" name="entry_date" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Makanan
                    </button>
                    <button type="button" id="cancelFoodBtn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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
            // Reusable Notification Function (sama seperti di habits.php dan finance.php)
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

            // --- Modal Logic for Food Entry ---
            const foodModal = document.getElementById('foodModal');
            const openFoodModalBtn = document.getElementById('open-food-modal-btn');
            const closeBtn = foodModal.querySelector('.close-btn');
            const cancelFoodBtn = document.getElementById('cancelFoodBtn');
            const foodForm = document.getElementById('foodForm');
            const entryDateInput = document.getElementById('entry_date');

            function openFoodModal() {
                foodModal.classList.add('show'); // Use class to show/hide
                foodForm.reset();
                entryDateInput.value = new Date().toISOString().split('T')[0]; // Set default date to today
            }

            function closeFoodModal() {
                foodModal.classList.remove('show');
            }

            openFoodModalBtn.addEventListener('click', openFoodModal);
            closeBtn.addEventListener('click', closeFoodModal);
            cancelFoodBtn.addEventListener('click', closeFoodModal);

            // Close modal when clicking outside of it
            window.addEventListener('click', (event) => {
                if (event.target === foodModal) {
                    closeFoodModal();
                }
            });

            // --- Form Submission (AJAX) for Food Entry ---
            foodForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);
                const actionUrl = this.action; // Get action URL from the form itself

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showNotification(data.message, 'success');
                        closeFoodModal();
                        setTimeout(() => location.reload(), 1000); // Reload page to show new data
                    } else {
                        showNotification(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error submitting food form:', error);
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