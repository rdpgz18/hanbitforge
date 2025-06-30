-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2025 at 04:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `habitforge_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `food_entries`
--

CREATE TABLE `food_entries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `calories` int(11) NOT NULL DEFAULT 0,
  `protein` decimal(8,2) NOT NULL DEFAULT 0.00,
  `carbohydrates` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fats` decimal(8,2) NOT NULL DEFAULT 0.00,
  `entry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `habits`
--

CREATE TABLE `habits` (
  `habit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `habit_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `current_streak` int(11) DEFAULT 0,
  `last_completed_date` date DEFAULT NULL,
  `status` enum('active','completed','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habits`
--

INSERT INTO `habits` (`habit_id`, `user_id`, `habit_name`, `description`, `frequency`, `current_streak`, `last_completed_date`, `status`, `created_at`) VALUES
(1, 1, 'Membaca Buku', 'Baca 15 halaman setiap pagi.', 'Setiap Hari', 1, '2025-06-30', 'completed', '2025-06-14 00:06:02'),
(2, 1, 'Minum Air', 'Minum 8 gelas air setiap hari.', 'Setiap Hari', 1, '2025-06-30', 'completed', '2025-06-14 00:06:02'),
(3, 1, 'Latihan Meditasi', 'Meditasi 10 menit sebelum tidur.', 'Setiap Malam', 1, '2025-06-30', 'completed', '2025-06-14 00:06:02'),
(4, 2, 'Meditasi', 'Meditasi', 'Setiap Hari', 1, '2025-06-14', 'completed', '2025-06-14 04:04:16'),
(5, 1, 'Meditasi : Malam 15 menit', 'Meditasi malam hari sebelum tidur', 'Setiap Hari', 1, '2025-06-30', 'completed', '2025-06-14 12:35:45');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `user_id`, `description`, `amount`, `type`, `category`, `transaction_date`, `created_at`) VALUES
(1, 1, 'Assets', 99999999.99, 'income', 'Buisniss', '2025-06-14', '2025-06-14 07:36:41'),
(2, 1, 'Uang Makan', 100000.00, 'expense', 'Uang Makan', '2025-06-14', '2025-06-14 09:49:48'),
(3, 2, 'Gaji', 2500000.00, 'income', 'Gaji Pokok', '2025-06-14', '2025-06-14 11:00:14'),
(4, 2, 'Operasional', 2000000.00, 'expense', 'Operasional', '2025-06-14', '2025-06-14 11:03:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `full_name`, `email`, `phone_number`, `password`, `avatar_url`, `bio`, `created_at`, `updated_at`, `failed_login_attempts`, `last_failed_login`) VALUES
(1, 'Ridwan', 'Ridwan', 'ridwan.mail1000@gmail.com', '083891162392', '$2y$10$xgQFSqm4OfWx.w3jgq98Du60BqwHKNgqHWi7MWYkn4r1qLJatx.w2', 'uploads/avatars/832387c4b7954483c0702e7179432bd9.png', 'Aku Bukan Yang Terbaik Tapi Aku Berusaha menjadi lebih baik', '2025-06-13 23:56:17', '2025-06-30 14:42:28', 0, NULL),
(2, 'user1', 'Rudi Sanjaya', 'garpuhnet.site@outlook.com', '082145450252', '$2y$10$l00wflnvXqRZp8.tkQUSluOj8OhtLIookxqGeb.kd3oaZd1gDNrZq', 'uploads/avatars/388bd3bd3ee036856b782b3615d0828e.jpg', 'tidak dapat bicara!', '2025-06-14 03:58:18', '2025-06-14 17:07:43', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_consents`
--

CREATE TABLE `user_consents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `consent_given_at` datetime DEFAULT current_timestamp(),
  `consent_type` varchar(50) NOT NULL DEFAULT 'cache_cookie',
  `is_accepted` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(21, 2, '9522de8934ec8734c719781d70e9ebae292bbcf6792b98c18fed389c1649b47e', '2025-07-14 19:08:02', '2025-06-14 04:02:03'),
(22, 2, '9522de8934ec8734c719781d70e9ebae292bbcf6792b98c18fed389c1649b47e', '2025-07-14 19:08:02', '2025-06-14 04:09:02'),
(27, 1, '0cb478a8c457afcaf3593e2507ed14f3b76224dcf106b459ebd37172da4ccf10', '2025-07-30 16:43:23', '2025-06-14 12:32:14');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `workout_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `workout_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `calories_burned` int(11) DEFAULT NULL,
  `workout_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`workout_id`, `user_id`, `workout_name`, `description`, `duration_minutes`, `calories_burned`, `workout_date`, `created_at`) VALUES
(1, 1, 'Renang : Gaya Bebas', 'Renang gaya bebas', 35, 700, '2025-06-14', '2025-06-14 09:42:08'),
(2, 2, 'Renang : gaya bebas', 'Renan', 35, 700, '2025-06-14', '2025-06-14 11:05:22'),
(3, 1, 'Jalan : Jalan Santai', 'Jalan santai 30 amenit', 30, 300, '2025-06-14', '2025-06-14 19:33:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `food_entries`
--
ALTER TABLE `food_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `habits`
--
ALTER TABLE `habits`
  ADD PRIMARY KEY (`habit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `user_consents`
--
ALTER TABLE `user_consents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_consent_per_session` (`session_id`,`consent_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`workout_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `food_entries`
--
ALTER TABLE `food_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `habits`
--
ALTER TABLE `habits`
  MODIFY `habit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_consents`
--
ALTER TABLE `user_consents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `workout_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_entries`
--
ALTER TABLE `food_entries`
  ADD CONSTRAINT `food_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `habits`
--
ALTER TABLE `habits`
  ADD CONSTRAINT `habits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
