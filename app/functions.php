<?php

//--- Dashboard

function getDailyHabitSummary($pdo, $user_id)
{
    $today = date('Y-m-d');
    $summary = [
        'completed_today' => 0,
        'total_active_habits' => 0,
        'total_habits' => 0 // Ini bisa dihitung juga, tergantung kebutuhan
    ];

    try {
        // Hitung kebiasaan yang selesai hari ini
        $stmt_completed = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = :user_id AND status = 'completed' AND last_completed_date = :today");
        $stmt_completed->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_completed->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt_completed->execute();
        $summary['completed_today'] = $stmt_completed->fetchColumn();

        // Hitung total kebiasaan aktif (atau semua kebiasaan, tergantung definisi Anda untuk "total" di 3/5)
        // Kita bisa asumsikan "total" di sini adalah jumlah kebiasaan yang tidak dihapus dan aktif.
        // Jika Anda ingin semua kebiasaan, ganti 'status != "archived"' dengan kondisi lain atau hapus.
        $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = :user_id AND status != 'archived'");
        $stmt_total->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_total->execute();
        $summary['total_active_habits'] = $stmt_total->fetchColumn();

        // Jika Anda ingin total semua kebiasaan tanpa status
        $stmt_all_total = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = :user_id");
        $stmt_all_total->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_all_total->execute();
        $summary['total_habits'] = $stmt_all_total->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting daily habit summary: " . $e->getMessage());
        // Biarkan nilai default 0 jika ada error
    }

    return $summary;
}

function getTotalCaloriesBurnedToday($pdo, $userId, $date)
{
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(calories_burned) AS total_burned_calories
            FROM workouts
            WHERE user_id = :user_id AND DATE(workout_date) = :date
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':date' => $date
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total_burned_calories'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error fetching total calories burned: " . $e->getMessage());
        return 0;
    }
}

function getTotalIncomeForMonth($pdo, $userId, $yearMonth)
{
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) AS total_income
            FROM transactions
            WHERE user_id = :user_id
              AND type = 'income'
              AND DATE_FORMAT(transaction_date, '%Y-%m') = :year_month
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':year_month' => $yearMonth
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total_income'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error fetching total income for month: " . $e->getMessage());
        return 0.00;
    }
}

function getTotalExpensesForMonth($pdo, $userId, $yearMonth)
{
    try {
        $stmt = $pdo->prepare("
            SELECT SUM(amount) AS total_expenses
            FROM transactions
            WHERE user_id = :user_id
              AND type = 'expense'
              AND DATE_FORMAT(transaction_date, '%Y-%m') = :year_month
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':year_month' => $yearMonth
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total_expenses'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error fetching total expenses for month: " . $e->getMessage());
        return 0.00;
    }
}

/**
 * Menghitung sisa anggaran (pendapatan - pengeluaran) untuk user pada bulan tertentu.
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param string $yearMonth Bulan dan tahun dalam format 'YYYY-MM'.
 * @return float Sisa anggaran.
 */
function getRemainingBudgetForMonth($pdo, $userId, $yearMonth)
{
    $totalIncome = getTotalIncomeForMonth($pdo, $userId, $yearMonth);
    $totalExpenses = getTotalExpensesForMonth($pdo, $userId, $yearMonth);
    return $totalIncome - $totalExpenses;
}


/**
 * Mengambil daftar kebiasaan yang relevan untuk "hari ini" berdasarkan frekuensi.
 * Ini akan mengambil kebiasaan yang frequency-nya 'Setiap Hari' atau 'Setiap Malam'.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param int $limit Batas jumlah kebiasaan yang diambil.
 * @return array Array berisi data kebiasaan.
 */
function getDailyHabitsForUser($pdo, $userId, $limit = 3) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                habit_id,
                habit_name,          -- Diganti dari 'name' menjadi 'habit_name'
                description,
                frequency,
                current_streak,
                last_completed_date,
                status               -- Menambahkan kolom status
            FROM habits
            WHERE user_id = :user_id
              AND (frequency = 'Setiap Hari' OR frequency = 'Setiap Malam') -- Menyesuaikan dengan nilai frequency di DB
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching daily habits: " . $e->getMessage());
        return [];
    }
}

