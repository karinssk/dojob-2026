-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 31, 2026 at 07:45 AM
-- Server version: 10.11.11-MariaDB-0+deb12u1-log
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rubyshop.co.th_dojob`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_mappings_arr`
--

CREATE TABLE `user_mappings_arr` (
  `id` int(11) NOT NULL,
  `line_user_id` varchar(255) NOT NULL,
  `rise_user_id` int(11) NOT NULL,
  `nick_name` varchar(255) DEFAULT NULL,
  `line_display_name` varchar(255) DEFAULT NULL,
  `duty_role` enum('boss','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `line_user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of LINE user IDs' CHECK (json_valid(`line_user_ids`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_mappings_arr`
--

INSERT INTO `user_mappings_arr` (`id`, `line_user_id`, `rise_user_id`, `nick_name`, `line_display_name`, `duty_role`, `is_active`, `line_user_ids`, `created_at`, `updated_at`) VALUES
(1, 'U0e70424426101ba374a3e27a45c6cfce', 1, 'พี่ตี้', 'THEMOST ', 'staff', 1, '[\"U0e70424426101ba374a3e27a45c6cfce\"]', '2025-10-04 09:12:41', '2025-10-04 09:12:41'),
(2, 'U4570233bcad0163390aa123df54d0f81', 3, 'พี่ mike', 'mike', 'staff', 1, '[\"U4570233bcad0163390aa123df54d0f81\"]', '2025-10-04 09:12:41', '2025-10-08 01:47:19'),
(3, 'Ud5fc1f611437c15ef91b8d70d8f411c8', 4, 'พี่เล็ก', 'Rubyshop lek', 'staff', 1, '[\"Ud5fc1f611437c15ef91b8d70d8f411c8\",\"Ue42351264b464ef1a5c2c287387b1f5a\",\"U37fb99a97bedf0a59b1da73b4b3b7840\",\"U37fb99a97bedf0a59b1da73b4b3b7840\"]', '2025-10-04 09:12:41', '2025-10-28 02:06:43'),
(4, 'U98f052fe51c8830fb8fa70f623ba3d54', 5, 'Benjawan Chomsuk', 'Aof', 'boss', 1, '[\"U4748247787fa236cce6a48ff901a3277\",\"Ub911d8ab19dc42e3cf41400b12b2f065\",\"U98f052fe51c8830fb8fa70f623ba3d54\"]', '2025-10-04 09:12:41', '2025-10-04 10:38:17'),
(5, 'U0d689b5bb12a67105e40f4955ac49a58', 6, 'พี่ นุ้ย', 'RUBYSHOP Sale. (New) ', 'staff', 1, '[\"U2ecc26052e4091fb0a68839a96e061ac\",\"U0d689b5bb12a67105e40f4955ac49a58\"]', '2025-10-04 09:12:41', '2025-10-08 01:47:31'),
(6, 'U75844c0c9c87b7ee2cfff3d28636504f', 22, 'ช้าง', 'P\'Rrin', 'boss', 1, '[\"Ud580f5646afc4193eb680477e21ceebe\",\"U75844c0c9c87b7ee2cfff3d28636504f\",\"U7b529cac4d0206deff5d8483a0d61b2c\"]', '2025-10-04 09:12:41', '2025-12-12 02:25:42'),
(7, 'U0631d2a95c0fca5683c1668332a88788', 24, 'พี่ เดี่ยว ', 'จุฬา พรมไทยสงค', 'staff', 1, '[\"U0631d2a95c0fca5683c1668332a88788\",\"Udf2a9b6c0e52dfd2564ced8c0522de4a\"]', '2025-10-04 09:12:41', '2025-10-08 01:47:42'),
(8, 'dom', 27, 'โดม', 'Dome. ', 'staff', 1, '[\"dom\"]', '2025-10-04 09:12:41', '2025-10-04 09:12:41'),
(9, 'U89870f18977094f599e47290f358c7b0', 30, 'เนย', 'เนย', 'staff', 1, '[\"U89870f18977094f599e47290f358c7b0\",\"Ufed3d780d605a69e12c6d1bca3095d74\",\"Uc160e1d16ad9aa1e5d2ba4f84547f6d1\"]', '2025-10-04 09:12:41', '2025-10-04 09:12:41'),
(23, 'U2113e5674f31198ce406e586134ceb8f', 35, 'สุกัญญา พุกพิบูลย์', 'U2113e5674f31198ce406e586134ceb8f', 'staff', 1, '[\"U2113e5674f31198ce406e586134ceb8f\"]', '2025-11-11 03:06:44', '2025-11-11 03:06:44'),
(35, 'Ubd6982bb89c6f3f093d9516f3425883a', 35, 'mook', 'mook', 'staff', 1, '[\"Ubd6982bb89c6f3f093d9516f3425883a\"]', '2025-10-28 12:31:26', '2025-10-28 12:31:43'),
(48, 'U94393eae109c35f1c668416676b77948', 23, 'พี่เก้า', 'พี่เก้า', 'staff', 1, '[\"U94393eae109c35f1c668416676b77948\"]', '2025-11-05 03:11:51', '2025-11-05 03:11:51'),
(50, 'U37fb99a97bedf0a59b1da73b4b3b7840', 4, 'พี่ เล็ก', 'อโรชา (พี่เล็ก)', 'staff', 1, '[\"U37fb99a97bedf0a59b1da73b4b3b7840\"]', '2025-10-28 02:09:52', '2025-11-03 02:00:01'),
(51, 'Uc68e451f1c3945233093dbbd98281d3a', 5, 'รุ่งอรุณ ลาดนอก (พี่นุ้ย)', 'รุ่งอรุณ ลาดนอก', 'staff', 1, '[\"Uc68e451f1c3945233093dbbd98281d3a}\"]', '2025-11-03 01:58:39', '2025-11-03 01:59:13'),
(52, 'U94393eae109c35f1c668416676b77948', 23, 'ศรีเพชร (พี่เก้า)', 'ศรีเพชร (พี่เก้า)', 'staff', 1, '[\"U94393eae109c35f1c668416676b77948\"]', '2025-11-05 03:11:51', '2025-11-05 03:12:57'),
(70, 'U441fe09ded061e38874f36a2217b320a', 39, 'อุ้ม', 'อุ้ม', 'staff', 1, '[\"U441fe09ded061e38874f36a2217b320a\"]', '2025-12-01 02:28:03', '2025-12-02 08:43:38'),
(71, 'U71e7185c4d41e6c646be02fa2a6baa37', 39, 'อภิญญา ญาณโภชน์', 'อภิญญา ญาณโภชน์', 'staff', 1, NULL, '2025-12-06 06:22:03', '2025-12-06 06:24:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_mappings_arr`
--
ALTER TABLE `user_mappings_arr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_line_user_id` (`line_user_id`),
  ADD KEY `idx_rise_user_id` (`rise_user_id`),
  ADD KEY `idx_duty_role` (`duty_role`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_mappings_arr`
--
ALTER TABLE `user_mappings_arr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
