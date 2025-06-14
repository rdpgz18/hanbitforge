<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php"; // Sertakan fungsi-fungsi latihan


$user_id = $_SESSION['user_id'];

// Ambil data untuk ringkasan di bagian atas
$weeklyCaloriesBurned = getWeeklyCaloriesBurned($pdo, $user_id);
$weeklyWorkoutCount = getWeeklyWorkoutCount($pdo, $user_id);

// Ambil riwayat latihan (misal, 5 latihan terbaru)
$recentWorkouts = getUserWorkouts($pdo, $user_id, 5); // Ambil 5 terakhir

// Ambil semua latihan untuk modal edit/daftar lengkap (jika ingin)
// $allWorkouts = getUserWorkouts($pdo, $user_id);

// Default rekomendasi
$nextRecommendation = "Siap"; // Anda bisa menambahkan logika yang lebih kompleks di sini

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Olahraga</title>

    <!-- Tailwindcss -->
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
            /* Tambahkan shadow agar lebih mirip */
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
                        <a href="./habits.php" class="flex items-center p-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-indigo-700 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Kebiasaan
                        </a>
                    </li>
                    <li>
                        <a href="./exercise.php" class="flex items-center p-3 rounded-lg text-indigo-700 bg-indigo-100 font-semibold hover:bg-indigo-200 transition-colors duration-200">
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
                    <h2 class="text-4xl font-bold text-gray-900 ">Latihan Fisik</h2>
                </div>
                <button id="open-add-workout-modal" class="bg-indigo-600 text-white py-2 px-5 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Catat Latihan Baru
                </button>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Kalori Terbakar Minggu Ini</p>
                        <p class="text-3xl font-bold text-green-600"><?= htmlspecialchars($weeklyCaloriesBurned) ?> <span class="text-xl font-normal">kcal</span></p>
                    </div>
                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Jumlah Latihan Minggu Ini</p>
                        <p class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($weeklyWorkoutCount) ?> <span class="text-xl font-normal">Sesi</span></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-enter card-enter-active">
                    <div>
                        <p class="text-gray-500 text-sm">Rekomendasi Berikutnya</p>
                        <p class="text-3xl font-bold text-purple-600"><?= htmlspecialchars($nextRecommendation) ?></p>
                    </div>
                    <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </section>
            <section class="mb-10">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Program Latihan Harianmu</h3>
                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
                    <h4 class="text-xl font-semibold text-purple-700 mb-3">Latihan Kekuatan: Full Body Workout</h4>
                    <p class="text-gray-600 mb-4">Fokus pada kelompok otot besar untuk peningkatan kekuatan dan massa otot.</p>
                    <ul class="list-disc list-inside space-y-2 mb-4 text-gray-700">
                        <li>Squats: 3 set x 8-12 repetisi</li>
                        <li>Push-ups (atau Bench Press): 3 set x max/8-12 repetisi</li>
                        <li>Rows (Dumbbell atau Barbell): 3 set x 8-12 repetisi</li>
                        <li>Plank: 3 set x 30-60 detik</li>
                        <li>Lunges: 3 set x 10 repetisi per kaki</li>
                    </ul>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-6 border-t pt-4">
                        <p class="text-sm text-gray-500 mb-2 sm:mb-0">Perkiraan Durasi: 45 menit - 1 jam</p>
                        <button class="bg-purple-600 text-white py-2 px-5 rounded-lg text-lg font-semibold hover:bg-purple-700 transition-colors duration-200 shadow-md">
                            Mulai Latihan Ini
                        </button>
                    </div>
                </div>
            </section>

            <section class="mb-10">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Riwayat Latihanmu</h3>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <ul class="divide-y divide-gray-200">
                        <?php if (empty($recentWorkouts)): ?>
                            <li class="py-4 text-center text-gray-500">Belum ada catatan latihan. Yuk, mulai Catat Latihan Baru!</li>
                        <?php else: ?>
                            <?php foreach ($recentWorkouts as $workout): ?>
                                <li class="py-4 flex justify-between items-center">
                                    <div>
                                        <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($workout['workout_name']) ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars(date('d F Y', strtotime($workout['workout_date']))) ?>,
                                            <?= htmlspecialchars($workout['duration_minutes']) ?> menit
                                            <?php if ($workout['calories_burned']): ?>
                                                (<?= htmlspecialchars($workout['calories_burned']) ?> kcal)
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($workout['description'])): ?>
                                            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($workout['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button class="edit-workout-btn text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                            data-workout-id="<?= htmlspecialchars($workout['workout_id']) ?>"
                                            data-workout-name="<?= htmlspecialchars($workout['workout_name']) ?>"
                                            data-description="<?= htmlspecialchars($workout['description']) ?>"
                                            data-duration-minutes="<?= htmlspecialchars($workout['duration_minutes']) ?>"
                                            data-calories-burned="<?= htmlspecialchars($workout['calories_burned']) ?>"
                                            data-workout-date="<?= htmlspecialchars($workout['workout_date']) ?>">
                                            Edit
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <button class="mt-6 w-full bg-gray-100 text-indigo-700 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-semibold">
                        Lihat Lebih Banyak Riwayat
                    </button>
                </div>
            </section>
        </main>
    </div>

    <div id="workoutModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="modalTitle" class="text-2xl font-bold text-gray-900 mb-6">Catat Latihan Baru</h3>
            <form id="workoutForm" method="POST" action="actions/save_workout.php">
                <input type="hidden" id="workoutId" name="workout_id">
                <div class="mb-4">
                    <label for="workoutName" class="block text-gray-700 text-sm font-bold mb-2">Nama Latihan:</label>
                    <input type="text" id="workoutName" name="workout_name" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi (Opsional):</label>
                    <textarea id="description" name="description" rows="3"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                <div class="mb-4">
                    <label for="durationMinutes" class="block text-gray-700 text-sm font-bold mb-2">Durasi (Menit):</label>
                    <input type="number" id="durationMinutes" name="duration_minutes" required min="1"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="caloriesBurned" class="block text-gray-700 text-sm font-bold mb-2">Kalori Terbakar (Opsional):</label>
                    <input type="number" id="caloriesBurned" name="calories_burned" min="0"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label for="workoutDate" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Latihan:</label>
                    <input type="date" id="workoutDate" name="workout_date" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Latihan
                    </button>
                    <button type="button" id="cancelWorkoutBtn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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

    <script src="../src/js//main.js"></script>
    <script src="../src/js/theme_handler.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reusable Notification Function (sama seperti di habits.php)
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

            // --- Modal Logic ---
            // ... (Kode modal dan form submission Anda di sini) ...

            const workoutModal = document.getElementById('workoutModal');
            const openAddWorkoutModalBtn = document.getElementById('open-add-workout-modal');
            const closeBtn = document.querySelector('.close-btn');
            const cancelWorkoutBtn = document.getElementById('cancelWorkoutBtn');
            const workoutForm = document.getElementById('workoutForm');
            const modalTitle = document.getElementById('modalTitle');

            // Form fields
            const workoutIdInput = document.getElementById('workoutId');
            const workoutNameInput = document.getElementById('workoutName');
            const descriptionInput = document.getElementById('description');
            const durationMinutesInput = document.getElementById('durationMinutes');
            const caloriesBurnedInput = document.getElementById('caloriesBurned');
            const workoutDateInput = document.getElementById('workoutDate');

            function openModal(isEdit = false, workoutData = {}) {
                workoutModal.style.display = 'flex'; // Use flex to center with CSS
                workoutForm.reset(); // Clear form

                if (isEdit) {
                    modalTitle.textContent = 'Edit Latihan';
                    workoutIdInput.value = workoutData.workout_id;
                    workoutNameInput.value = workoutData.workout_name;
                    descriptionInput.value = workoutData.description;
                    durationMinutesInput.value = workoutData.duration_minutes;
                    caloriesBurnedInput.value = workoutData.calories_burned;
                    workoutDateInput.value = workoutData.workout_date;
                } else {
                    modalTitle.textContent = 'Catat Latihan Baru';
                    workoutIdInput.value = ''; // Clear hidden ID for new entry
                    // Set default date to today for new entry
                    workoutDateInput.value = new Date().toISOString().split('T')[0];
                }
            }

            function closeModal() {
                workoutModal.style.display = 'none';
            }

            openAddWorkoutModalBtn.addEventListener('click', () => openModal(false));
            closeBtn.addEventListener('click', closeModal);
            cancelWorkoutBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside of it
            window.addEventListener('click', (event) => {
                if (event.target === workoutModal) {
                    closeModal();
                }
            });

            // Handle Edit button clicks
            document.querySelectorAll('.edit-workout-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const workoutData = this.dataset; // Get all data-attributes
                    openModal(true, workoutData);
                });
            });

            // --- Form Submission (AJAX) ---
            workoutForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);
                const workoutId = formData.get('workout_id');
                const actionUrl = workoutId ? '../actions/edit_workout.php' : '../actions/add_workout.php';

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showNotification(data.message, 'success'); // Gunakan fungsi notifikasi
                        closeModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error'); // Gunakan fungsi notifikasi
                    }
                } catch (error) {
                    console.error('Error submitting workout form:', error);
                    showNotification('Terjadi kesalahan jaringan atau server.', 'error');
                }
            });

            // Sidebar toggle (if you have one)
            const openMenuBtn = document.getElementById('open-menu-btn');
            const sidebar = document.getElementById('sidebar');
            if (openMenuBtn && sidebar) {
                openMenuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                });
            }
        });
    </script>
</body>

</html>