// Fungsi getHabitProgressForToday (JIKA Anda memiliki tabel user_sessions)
// Jika Anda tidak memiliki tabel user_sessions, fungsi ini tidak akan berfungsi
// dan Anda harus mengelola progres hanya berdasarkan last_completed_date dan status di tabel habits.
/*
function getHabitProgressForToday($pdo, $userId, $habitId, $date) {
    try {
        $stmt = $pdo->prepare("
            SELECT value_recorded, is_completed
            FROM user_sessions
            WHERE user_id = :user_id AND habit_id = :habit_id AND session_date = :session_date
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':habit_id' => $habitId,
            ':session_date' => $date
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching habit progress: " . $e->getMessage());
        return false;
    }
}
*/


//--- HABITS 
// Fungsi yang sudah ada (contoh, sesuaikan dengan milik Anda)
// Set default timezone di awal aplikasi Anda
date_default_timezone_set('Asia/Jakarta');

/**
 * Mengambil semua kebiasaan untuk user tertentu.
 * @param PDO $pdo Koneksi PDO.
 * @param int $user_id ID pengguna.
 * @return array Daftar kebiasaan.
 */
function getHabitsByUserId($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM habits WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting habits: " . $e->getMessage());
        return [];
    }
}

/**
 * Menghitung progres kebiasaan berdasarkan streak dan frekuensi.
 * Ini adalah contoh sederhana; Anda mungkin ingin logika yang lebih kompleks.
 * @param int $current_streak Streak saat ini.
 * @param string $frequency Frekuensi kebiasaan (misal: 'Setiap Hari', 'Setiap Minggu').
 * @return int Progres dalam persen (0-100).
 */
function calculateProgress($current_streak, $frequency)
{
    // Progres bisa diartikan sebagai seberapa dekat pengguna mencapai "target" tertentu
    // atau sekadar visualisasi dari streak.
    // Contoh: setiap streak 1 hari = 10% progres untuk tujuan 10 hari.
    // Jika streak 0, progres 0.
    if ($current_streak >= 10) { // Maksimum 100% jika streak mencapai 10
        return 100;
    } elseif ($current_streak > 0) {
        return min(100, $current_streak * 10); // Setiap streak 1 hari, progres +10%
    }
    return 0; // Sesuaikan dengan logika Anda
}

/**
 * Fungsi untuk menandai kebiasaan sebagai selesai.
 * Ini akan meningkatkan streak, mengupdate status, dan tanggal selesai terakhir.
 * @param PDO $pdo Koneksi PDO.
 * @param int $habit_id ID kebiasaan.
 * @param int $user_id ID pengguna.
 * @return bool True jika berhasil, false jika gagal.
 */
function markHabitAsCompleted($pdo, $habit_id, $user_id)
{
    try {
        $today = date('Y-m-d');

        // Ambil data kebiasaan saat ini
        $stmt_select = $pdo->prepare("SELECT status, current_streak, best_streak, last_completed_date FROM habits WHERE habit_id = :habit_id AND user_id = :user_id");
        $stmt_select->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
        $stmt_select->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_select->execute();
        $habit = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$habit) {
            error_log("Habit ID $habit_id not found for user ID $user_id.");
            return false;
        }

        // Cek apakah kebiasaan sudah diselesaikan hari ini
        if ($habit['status'] === 'completed' && $habit['last_completed_date'] === $today) {
            error_log("Habit ID $habit_id already completed today for user ID $user_id.");
            return false; // Sudah selesai hari ini, tidak perlu diupdate
        }

        $new_streak = $habit['current_streak'] + 1;
        $new_best_streak = max($new_streak, $habit['best_streak']);

        $stmt_update = $pdo->prepare("
            UPDATE habits
            SET
                status = 'completed',
                current_streak = :new_streak,
                best_streak = :new_best_streak,
                last_completed_date = :today,
                updated_at = NOW()
            WHERE habit_id = :habit_id AND user_id = :user_id
        ");

        $stmt_update->bindParam(':new_streak', $new_streak, PDO::PARAM_INT);
        $stmt_update->bindParam(':new_best_streak', $new_best_streak, PDO::PARAM_INT);
        $stmt_update->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt_update->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
        $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        $stmt_update->execute();

        error_log("Habit ID $habit_id completed. New streak: $new_streak.");
        return true;

    } catch (PDOException $e) {
        error_log("Error marking habit as completed: " . $e->getMessage());
        return false;
    }
}

