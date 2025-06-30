<?php
// Pastikan auth/auth.php dan config.php sudah di-include di bagian atas file ini
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";


$user_id = $_SESSION['user_id'];
$userProfile = getUserProfile($pdo, $user_id);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi - HabitForge</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/theme.css">
    
    <script src="../src/js/tailwind.config.js"></script>
</head>

<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <header class="bg-indigo-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">HabitForge</h1>
        </div>
    </header>

    <main class="container mx-auto p-6 md:p-8 mt-8 bg-white rounded-lg shadow-xl mb-8">
        <h1 class="text-4xl font-bold text-center text-gray-900 mb-8">Kebijakan Privasi</h1>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">1. Pendahuluan</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Selamat datang di HabitForge. Kami berkomitmen untuk melindungi privasi pengguna kami. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, mengungkapkan, dan melindungi informasi Anda saat Anda menggunakan aplikasi dan layanan kami ("Layanan").
            </p>
            <p class="text-gray-700 leading-relaxed">
                Dengan menggunakan Layanan kami, Anda menyetujui praktik data yang dijelaskan dalam Kebijakan Privasi ini. Jika Anda tidak setuju dengan ketentuan Kebijakan Privasi ini, mohon jangan menggunakan Layanan kami.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">2. Informasi yang Kami Kumpulkan</h2>
            <p class="text-gray-700 leading-relaxed mb-2">Kami dapat mengumpulkan berbagai jenis informasi sehubungan dengan penggunaan Layanan Anda:</p>
            <ul class="list-disc list-inside text-gray-700 leading-relaxed ml-4">
                <li class="mb-2">
                    <strong>Informasi Pribadi:</strong> Informasi yang dapat mengidentifikasi Anda secara pribadi, seperti nama, alamat email, username, dan password (terenkripsi). Informasi ini Anda berikan saat mendaftar, mengelola profil, atau berinteraksi dengan layanan tertentu.
                </li>
                <li class="mb-2">
                    <strong>Informasi Penggunaan:</strong> Data tentang bagaimana Anda menggunakan Layanan, termasuk fitur yang Anda akses, waktu dan durasi penggunaan, kebiasaan yang Anda catat, dan interaksi Anda dengan aplikasi.
                </li>
                <li class="mb-2">
                    <strong>Data Teknis:</strong> Informasi tentang perangkat yang Anda gunakan untuk mengakses Layanan, seperti alamat IP, jenis perangkat, sistem operasi, jenis browser, dan data diagnostik lainnya.
                </li>
                <li class="mb-2">
                    <strong>Data Lokasi (Opsional):</strong> Jika Anda mengizinkan, kami dapat mengumpulkan data lokasi Anda untuk fitur tertentu (misalnya, pengingat berdasarkan lokasi).
                </li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">3. Bagaimana Kami Menggunakan Informasi Anda</h2>
            <p class="text-gray-700 leading-relaxed mb-2">Kami menggunakan informasi yang kami kumpulkan untuk berbagai tujuan, antara lain:</p>
            <ul class="list-disc list-inside text-gray-700 leading-relaxed ml-4">
                <li class="mb-2">Untuk menyediakan, mengoperasikan, dan memelihara Layanan kami.</li>
                <li class="mb-2">Untuk meningkatkan, mempersonalisasi, dan memperluas Layanan kami.</li>
                <li class="mb-2">Untuk memahami dan menganalisis bagaimana Anda menggunakan Layanan kami.</li>
                <li class="mb-2">Untuk mengembangkan produk, layanan, fitur, dan fungsionalitas baru.</li>
                <li class="mb-2">Untuk berkomunikasi dengan Anda, baik secara langsung atau melalui salah satu mitra kami, termasuk untuk layanan pelanggan, untuk memberi Anda pembaruan dan informasi lain yang berkaitan dengan Layanan, dan untuk tujuan pemasaran dan promosi.</li>
                <li class="mb-2">Untuk memproses transaksi Anda.</li>
                <li class="mb-2">Untuk mengirimkan email secara berkala mengenai pembaruan, promosi, atau informasi lain yang relevan (Anda dapat memilih untuk tidak menerima email ini).</li>
                <li class="mb-2">Untuk menemukan dan mencegah penipuan.</li>
                <li class="mb-2">Untuk mematuhi kewajiban hukum.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">4. Pembagian Informasi Anda</h2>
            <p class="text-gray-700 leading-relaxed mb-2">Kami tidak akan menjual informasi pribadi Anda kepada pihak ketiga. Kami dapat membagikan informasi Anda dalam situasi berikut:</p>
            <ul class="list-disc list-inside text-gray-700 leading-relaxed ml-4">
                <li class="mb-2">
                    <strong>Dengan Penyedia Layanan:</strong> Kami dapat membagikan informasi Anda dengan penyedia layanan pihak ketiga yang melakukan layanan atas nama kami (misalnya, hosting, analitik, pengiriman email). Penyedia ini diwajibkan untuk menjaga kerahasiaan informasi Anda.
                </li>
                <li class="mb-2">
                    <strong>Untuk Kepatuhan Hukum:</strong> Kami dapat mengungkapkan informasi Anda jika diwajibkan oleh hukum atau sebagai tanggapan atas permintaan hukum yang sah.
                </li>
                <li class="mb-2">
                    <strong>Transfer Bisnis:</strong> Dalam hal merger, akuisisi, atau penjualan aset, informasi Anda dapat ditransfer sebagai bagian dari transaksi tersebut.
                </li>
                <li class="mb-2">
                    <strong>Dengan Persetujuan Anda:</strong> Kami dapat mengungkapkan informasi Anda untuk tujuan lain dengan persetujuan Anda.
                </li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">5. Keamanan Data</h2>
            <p class="text-gray-700 leading-relaxed mb-2">
                Kami menerapkan langkah-langkah keamanan teknis dan organisasi yang wajar yang dirancang untuk melindungi keamanan informasi pribadi yang kami proses. Namun, perlu diingat bahwa tidak ada sistem keamanan yang 100% sempurna, dan kami tidak dapat menjamin keamanan mutlak informasi Anda.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">6. Hak-hak Anda</h2>
            <p class="text-gray-700 leading-relaxed mb-2">Anda memiliki hak-hak tertentu sehubungan dengan informasi pribadi Anda, termasuk hak untuk:</p>
            <ul class="list-disc list-inside text-gray-700 leading-relaxed ml-4">
                <li class="mb-2">Mengakses dan memperoleh salinan informasi pribadi Anda.</li>
                <li class="mb-2">Meminta koreksi atas informasi pribadi yang tidak akurat.</li>
                <li class="mb-2">Meminta penghapusan informasi pribadi Anda (tergantung pada batasan hukum).</li>
                <li class="mb-2">Menarik persetujuan Anda untuk pemrosesan data tertentu.</li>
            </ul>
            <p class="text-gray-700 leading-relaxed mt-4">
                Untuk menggunakan hak-hak ini, silakan hubungi kami melalui informasi kontak di bawah.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">7. Perubahan Kebijakan Privasi Ini</h2>
            <p class="text-gray-700 leading-relaxed mb-2">
                Kami dapat memperbarui Kebijakan Privasi kami dari waktu ke waktu. Kami akan memberitahu Anda tentang setiap perubahan dengan memposting Kebijakan Privasi baru di halaman ini dan memperbarui tanggal "Terakhir Diperbarui" di bagian atas. Anda disarankan untuk meninjau Kebijakan Privasi ini secara berkala untuk setiap perubahan.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Tanggal Efektif: <span class="font-semibold">15 Juni 2025</span>
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">8. Hubungi Kami</h2>
            <p class="text-gray-700 leading-relaxed mb-2">
                Jika Anda memiliki pertanyaan atau kekhawatiran tentang Kebijakan Privasi ini, silakan hubungi kami:
            </p>
            <p class="text-gray-700 leading-relaxed">
                Email: <a href="mailto:support@habitforge.com" class="text-indigo-600 hover:underline">support@habitforge.com</a>
            </p>
        </section>

        <div class="text-center mt-10">
            <a href="javascript:history.back()" class="text-indigo-600 hover:text-indigo-800 font-bold">&larr; Kembali</a>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 text-center mt-8 shadow-inner">
        <p>&copy; <?php echo date("Y"); ?> HabitForge. Hak Cipta Dilindungi.</p>
    </footer>

    <script src="../src/js/theme_handler.js"></script>

</body>

</html>