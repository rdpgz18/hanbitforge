<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil parameter dari URL
$habit_id = $_GET['habit_id'] ?? null;
$habit_name = $_GET['name'] ?? 'Kebiasaan';
$duration_seconds = $_GET['duration'] ?? 0; // Durasi dalam detik

// Validasi dasar
if (!$habit_id || !is_numeric($habit_id) || $duration_seconds <= 0) {
    // Redirect kembali atau tampilkan pesan error
    header("Location: habits.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Timer Kebiasaan</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="src/css/tailwind.css" rel="stylesheet">
    <link href="src/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        .timer-container {
            background-color: white;
            padding: 3rem; /* 48px */
            border-radius: 1rem; /* 16px */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 500px;
        }
        #timer-display {
            font-size: 5rem; /* 80px */
            font-weight: 700; /* bold */
            color: #312e81; /* indigo-800 */
            margin-bottom: 2rem; /* 32px */
        }
        .timer-buttons button {
            padding: 0.75rem 1.5rem; /* 12px 24px */
            font-size: 1rem; /* 16px */
            font-weight: 600; /* semibold */
            border-radius: 0.5rem; /* 8px */
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .timer-buttons .start-btn {
            background-color: #4f46e5; /* indigo-600 */
            color: white;
        }
        .timer-buttons .start-btn:hover {
            background-color: #4338ca; /* indigo-700 */
        }
        .timer-buttons .pause-btn {
            background-color: #f59e0b; /* amber-500 */
            color: white;
        }
        .timer-buttons .pause-btn:hover {
            background-color: #d97706; /* amber-600 */
        }
        .timer-buttons .reset-btn {
            background-color: #ef4444; /* red-500 */
            color: white;
        }
        .timer-buttons .reset-btn:hover {
            background-color: #dc2626; /* red-600 */
        }
        .timer-buttons .back-btn {
            background-color: #6b7280; /* gray-500 */
            color: white;
        }
        .timer-buttons .back-btn:hover {
            background-color: #4b5563; /* gray-600 */
        }
    </style>
</head>
<body>
    <div class="timer-container">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Timer untuk: <br><span class="text-indigo-600"><?= htmlspecialchars($habit_name) ?></span></h2>
        <div id="timer-display">00:00</div>
        <div class="timer-buttons flex justify-center space-x-4">
            <button id="start-pause-btn" class="start-btn">Mulai</button>
            <button id="reset-btn" class="reset-btn">Reset</button>
            <button id="back-btn" class="back-btn">Kembali</button>
        </div>
        <input type="hidden" id="habit-id-input" value="<?= htmlspecialchars($habit_id) ?>">
        <input type="hidden" id="initial-duration-input" value="<?= htmlspecialchars($duration_seconds) ?>">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timerDisplay = document.getElementById('timer-display');
            const startPauseBtn = document.getElementById('start-pause-btn');
            const resetBtn = document.getElementById('reset-btn');
            const backBtn = document.getElementById('back-btn');
            const habitId = document.getElementById('habit-id-input').value;
            const initialDuration = parseInt(document.getElementById('initial-duration-input').value);

            let timerInterval;
            let timeRemaining = initialDuration; // Waktu tersisa dalam detik
            let isPaused = true;
            let startTime = 0; // Waktu ketika timer dimulai (timestamp)

            // Fungsi untuk membaca cookie
            function getCookie(name) {
                const nameEQ = name + "=";
                const ca = document.cookie.split(';');
                for(let i=0; i < ca.length; i++) {
                    let c = ca[i];
                    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
                }
                return null;
            }

            // Fungsi untuk membuat/mengupdate cookie
            function setCookie(name, value, days) {
                let expires = "";
                if (days) {
                    const date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; SameSite=Lax";
            }

            // Fungsi untuk menghapus cookie
            function deleteCookie(name) {
                document.cookie = name + '=; Max-Age=-99999999; path=/; SameSite=Lax';
            }

            // Memformat waktu ke MM:SS
            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
            }

            // Memperbarui tampilan timer
            function updateDisplay() {
                timerDisplay.textContent = formatTime(timeRemaining);
            }

            // Logika timer
            function startTimer() {
                if (!isPaused) return; // Jangan mulai jika sudah berjalan

                isPaused = false;
                startPauseBtn.textContent = 'Jeda';
                startPauseBtn.classList.remove('start-btn');
                startPauseBtn.classList.add('pause-btn');

                // Jika melanjutkan dari jeda, gunakan sisa waktu
                // Jika mulai baru, waktu awal adalah sekarang
                if (startTime === 0) { // Hanya set startTime jika benar-benar baru dimulai (bukan dari jeda)
                     startTime = Date.now();
                } else { // Jika melanjutkan dari jeda, hitung ulang startTime berdasarkan waktuRemaining saat ini
                    startTime = Date.now() - (initialDuration - timeRemaining) * 1000;
                }

                setCookie(`timer_${habitId}_startTime`, startTime, 1);
                setCookie(`timer_${habitId}_timeRemaining`, timeRemaining, 1);
                setCookie(`timer_${habitId}_isPaused`, 'false', 1);

                timerInterval = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - startTime) / 1000);
                    timeRemaining = initialDuration - elapsed;

                    if (timeRemaining <= 0) {
                        clearInterval(timerInterval);
                        timeRemaining = 0;
                        updateDisplay();
                        isPaused = true;
                        startPauseBtn.textContent = 'Selesai!';
                        startPauseBtn.disabled = true; // Nonaktifkan tombol setelah selesai

                        // Opsional: Kirim sinyal ke server bahwa kebiasaan selesai
                        // fetch('actions/complete_habit.php', {
                        //     method: 'POST',
                        //     headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        //     body: `habit_id=${habitId}&action_type=complete`
                        // }).then(response => response.json())
                        //   .then(data => {
                        //       if (data.success) {
                        //           // Tampilkan notifikasi sukses
                        //           alert('Kebiasaan berhasil diselesaikan!');
                        //       } else {
                        //           // Tampilkan notifikasi gagal
                        //           alert('Gagal menyelesaikan kebiasaan: ' + data.message);
                        //       }
                        //   }).catch(error => console.error('Error completing habit:', error));

                        // Hapus semua cookie terkait timer ini
                        deleteCookie(`timer_${habitId}_startTime`);
                        deleteCookie(`timer_${habitId}_timeRemaining`);
                        deleteCookie(`timer_${habitId}_isPaused`);
                        deleteCookie(`timer_${habitId}_initialDuration`); // Hapus ini juga jika ada
                    }
                    updateDisplay();
                }, 1000); // Perbarui setiap detik
            }

            function pauseTimer() {
                if (isPaused) return;
                isPaused = true;
                clearInterval(timerInterval);
                startPauseBtn.textContent = 'Lanjutkan';
                startPauseBtn.classList.remove('pause-btn');
                startPauseBtn.classList.add('start-btn');
                // Simpan sisa waktu saat jeda
                setCookie(`timer_${habitId}_timeRemaining`, timeRemaining, 1);
                setCookie(`timer_${habitId}_isPaused`, 'true', 1);
            }

            function resetTimer() {
                clearInterval(timerInterval);
                timeRemaining = initialDuration;
                isPaused = true;
                startTime = 0; // Reset start time
                updateDisplay();
                startPauseBtn.textContent = 'Mulai';
                startPauseBtn.classList.remove('pause-btn');
                startPauseBtn.classList.add('start-btn');
                startPauseBtn.disabled = false; // Aktifkan lagi tombol

                // Hapus cookie
                deleteCookie(`timer_${habitId}_startTime`);
                deleteCookie(`timer_${habitId}_timeRemaining`);
                deleteCookie(`timer_${habitId}_isPaused`);
            }

            // Inisialisasi timer dari cookie saat halaman dimuat
            function initializeTimerFromCookie() {
                const savedStartTime = getCookie(`timer_${habitId}_startTime`);
                const savedTimeRemaining = getCookie(`timer_${habitId}_timeRemaining`);
                const savedIsPaused = getCookie(`timer_${habitId}_isPaused`);

                if (savedStartTime && savedTimeRemaining) {
                    startTime = parseInt(savedStartTime);
                    timeRemaining = parseInt(savedTimeRemaining);
                    isPaused = (savedIsPaused === 'true');

                    if (!isPaused) {
                        // Hitung waktu yang sudah berlalu sejak terakhir kali disimpan
                        const elapsedSinceLastSave = Math.floor((Date.now() - startTime) / 1000);
                        timeRemaining = initialDuration - elapsedSinceLastSave; // Re-calculate based on initial and elapsed
                        if (timeRemaining < 0) timeRemaining = 0; // Jangan sampai minus

                        updateDisplay(); // Update display immediately
                        startTimer(); // Lanjutkan timer jika tidak dijeda
                    } else {
                        // Jika dijeda, tampilkan sisa waktu dan atur tombol ke "Lanjutkan"
                        updateDisplay();
                        startPauseBtn.textContent = 'Lanjutkan';
                        startPauseBtn.classList.remove('start-btn');
                        startPauseBtn.classList.add('pause-btn'); // Ganti kelas agar terlihat seperti tombol jeda
                    }
                } else {
                    updateDisplay(); // Tampilkan waktu awal jika tidak ada cookie
                }

                 if (timeRemaining <= 0) { // Jika sudah selesai dari cookie
                    startPauseBtn.textContent = 'Selesai!';
                    startPauseBtn.disabled = true;
                    clearInterval(timerInterval); // Pastikan interval berhenti
                }
            }


            // Event Listeners
            startPauseBtn.addEventListener('click', () => {
                if (isPaused) {
                    startTimer();
                } else {
                    pauseTimer();
                }
            });

            resetBtn.addEventListener('click', resetTimer);
            backBtn.addEventListener('click', () => {
                window.location.href = 'habits.php'; // Kembali ke halaman kebiasaan
            });

            // Panggil inisialisasi saat DOM siap
            initializeTimerFromCookie();
        });
    </script>
</body>
</html>