/**
 * Otomatis mengupdate status kebiasaan dan mereset streak yang terlewatkan per hari.
 * Ini harus dijalankan sekali setiap kali user mengakses dashboard atau halaman yang menampilkan kebiasaan.
 * @param PDO $pdo Koneksi PDO.
 * @param int $user_id ID pengguna.
 * @return int Jumlah kebiasaan yang diperbarui.
 */
function autoUpdateHabitStatuses($pdo, $user_id)
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $updated_count = 0;

    try {
        // 1. Ambil semua kebiasaan pengguna
        $stmt = $pdo->prepare("SELECT habit_id, status, current_streak, last_completed_date, frequency FROM habits WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($habits as $habit) {
            $habit_id = $habit['habit_id'];
            $current_status = $habit['status'];
            $current_streak = $habit['current_streak'];
            $last_completed_date = $habit['last_completed_date'];
            $frequency = $habit['frequency']; // Ambil frekuensi

            // Logika auto-reset untuk kebiasaan harian ('Setiap Hari')
            if ($frequency === 'Setiap Hari') {
                // Jika kebiasaan sudah selesai KEMARIN atau sebelumnya, dan BUKAN hari ini
                if ($current_status === 'completed' && $last_completed_date !== $today) {
                    // Reset status ke 'active' untuk hari ini
                    // Streak akan direset ke 0 jika terakhir selesai adalah KEMARIN atau lebih lama
                    // Jika last_completed_date adalah kemarin, maka streak berlanjut
                    // Jika last_completed_date adalah 2 hari lalu atau lebih, maka streak putus
                    $new_streak = 0;
                    if ($last_completed_date === $yesterday) {
                        // Jika selesai kemarin, streak tidak putus, tapi status direset
                        // (Streak akan bertambah saat diklik "Selesai Hari Ini" lagi)
                        $new_streak = $current_streak; // Tetap pakai streak kemarin, akan di ++ saat completed
                    }
                    // Jika last_completed_date bukan kemarin (atau null), maka streak putus
                    // (new_streak sudah 0)

                    $stmt_update = $pdo->prepare("UPDATE habits SET status = 'active' WHERE habit_id = :habit_id");
                    $stmt_update->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
                    $stmt_update->execute();
                    $updated_count++;
                    error_log("Habit ID $habit_id reset to active for today. Last completed: $last_completed_date");

                }
                // Jika kebiasaan aktif tapi belum diselesaikan hari ini DAN terakhir diselesaikan BUKAN kemarin
                // Ini berarti streak-nya putus atau belum pernah diselesaikan
                // Atau, jika status 'active' dan last_completed_date bukan hari ini DAN bukan kemarin
                if ($current_status === 'active' && ($last_completed_date === null || $last_completed_date < $yesterday)) {
                     if ($current_streak > 0) { // Hanya reset jika ada streak
                        $stmt_update = $pdo->prepare("UPDATE habits SET current_streak = 0 WHERE habit_id = :habit_id");
                        $stmt_update->bindParam(':habit_id', $habit_id, PDO::PARAM_INT);
                        $stmt_update->execute();
                        $updated_count++;
                        error_log("Habit ID $habit_id streak reset to 0 (missed previous days). Last completed: $last_completed_date");
                    }
                }
            }
            // TODO: Tambahkan logika untuk frekuensi 'Setiap Minggu', 'Setiap Bulan', dll.
            // Logika untuk mingguan/bulanan akan lebih kompleks, misalnya reset setiap awal minggu/bulan.
            // Untuk saat ini, fokus pada 'Setiap Hari'.
        }
    } catch (PDOException $e) {
        error_log("Error in autoUpdateHabitStatuses: " . $e->getMessage());
        return 0;
    }
    return $updated_count;
}


