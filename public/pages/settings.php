<?php
// settings.php (atau file yang relevan)
// Pastikan auth/auth.php dan config.php sudah di-include di bagian atas file ini
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";


$user_id = $_SESSION['user_id'];
$userProfile = getUserProfile($pdo, $user_id);

// Jika profil tidak ditemukan (jarang terjadi jika user sudah login)
// Jika profil tidak ditemukan, tetapkan nilai default
if (!$userProfile) {
    $userProfile = [
        'full_name' => '',
        'email' => '',
        'avatar_url' => 'https://via.placeholder.com/150', // Default placeholder
        'bio' => ''
    ];
    error_log("User profile not found for ID: " . $user_id);
}

// --- HTML Dimulai ---

?>

<!DOCTYPE html>
<html lang="id" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Pengaturan</title>

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

        .form-field.disabled {
            background-color: #f0f0f0;
            /* Warna latar belakang untuk field yang disabled */
            cursor: not-allowed;
        }

        /* Notifikasi Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
            text-align: center;
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

        .modal-header {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .modal-body {
            margin-bottom: 25px;
            color: #555;
        }

        .modal-footer button {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }

        .modal-footer .success {
            background-color: #28a745;
            /* green-600 */
            color: white;
        }

        .modal-footer .error {
            background-color: #dc3545;
            /* red-600 */
            color: white;
        }

        .modal-footer .info {
            background-color: #007bff;
            /* blue-600 */
            color: white;
        }

        .modal-footer .close {
            background-color: #ccc;
            color: #333;
            margin-left: 10px;
        }

        /* Notifikasi Toast (opsional, sebagai alternatif modal) */
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        .notification.info {
            background-color: #007bff;
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
                        <a href="./settings.php" class="flex items-center p-3 rounded-lg text-indigo-700 bg-indigo-100 font-semibold hover:bg-indigo-200 transition-colors duration-200">
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
                    <h2 class="text-4xl font-bold text-gray-900 ">Pengaturan</h2>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <nav class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-min">
                    <ul class="space-y-2">
                        <li>
                            <a href="#profile-settings" class="flex items-center p-3 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Profil
                            </a>
                        </li>
                        <li>
                            <a href="#account-security" class="flex items-center p-3 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2v5a2 2 0 01-2 2h-2a2 2 0 01-2-2V9a2 2 0 012-2h2z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Akun & Keamanan
                            </a>
                        </li>
                        <li>
                            <a href="#notification-settings" class="flex items-center p-3 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                Notifikasi
                            </a>
                        </li>
                        <li>
                            <a href="#preferences" class="flex items-center p-3 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Preferensi Aplikasi
                            </a>
                        </li>
                        <li>
                            <a href="#data-privacy" class="flex items-center p-3 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-1.75-3M12 12v3M12 7v.01M12 19h.01M12 15h.01M12 17h.01M12 21h.01M7.5 4.25L7 4.25M16.5 4.25L17 4.25M18 14v-2.5M14 18h2.5M10 18h-2.5M6 14v-2.5M12 11a1 1 0 11-2 0 1 1 0 012 0zM12 6a1 1 0 11-2 0 1 1 0 012 0zM12 16a1 1 0 11-2 0 1 1 0 012 0z"></path>
                                </svg>
                                Data & Privasi
                            </a>
                        </li>
                        <li>
                            <a href="#about" class="flex items-center p-3 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Tentang
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="lg:col-span-2 space-y-8">

                    <section id="profile-settings" class="bg-white p-6 rounded-lg shadow-md card-enter card-enter-active">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Profil</h3>
                        <form id="profileForm" class="space-y-6">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userProfile['user_id'] ?? '') ?>">

                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($userProfile['full_name'] ?? '') ?>"
                                    class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 form-field" disabled>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($userProfile['email'] ?? '') ?>"
                                    class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 form-field" disabled>
                                <p class="mt-2 text-sm text-gray-500">Email tidak dapat diubah di sini. Hubungi dukungan jika perlu.</p>
                            </div>
                            <div>
                                <label for="avatar_upload" class="block text-sm font-medium text-gray-700">Foto Profil</label>
                                <div class="mt-2 flex items-center space-x-4">
                                    <img id="avatar-preview" class="h-16 w-16 rounded-full object-cover"
                                        src="../<?= htmlspecialchars($userProfile['avatar_url'] ?? 'https://via.placeholder.com/150') ?>" alt="Foto Profil">
                                    <input type="file" id="avatar_upload" name="avatar_upload" accept="image/*" class="hidden form-field">
                                    <button type="button" id="change-avatar-btn" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" disabled>
                                        Ubah Foto
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700">Bio Singkat</label>
                                <textarea id="bio" name="bio" rows="3"
                                    class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 form-field" disabled><?= htmlspecialchars($userProfile['bio'] ?? '') ?></textarea>
                            </div>
                            <div class="flex justify-end space-x-4">
                                <button type="button" id="editProfileBtn" class="bg-blue-600 text-white py-3 px-6 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors duration-200 shadow-md">
                                    Edit Profil
                                </button>
                                <button type="submit" id="saveProfileBtn" class="bg-indigo-600 text-white py-3 px-6 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md hidden">
                                    Simpan Perubahan
                                </button>
                                <button type="button" id="cancelEditBtn" class="bg-gray-500 text-white py-3 px-6 rounded-lg text-lg font-semibold hover:bg-gray-600 transition-colors duration-200 shadow-md hidden">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </section>

                    <section id="account-security" class="bg-white p-6 rounded-lg shadow-md card-enter card-enter-active">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Akun & Keamanan</h3>
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800">Ubah Kata Sandi</h4>
                                <form id="passwordForm" class="space-y-4 mt-3">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700">Kata Sandi Saat Ini</label>
                                        <input type="password" id="current_password" name="current_password"
                                            class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 password-field" disabled>
                                    </div>
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                                        <input type="password" id="new_password" name="new_password"
                                            class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 password-field" disabled>
                                    </div>
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi Baru</label>
                                        <input type="password" id="confirm_password" name="confirm_password"
                                            class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 password-field" disabled>
                                    </div>
                                    <div class="flex justify-end space-x-4">
                                        <button type="button" id="editPasswordBtn" class="bg-blue-600 text-white py-2 px-5 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200 shadow-md">
                                            Edit Kata Sandi
                                        </button>
                                        <button type="submit" id="savePasswordBtn" class="bg-indigo-600 text-white py-2 px-5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md hidden">
                                            Simpan Kata Sandi
                                        </button>
                                        <button type="button" id="cancelPasswordEditBtn" class="bg-gray-500 text-white py-2 px-5 rounded-lg font-semibold hover:bg-gray-600 transition-colors duration-200 shadow-md hidden">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="border-t pt-6">
                                <h4 class="text-lg font-semibold text-gray-800">Verifikasi Dua Langkah</h4>
                                <div class="mt-3 flex items-center justify-between">
                                    <p class="text-gray-600">Tambahkan lapisan keamanan ekstra pada akun Anda.</p>
                                    <label class="inline-flex relative items-center cursor-pointer">
                                        <input type="checkbox" value="" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="border-t pt-6">
                                <h4 class="text-lg font-semibold text-red-800">Hapus Akun</h4>
                                <p class="text-gray-600 mt-2">Menghapus akun Anda bersifat permanen dan tidak dapat dibatalkan.</p>
                                <button class="mt-4 bg-red-100 text-red-700 py-2 px-4 rounded-lg hover:bg-red-200 transition-colors duration-200 font-semibold">
                                    Hapus Akun Saya
                                </button>
                            </div>
                        </div>
                    </section>

                    <section id="notification-settings" class="bg-white p-6 rounded-lg shadow-md card-enter card-enter-active">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Notifikasi</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label for="notification-habit" class="text-gray-700 text-lg font-medium">Pengingat Kebiasaan</label>
                                <label class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="notification-habit" value="" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <label for="notification-workout" class="text-gray-700 text-lg font-medium">Pengingat Olahraga</label>
                                <label class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="notification-workout" value="" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <label for="notification-nutrition" class="text-gray-700 text-lg font-medium">Pengingat Nutrisi</label>
                                <label class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="notification-nutrition" value="" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <label for="notification-finance" class="text-gray-700 text-lg font-medium">Ringkasan Keuangan Harian</label>
                                <label class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox" id="notification-finance" value="" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex justify-end pt-4">
                                <button class="bg-indigo-600 text-white py-2 px-5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors duration-200 shadow-md">
                                    Simpan Pengaturan Notifikasi
                                </button>
                            </div>
                        </div>
                    </section>


                    <section id="preferences" class="bg-white p-6 rounded-lg shadow-md card-enter card-enter-active mt-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Preferensi Aplikasi</h3>
                        <div class="space-y-6">
                            <div>
                                <label for="theme" class="block text-sm font-medium text-gray-700">Tema Aplikasi</label>
                                <select id="theme" name="app_theme"
                                    class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="light">Terang (Default)</option>
                                    <option value="dark">Gelap</option>
                                    <option value="system">Sistem</option>
                                </select>
                            </div>
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700">Bahasa</label>
                                <div id="google_translate_element" class="mt-1"></div>
                                <p class="mt-2 text-sm text-gray-500">Pilih bahasa untuk terjemahan otomatis halaman.</p>
                            </div>
                        </div>
                    </section>

                    <section id="data-privacy" class="bg-white p-6 rounded-lg shadow-md card-enter card-enter-active">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Data & Privasi</h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800">Ekspor Data Anda</h4>
                                <p class="text-gray-600 mt-2">Anda bisa mengunduh salinan semua data Anda yang tersimpan di HabitForge.</p>
                                <button class="mt-4 bg-gray-100 text-indigo-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-semibold">
                                    Mulai Ekspor Data
                                </button>
                            </div>
                            <div class="border-t pt-6">
                                <h4 class="text-lg font-semibold text-gray-800">Kebijakan Privasi</h4>
                                <p class="text-gray-600 mt-2">Baca bagaimana kami mengelola data Anda.</p>
                                <a href="./privacy_policy.php" class="inline-block mt-4 text-indigo-600 hover:text-indigo-800 font-semibold transition-colors duration-200">
                                    Lihat Kebijakan Privasi
                                </a>
                            </div>
                            <div class="border-t pt-6">
                                <h4 class="text-lg font-semibold text-gray-800">Persyaratan Layanan</h4>
                                <p class="text-gray-600 mt-2">Pahami syarat dan ketentuan penggunaan aplikasi kami.</p>
                                <a href="./terms_of_service.php" class="inline-block mt-4 text-indigo-600 hover:text-indigo-800 font-semibold transition-colors duration-200">
                                    Lihat Persyaratan Layanan
                                </a>
                            </div>
                        </div>
                    </section>

                    <section id="about" class="bg-white p-6 rounded-lg shadow-md card-enter card-enter-active">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Tentang HabitForge</h3>
                        <div class="space-y-4">
                            <p class="text-gray-600">HabitForge adalah aplikasi yang dirancang untuk membantu Anda membangun kebiasaan baik, melacak kemajuan olahraga, memantau nutrisi, dan mengelola keuangan Anda secara efektif.</p>
                            <p class="text-gray-600">Versi Aplikasi: <span class="font-semibold">1.0.0</span></p>
                            <p class="text-gray-600">Hak Cipta © 2025 HabitForge. Semua hak dilindungi undang-undang.</p>
                            <a href="../dashboard.php" class="inline-block mt-4 text-indigo-600 hover:text-indigo-800 font-semibold transition-colors duration-200">
                                Kunjungi Situs Web Kami
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>


    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <h3 id="modalHeader" class="modal-header"></h3>
            <p id="modalBody" class="modal-body"></p>
            <div class="modal-footer">
                <button id="modalCloseBtn" class="close">Tutup</button>
            </div>
        </div>
    </div>

    <div id="notification-container"></div>


    <script src="../src/js/main.js"></script>
    <script src="../src/js/theme_handler.js"></script>
    <script>
        // Smooth scrolling for anchor links in settings
        document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });

                // Optional: Highlight active link
                document.querySelectorAll('nav a').forEach(link => {
                    link.classList.remove('bg-gray-100', 'text-indigo-700', 'font-semibold');
                    link.classList.add('text-gray-700');
                });
                this.classList.add('bg-gray-100', 'text-indigo-700', 'font-semibold');
                this.classList.remove('text-gray-700');
            });
        });

        // Set initial active link if there's a hash in the URL
        if (window.location.hash) {
            const initialSection = document.querySelector(`nav a[href="${window.location.hash}"]`);
            if (initialSection) {
                initialSection.click(); // Simulate click to scroll and highlight
            }
        } else {
            // Highlight the first item by default if no hash
            const firstLink = document.querySelector('nav ul li a');
            if (firstLink) {
                firstLink.classList.add('bg-gray-100', 'text-indigo-700', 'font-semibold');
                firstLink.classList.remove('text-gray-700');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // --- Notification Toast Function (reusable) ---
            function showToastNotification(message, type = 'info', duration = 3000) {
                const container = document.getElementById('notification-container');
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;

                container.appendChild(notification);

                void notification.offsetWidth; // Trigger reflow for animation
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

            // --- Notification Modal Function ---
            const notificationModal = document.getElementById('notificationModal');
            const modalHeader = document.getElementById('modalHeader');
            const modalBody = document.getElementById('modalBody');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

            function showModalNotification(header, body, type = 'info') {
                modalHeader.textContent = header;
                modalBody.textContent = body;
                modalCloseBtn.className = `close ${type}`; // Add type class to close button for styling
                notificationModal.classList.add('show');
            }

            modalCloseBtn.addEventListener('click', () => {
                notificationModal.classList.remove('show');
            });

            // Close modal when clicking outside of it
            window.addEventListener('click', (event) => {
                if (event.target === notificationModal) {
                    notificationModal.classList.remove('show');
                }
            });


            // --- Profile Form Logic ---
            const profileForm = document.getElementById('profileForm');
            const formFields = profileForm.querySelectorAll('.form-field');
            const editProfileBtn = document.getElementById('editProfileBtn');
            const saveProfileBtn = document.getElementById('saveProfileBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const avatarUploadInput = document.getElementById('avatar_upload');
            const changeAvatarBtn = document.getElementById('change-avatar-btn');
            const avatarPreview = document.getElementById('avatar-preview');

            // Store initial values to revert on cancel
            let initialFormValues = {};
            let initialAvatarUrl = avatarPreview.src;

            // Function to enable/disable form fields
            function setFormEnabled(enabled) {
                formFields.forEach(field => {
                    // Email field should always remain disabled for direct editing
                    if (field.id === 'email') {
                        field.disabled = true;
                        field.classList.add('form-field-disabled'); // Optional: for specific styling
                    } else {
                        field.disabled = !enabled;
                        if (enabled) {
                            field.classList.remove('form-field-disabled');
                            field.classList.remove('disabled'); // Remove tailwind-specific styling if applied
                        } else {
                            field.classList.add('form-field-disabled');
                            field.classList.add('disabled');
                        }
                    }
                });
                changeAvatarBtn.disabled = !enabled; // Enable/disable "Ubah Foto" button

                if (enabled) {
                    editProfileBtn.classList.add('hidden');
                    saveProfileBtn.classList.remove('hidden');
                    cancelEditBtn.classList.remove('hidden');
                } else {
                    editProfileBtn.classList.remove('hidden');
                    saveProfileBtn.classList.add('hidden');
                    cancelEditBtn.classList.add('hidden');
                }
            }

            // Save initial values when page loads
            function captureInitialValues() {
                formFields.forEach(field => {
                    initialFormValues[field.id] = field.value;
                });
                initialAvatarUrl = avatarPreview.src;
            }

            // Set form to initial disabled state on load
            setFormEnabled(false);
            captureInitialValues(); // Capture initial state once form is rendered with PHP data

            // --- Event Listeners ---
            editProfileBtn.addEventListener('click', () => {
                setFormEnabled(true);
            });

            cancelEditBtn.addEventListener('click', () => {
                // Revert form fields to initial values
                formFields.forEach(field => {
                    if (initialFormValues.hasOwnProperty(field.id)) {
                        field.value = initialFormValues[field.id];
                    }
                });
                avatarPreview.src = initialAvatarUrl; // Revert avatar preview
                avatarUploadInput.value = ''; // Clear file input
                setFormEnabled(false); // Disable form again
            });

            changeAvatarBtn.addEventListener('click', () => {
                avatarUploadInput.click(); // Trigger file input click
            });

            avatarUploadInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        avatarPreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            profileForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Prevent default form submission

                const formData = new FormData();
                formData.append('user_id', this.querySelector('input[name="user_id"]').value);
                formData.append('full_name', document.getElementById('full_name').value);
                formData.append('bio', document.getElementById('bio').value);

                const avatarFile = avatarUploadInput.files[0];
                if (avatarFile) {
                    formData.append('avatar_upload', avatarFile);
                }

                try {
                    const response = await fetch('../actions/update_profile.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showModalNotification('Berhasil!', data.message, 'success');
                        showToastNotification(data.message, 'success'); // Optional: show toast too

                        // Update initial values after successful save
                        captureInitialValues();
                        setFormEnabled(false); // Disable form after saving
                    } else {
                        showModalNotification('Gagal!', data.message, 'error');
                        showToastNotification(data.message, 'error'); // Optional: show toast too
                    }
                } catch (error) {
                    console.error('Error submitting profile form:', error);
                    showModalNotification('Kesalahan!', 'Terjadi kesalahan jaringan atau server.', 'error');
                    showToastNotification('Terjadi kesalahan jaringan atau server.', 'error'); // Optional: show toast too
                }
            });

            // --- Password Form Logic ---
            const passwordForm = document.getElementById('passwordForm');
            const passwordFields = passwordForm.querySelectorAll('.password-field');
            const editPasswordBtn = document.getElementById('editPasswordBtn');
            const savePasswordBtn = document.getElementById('savePasswordBtn');
            const cancelPasswordEditBtn = document.getElementById('cancelPasswordEditBtn');

            function setPasswordFormEnabled(enabled) {
                passwordFields.forEach(field => {
                    field.disabled = !enabled;
                    if (enabled) {
                        field.classList.remove('disabled');
                    } else {
                        field.classList.add('disabled');
                        field.value = ''; // Clear fields when disabled
                    }
                });

                if (enabled) {
                    editPasswordBtn.classList.add('hidden');
                    savePasswordBtn.classList.remove('hidden');
                    cancelPasswordEditBtn.classList.remove('hidden');
                } else {
                    editPasswordBtn.classList.remove('hidden');
                    savePasswordBtn.classList.add('hidden');
                    cancelPasswordEditBtn.classList.add('hidden');
                }
            }

            // Set password form to initial disabled state on load
            setPasswordFormEnabled(false);

            // --- Event Listeners for Password Form ---
            editPasswordBtn.addEventListener('click', () => {
                setPasswordFormEnabled(true);
            });

            cancelPasswordEditBtn.addEventListener('click', () => {
                setPasswordFormEnabled(false);
            });

            passwordForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                // Basic client-side validation
                if (currentPassword === '' || newPassword === '' || confirmPassword === '') {
                    // PERUBAHAN DI SINI: Gunakan showModalNotification
                    showModalNotification('Error!', 'Semua kolom kata sandi harus diisi.', 'error');
                    showToastNotification('Semua kolom kata sandi harus diisi.', 'error');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    // PERUBAHAN DI SINI: Gunakan showModalNotification
                    showModalNotification('Error!', 'Kata sandi baru dan konfirmasi tidak cocok.', 'error');
                    showToastNotification('Kata sandi baru dan konfirmasi tidak cocok.', 'error');
                    return;
                }

                if (newPassword.length < 8) {
                    // PERUBAHAN DI SINI: Gunakan showModalNotification
                    showModalNotification('Error!', 'Kata sandi baru minimal 8 karakter.', 'error');
                    showToastNotification('Kata sandi baru minimal 8 karakter.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);

                try {
                    const response = await fetch('../actions/update_password.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        // PERUBAHAN DI SINI: Gunakan showModalNotification untuk sukses
                        showModalNotification('Berhasil!', data.message, 'success');
                        showToastNotification(data.message, 'success');
                        setPasswordFormEnabled(false); // Disable form after success
                    } else {
                        // PERUBAHAN DI SINI: Gunakan showModalNotification untuk gagal
                        showModalNotification('Gagal!', data.message, 'error');
                        showToastNotification(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error submitting password form:', error);
                    // PERUBAHAN DI SINI: Gunakan showModalNotification untuk error jaringan
                    showModalNotification('Kesalahan!', 'Terjadi kesalahan jaringan atau server.', 'error');
                    showToastNotification('Terjadi kesalahan jaringan atau server.', 'error');
                }
            });


        });
    </script>
</body>

</html>