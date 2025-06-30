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
    <title>Ketentuan Layanan - HabitForge</title>
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
        <h1 class="text-4xl font-bold text-center text-gray-900 mb-8">Ketentuan Layanan</h1>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">1. Penerimaan Ketentuan</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Dengan mengakses dan menggunakan aplikasi atau layanan HabitForge ("Layanan"), Anda setuju untuk terikat oleh Ketentuan Layanan ini ("Ketentuan"). Jika Anda tidak setuju dengan semua ketentuan ini, Anda tidak diizinkan untuk menggunakan Layanan.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Ketentuan ini berlaku untuk semua pengunjung, pengguna, dan pihak lain yang mengakses atau menggunakan Layanan.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">2. Perubahan Ketentuan</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Kami berhak untuk, atas kebijakan tunggal kami, memodifikasi atau mengganti Ketentuan ini kapan saja. Jika revisi adalah materi, kami akan mencoba memberikan pemberitahuan setidaknya 30 hari sebelum ketentuan baru berlaku. Apa yang merupakan perubahan materi akan ditentukan atas kebijakan tunggal kami.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Dengan terus mengakses atau menggunakan Layanan kami setelah revisi tersebut berlaku, Anda setuju untuk terikat oleh ketentuan yang direvisi. Jika Anda tidak setuju dengan ketentuan baru, harap berhenti menggunakan Layanan.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">3. Akun Pengguna</h2>
            <p class="text-gray-700 leading-relaxed mb-2">
                Saat Anda membuat akun dengan kami, Anda harus memberikan informasi yang akurat, lengkap, dan terkini setiap saat. Kegagalan untuk melakukannya merupakan pelanggaran Ketentuan, yang dapat mengakibatkan penghentian segera akun Anda di Layanan kami.
            </p>
            <ul class="list-disc list-inside text-gray-700 leading-relaxed ml-4">
                <li class="mb-2">Anda bertanggung jawab untuk menjaga kerahasiaan kata sandi Anda dan untuk semua aktivitas atau tindakan di bawah kata sandi Anda.</li>
                <li class="mb-2">Anda harus segera memberitahu kami jika ada pelanggaran keamanan atau penggunaan akun Anda yang tidak sah.</li>
                <li class="mb-2">Anda tidak boleh menggunakan sebagai username nama orang atau entitas lain atau yang tidak tersedia secara hukum untuk digunakan, nama atau merek dagang yang tunduk pada hak-hak orang atau entitas lain selain Anda tanpa otorisasi yang sesuai, atau nama yang menyinggung, vulgar, atau cabul.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">4. Kebiasaan dan Konten Pengguna</h2>
            <p class="text-gray-700 leading-relaxed mb-2">
                Layanan kami memungkinkan Anda untuk memasukkan, menyimpan, dan melacak kebiasaan. Anda bertanggung jawab penuh atas kebiasaan dan data terkait yang Anda masukkan.
            </p>
            <ul class="list-disc list-inside text-gray-700 leading-relaxed ml-4">
                <li class="mb-2">Anda harus memastikan bahwa kebiasaan atau konten apa pun yang Anda masukkan tidak melanggar hukum yang berlaku.</li>
                <li class="mb-2">Kami tidak bertanggung jawab atau berkewajiban atas konten pengguna yang diposting di atau melalui Layanan.</li>
                <li class="mb-2">Kami berhak (tetapi tidak memiliki kewajiban) untuk, atas kebijakan tunggal kami, menghapus atau menolak konten apa pun yang kami anggap tidak pantas.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">5. Penghentian</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Kami dapat menghentikan atau menangguhkan akun Anda dan segera memblokir akses ke Layanan kami, tanpa pemberitahuan sebelumnya atau kewajiban, atas kebijakan tunggal kami, untuk alasan apa pun, termasuk tanpa batasan jika Anda melanggar Ketentuan.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Jika Anda ingin menghentikan akun Anda, Anda dapat melakukannya dengan menghentikan penggunaan Layanan.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">6. Batasan Tanggung Jawab</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Dalam keadaan apa pun, HabitForge, direktur, karyawan, mitra, agen, pemasok, atau afiliasinya tidak akan bertanggung jawab atas kerugian tidak langsung, insidental, khusus, konsekuensial, atau hukuman, termasuk tanpa batasan, kehilangan keuntungan, data, penggunaan, niat baik, atau kerugian tidak berwujud lainnya, yang dihasilkan dari (i) akses Anda atau penggunaan atau ketidakmampuan untuk mengakses atau menggunakan Layanan; (ii) perilaku atau konten pihak ketiga apa pun di Layanan; (iii) konten yang diperoleh dari Layanan; dan (iv) akses tidak sah, penggunaan, atau perubahan transmisi atau konten Anda, baik berdasarkan jaminan, kontrak, tort (termasuk kelalaian), atau teori hukum lainnya, baik kami telah diberitahu tentang kemungkinan kerugian tersebut atau tidak, dan bahkan jika obat yang ditetapkan di sini ditemukan gagal dari tujuan pentingnya.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">7. Penafian</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Penggunaan Layanan adalah risiko Anda sendiri. Layanan disediakan atas dasar "SEBAGAIMANA ADANYA" dan "SEBAGAIMANA TERSEDIA". Layanan disediakan tanpa jaminan dalam bentuk apa pun, baik tersurat maupun tersirat, termasuk, namun tidak terbatas pada, jaminan tersirat atas kelayakan jual, kesesuaian untuk tujuan tertentu, non-pelanggaran, atau kinerja.
            </p>
            <p class="text-gray-700 leading-relaxed">
                HabitForge dan anak perusahaan, afiliasi, dan pemberi lisensinya tidak menjamin bahwa a) Layanan akan berfungsi tanpa gangguan, aman, atau tersedia pada waktu atau lokasi tertentu; b) setiap kesalahan atau cacat akan diperbaiki; c) Layanan bebas dari virus atau komponen berbahaya lainnya; atau d) hasil penggunaan Layanan akan memenuhi persyaratan Anda.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">8. Hukum yang Mengatur</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Ketentuan ini akan diatur dan ditafsirkan sesuai dengan hukum [Negara Anda, misal: Indonesia], tanpa memperhatikan pertentangan ketentuan hukumnya.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Kegagalan kami untuk menegakkan hak atau ketentuan apa pun dari Ketentuan ini tidak akan dianggap sebagai pengabaian hak-hak tersebut. Jika ada ketentuan dalam Ketentuan ini yang dianggap tidak valid atau tidak dapat dilaksanakan oleh pengadilan, ketentuan lainnya dari Ketentuan ini akan tetap berlaku.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">9. Hubungi Kami</h2>
            <p class="text-gray-700 leading-relaxed mb-2">
                Jika Anda memiliki pertanyaan tentang Ketentuan ini, silakan hubungi kami:
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