//---EXERCISE

// Fungsi untuk mendapatkan semua latihan fisik untuk pengguna tertentu
function getUserWorkouts($pdo, $user_id, $limit = null)
{
    try {
        $sql = "SELECT * FROM workouts WHERE user_id = :user_id ORDER BY workout_date DESC, created_at DESC";
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user workouts: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk mendapatkan detail latihan fisik berdasarkan ID
function getWorkoutById($pdo, $workout_id, $user_id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM workouts WHERE workout_id = :workout_id AND user_id = :user_id");
        $stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting workout by ID: " . $e->getMessage());
        return null;
    }
}

// Fungsi untuk menambah latihan fisik baru
function addWorkout($pdo, $user_id, $workout_name, $description, $duration_minutes, $calories_burned, $workout_date)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO workouts (user_id, workout_name, description, duration_minutes, calories_burned, workout_date) VALUES (:user_id, :workout_name, :description, :duration_minutes, :calories_burned, :workout_date)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':workout_name', $workout_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':duration_minutes', $duration_minutes, PDO::PARAM_INT);
        $stmt->bindParam(':calories_burned', $calories_burned, PDO::PARAM_INT);
        $stmt->bindParam(':workout_date', $workout_date, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding workout: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mengedit latihan fisik
function editWorkout($pdo, $workout_id, $user_id, $workout_name, $description, $duration_minutes, $calories_burned, $workout_date)
{
    try {
        $stmt = $pdo->prepare("UPDATE workouts SET workout_name = :workout_name, description = :description, duration_minutes = :duration_minutes, calories_burned = :calories_burned, workout_date = :workout_date WHERE workout_id = :workout_id AND user_id = :user_id");
        $stmt->bindParam(':workout_name', $workout_name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':duration_minutes', $duration_minutes, PDO::PARAM_INT);
        $stmt->bindParam(':calories_burned', $calories_burned, PDO::PARAM_INT);
        $stmt->bindParam(':workout_date', $workout_date, PDO::PARAM_STR);
        $stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error editing workout: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk menghitung kalori terbakar minggu ini
function getWeeklyCaloriesBurned($pdo, $user_id)
{
    try {
        $start_of_week = date('Y-m-d', strtotime('monday this week')); // Atau 'sunday this week' tergantung awal minggu Anda
        $end_of_week = date('Y-m-d', strtotime('sunday this week')); // Atau 'saturday this week'

        $stmt = $pdo->prepare("SELECT SUM(calories_burned) FROM workouts WHERE user_id = :user_id AND workout_date BETWEEN :start_of_week AND :end_of_week");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_of_week', $start_of_week, PDO::PARAM_STR);
        $stmt->bindParam(':end_of_week', $end_of_week, PDO::PARAM_STR);
        $stmt->execute();
        $calories = $stmt->fetchColumn();
        return $calories ?? 0; // Mengembalikan 0 jika NULL
    } catch (PDOException $e) {
        error_log("Error getting weekly calories burned: " . $e->getMessage());
        return 0;
    }
}

// Fungsi untuk menghitung jumlah latihan minggu ini
function getWeeklyWorkoutCount($pdo, $user_id)
{
    try {
        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = :user_id AND workout_date BETWEEN :start_of_week AND :end_of_week");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_of_week', $start_of_week, PDO::PARAM_STR);
        $stmt->bindParam(':end_of_week', $end_of_week, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count ?? 0;
    } catch (PDOException $e) {
        error_log("Error getting weekly workout count: " . $e->getMessage());
        return 0;
    }
}



//--- FINANCE

// Fungsi untuk mendapatkan ringkasan keuangan bulanan
function getMonthlyFinancialSummary($pdo, $user_id)
{
    try {
        $first_day_of_month = date('Y-m-01');
        $last_day_of_month = date('Y-m-t');

        // Pendapatan Bulan Ini
        $stmt_income = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = :user_id AND type = 'income' AND transaction_date BETWEEN :start_date AND :end_date");
        $stmt_income->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_income->bindParam(':start_date', $first_day_of_month, PDO::PARAM_STR);
        $stmt_income->bindParam(':end_date', $last_day_of_month, PDO::PARAM_STR);
        $stmt_income->execute();
        $total_income = $stmt_income->fetchColumn();

        // Pengeluaran Bulan Ini
        $stmt_expense = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = :user_id AND type = 'expense' AND transaction_date BETWEEN :start_date AND :end_date");
        $stmt_expense->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_expense->bindParam(':start_date', $first_day_of_month, PDO::PARAM_STR);
        $stmt_expense->bindParam(':end_date', $last_day_of_month, PDO::PARAM_STR);
        $stmt_expense->execute();
        $total_expense = $stmt_expense->fetchColumn();

        $total_income = $total_income ?? 0;
        $total_expense = $total_expense ?? 0;
        $balance = $total_income - $total_expense;

        return [
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'balance' => $balance
        ];
    } catch (PDOException $e) {
        error_log("Error getting monthly financial summary: " . $e->getMessage());
        return ['total_income' => 0, 'total_expense' => 0, 'balance' => 0];
    }
}

// Fungsi untuk mendapatkan riwayat transaksi terbaru
function getRecentTransactions($pdo, $user_id, $limit = 5)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY transaction_date DESC, created_at DESC LIMIT :limit");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting recent transactions: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk menambah transaksi baru
function addTransaction($pdo, $user_id, $description, $amount, $type, $category, $transaction_date)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, description, amount, type, category, transaction_date) VALUES (:user_id, :description, :amount, :type, :category, :transaction_date)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':amount', $amount); // DECIMAL types usually don't need PDO::PARAM_STR if it's float/int
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':transaction_date', $transaction_date, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding transaction: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mendapatkan persentase pengeluaran per kategori bulan ini
function getMonthlyCategoryExpenses($pdo, $user_id)
{
    try {
        $first_day_of_month = date('Y-m-01');
        $last_day_of_month = date('Y-m-t');

        $stmt = $pdo->prepare("SELECT category, SUM(amount) as total_amount FROM transactions WHERE user_id = :user_id AND type = 'expense' AND transaction_date BETWEEN :start_date AND :end_date GROUP BY category ORDER BY total_amount DESC");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $first_day_of_month, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $last_day_of_month, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting monthly category expenses: " . $e->getMessage());
        return [];
    }
}

// Fungsi baru untuk mendapatkan semua transaksi dalam rentang tanggal
function getTransactionsByDateRange($pdo, $user_id, $start_date, $end_date)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = :user_id AND transaction_date BETWEEN :start_date AND :end_date ORDER BY transaction_date DESC, created_at DESC");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting transactions by date range: " . $e->getMessage());
        return [];
    }
}

// Fungsi baru untuk menghitung total pemasukan/pengeluaran dalam rentang tanggal
function getTotalsByDateRange($pdo, $user_id, $start_date, $end_date)
{
    try {
        $stmt = $pdo->prepare("SELECT type, SUM(amount) as total_amount FROM transactions WHERE user_id = :user_id AND transaction_date BETWEEN :start_date AND :end_date GROUP BY type");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals = ['income' => 0, 'expense' => 0];
        foreach ($results as $row) {
            $totals[$row['type']] = $row['total_amount'];
        }
        return $totals;
    } catch (PDOException $e) {
        error_log("Error getting totals by date range: " . $e->getMessage());
        return ['income' => 0, 'expense' => 0];
    }
}


// Fungsi untuk format mata uang IDR
function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}


//--- NUTRITION

/***
 * Mengambil total nutrisi harian untuk user tertentu pada tanggal tertentu.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param string $date Tanggal (format 'YYYY-MM-DD'). Default hari ini.
 * @return array Total nutrisi (kalori, protein, karbohidrat, lemak)
 */
function getDailyNutrientTotals($pdo, $userId, $date = null)
{
    if ($date === null) {
        $date = date('Y-m-d'); // Hari ini
    }

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(calories), 0) AS total_calories,
            COALESCE(SUM(protein), 0) AS total_protein,
            COALESCE(SUM(carbohydrates), 0) AS total_carbohydrates,
            COALESCE(SUM(fats), 0) AS total_fats
        FROM food_entries
        WHERE user_id = :user_id AND entry_date = :entry_date
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':entry_date' => $date
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Mengambil daftar makanan yang dicatat untuk user tertentu pada tanggal tertentu.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param string $date Tanggal (format 'YYYY-MM-DD'). Default hari ini.
 * @return array Daftar entri makanan.
 */
function getDailyFoodEntries($pdo, $userId, $date = null)
{
    if ($date === null) {
        $date = date('Y-m-d'); // Hari ini
    }

    $stmt = $pdo->prepare("
        SELECT id, food_name, calories, protein, carbohydrates, fats, entry_date
        FROM food_entries
        WHERE user_id = :user_id AND entry_date = :entry_date
        ORDER BY created_at DESC
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':entry_date' => $date
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Menambahkan entri makanan baru ke database.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param string $foodName Nama makanan.
 * @param int $calories Kalori.
 * @param float $protein Protein.
 * @param float $carbs Karbohidrat.
 * @param float $fats Lemak.
 * @param string $entryDate Tanggal entri (format 'YYYY-MM-DD').
 * @return bool True jika berhasil, false jika gagal.
 */
function addFoodEntry($pdo, $userId, $foodName, $calories, $protein, $carbs, $fats, $entryDate)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO food_entries (user_id, food_name, calories, protein, carbohydrates, fats, entry_date)
            VALUES (:user_id, :food_name, :calories, :protein, :carbohydrates, :fats, :entry_date)
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':food_name' => $foodName,
            ':calories' => $calories,
            ':protein' => $protein,
            ':carbohydrates' => $carbs,
            ':fats' => $fats,
            ':entry_date' => $entryDate
        ]);
    } catch (PDOException $e) {
        error_log("Error adding food entry: " . $e->getMessage());
        return false;
    }
}



//--- SETTINGS


/**
 * Mengambil data profil pengguna berdasarkan user_id.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @return array|false Data profil pengguna atau false jika tidak ditemukan.
 */
function getUserProfile($pdo, $userId)
{
    try {
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, avatar_url, bio FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Memperbarui data profil pengguna.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param array $data Data yang akan diperbarui (full_name, bio, avatar_url).
 * @return bool True jika berhasil, false jika gagal.
 */
function updateUserProfile($pdo, $userId, $data)
{
    try {
        $updateFields = [];
        $params = [':user_id' => $userId];

        if (isset($data['full_name'])) {
            $updateFields[] = 'full_name = :full_name';
            $params[':full_name'] = $data['full_name'];
        }
        if (isset($data['bio'])) {
            $updateFields[] = 'bio = :bio';
            $params[':bio'] = $data['bio'];
        }
        if (isset($data['avatar_url'])) {
            $updateFields[] = 'avatar_url = :avatar_url';
            $params[':avatar_url'] = $data['avatar_url'];
        }

        if (empty($updateFields)) {
            return false; // Tidak ada yang perlu diperbarui
        }

        $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return false;
    }
}



/**
 * Memperbarui kata sandi pengguna.
 *
 * @param PDO $pdo Objek PDO database.
 * @param int $userId ID pengguna.
 * @param string $currentPassword Kata sandi saat ini yang diberikan pengguna.
 * @param string $newPassword Kata sandi baru yang ingin diset.
 * @return array Hasil operasi: ['success' => bool, 'message' => string]
 */
function updatePassword($pdo, $userId, $currentPassword, $newPassword)
{
    try {
        // 1. Ambil hash kata sandi yang tersimpan untuk user ini
        $stmt = $pdo->prepare("SELECT password, email FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
        }

        $storedHash = $user['password'];
        $userEmail = $user['email'];

        // 2. Verifikasi kata sandi saat ini
        if (!password_verify($currentPassword, $storedHash)) {
            return ['success' => false, 'message' => 'Kata sandi saat ini salah.'];
        }

        // 3. Hash kata sandi baru
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // 4. Perbarui kata sandi di database
        $stmt = $pdo->prepare("UPDATE users SET password = :new_password_hash, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        if ($stmt->execute([
            ':new_password_hash' => $newPasswordHash,
            ':user_id' => $userId
        ])) {
            // Jika berhasil, kirim notifikasi email
            sendPasswordChangeNotification($userEmail);
            return ['success' => true, 'message' => 'Kata sandi berhasil diperbarui!'];
        } else {
            return ['success' => false, 'message' => 'Gagal memperbarui kata sandi di database.'];
        }
    } catch (PDOException $e) {
        error_log("Error updating password: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat memperbarui kata sandi.'];
    }
}

/**
 * Mengirim notifikasi perubahan kata sandi ke email pengguna.
 * Anda perlu mengkonfigurasi PHPMailer atau fungsi mail() di sini.
 *
 * @param string $recipientEmail Alamat email penerima.
 */
function sendPasswordChangeNotification($recipientEmail)
{
    // --- Konfigurasi PHPMailer (Direkomendasikan) ---
    // Pastikan Anda sudah menginstal PHPMailer via Composer:
    // composer require phpmailer/phpmailer
    // Dan Anda memiliki autoload.php di proyek Anda

    require_once __DIR__ . '/../vendor/autoload.php'; // Sesuaikan path ini!
    $mail = new PHPMailer\PHPMailer\PHPMailer();

    try {
        // Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'ridwankecil473@gmail.com';               // SMTP username
        $mail->Password   = 'ygpk wrqa pzxx pyug';                  // SMTP password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        // Recipients
        $mail->setFrom('no-reply@habitForge.app', 'HabitForge Support');
        $mail->addAddress($recipientEmail);     // Add a recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Notifikasi Perubahan Kata Sandi Anda';
        $mail->Body    = 'Halo,<br><br>Kata sandi akun HabitForge Anda baru saja diubah. Jika ini bukan Anda, segera hubungi dukungan kami.<br><br>Terima kasih,<br>Tim HabitForge';
        $mail->AltBody = 'Kata sandi akun HabitForge Anda baru saja diubah. Jika ini bukan Anda, segera hubungi dukungan kami.';

        $mail->send();
        // error_log("Password change notification sent to: " . $recipientEmail); // Untuk debugging
    } catch (Exception $e) {
        error_log("Failed to send password change notification to " . $recipientEmail . ": " . $mail->ErrorInfo);
    }

    /*
    // --- Alternatif: Fungsi mail() bawaan PHP (kurang disarankan untuk produksi) ---
    // Pastikan server Anda dikonfigurasi untuk mengirim email (mis. dengan sendmail/postfix)
    // $subject = "Notifikasi Perubahan Kata Sandi HabitForge";
    // $message = "Halo,\n\nKata sandi akun HabitForge Anda baru saja diubah. Jika ini bukan Anda, segera hubungi dukungan kami.\n\nTerima kasih,\nTim HabitForge";
    // $headers = "From: no-reply@yourdomain.com\r\n";
    // $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // if (mail($recipientEmail, $subject, $message, $headers)) {
    //     error_log("Password change notification sent successfully via mail().");
    // } else {
    //     error_log("Failed to send password change notification via mail().");
    // }
    */
}
