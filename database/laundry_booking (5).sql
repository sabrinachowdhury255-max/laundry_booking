-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2026 at 12:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laundry_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('confirmed','cancelled','completed') NOT NULL DEFAULT 'confirmed',
  `reminder_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `machine_id`, `booking_date`, `start_time`, `end_time`, `status`, `reminder_enabled`, `created_at`) VALUES
(1, 1, 1, '2026-09-23', '08:00:00', '09:00:00', 'cancelled', 0, '2026-09-22 09:48:51'),
(2, 2, 1, '2026-09-24', '09:00:00', '10:00:00', 'confirmed', 0, '2026-09-22 09:55:26'),
(3, 1, 1, '2026-09-23', '11:00:00', '12:00:00', 'cancelled', 0, '2026-09-22 12:10:18'),
(4, 1, 4, '2026-09-24', '12:00:00', '13:00:00', 'confirmed', 0, '2026-09-24 09:54:23'),
(5, 1, 1, '2026-09-24', '14:00:00', '15:00:00', 'confirmed', 0, '2026-09-24 11:12:57'),
(6, 1, 5, '2026-09-24', '18:00:00', '19:00:00', 'confirmed', 0, '2026-09-24 11:13:10'),
(7, 1, 4, '2026-09-24', '14:00:00', '15:00:00', 'confirmed', 0, '2026-09-24 11:27:12'),
(8, 1, 2, '2026-09-24', '14:00:00', '15:00:00', 'confirmed', 0, '2026-09-24 11:27:32'),
(9, 1, 1, '2026-09-30', '11:00:00', '12:00:00', 'confirmed', 0, '2026-09-30 08:36:55'),
(10, 1, 1, '2026-09-30', '15:00:00', '16:00:00', 'cancelled', 0, '2026-09-30 12:43:27'),
(11, 1, 1, '2026-09-30', '15:00:00', '16:00:00', 'confirmed', 0, '2026-09-30 12:44:32'),
(12, 1, 4, '2026-09-30', '15:00:00', '16:00:00', 'confirmed', 0, '2026-09-30 12:52:14'),
(13, 1, 2, '2026-10-01', '11:00:00', '12:00:00', 'confirmed', 0, '2026-10-01 07:53:49'),
(14, 4, 1, '2026-10-01', '11:00:00', '12:00:00', 'confirmed', 0, '2026-10-01 07:58:13'),
(15, 4, 3, '2026-10-01', '11:00:00', '12:00:00', 'confirmed', 0, '2026-10-01 08:04:19'),
(16, 1, 1, '2026-10-06', '16:00:00', '17:00:00', 'confirmed', 0, '2026-10-06 13:36:26'),
(17, 1, 4, '2026-10-06', '16:00:00', '17:00:00', 'confirmed', 0, '2026-10-06 13:55:32'),
(18, 1, 1, '2026-10-07', '14:00:00', '15:00:00', 'cancelled', 0, '2026-10-07 09:48:30'),
(19, 1, 1, '2026-10-07', '12:00:00', '13:00:00', 'cancelled', 0, '2026-10-07 09:48:43'),
(20, 1, 4, '2026-10-07', '12:00:00', '13:00:00', 'cancelled', 0, '2026-10-07 09:58:28'),
(21, 1, 4, '2026-10-07', '14:00:00', '15:00:00', 'cancelled', 0, '2026-10-07 10:08:38'),
(22, 1, 1, '2026-10-07', '13:00:00', '14:00:00', 'confirmed', 0, '2026-10-07 10:15:17'),
(23, 1, 3, '2026-10-07', '14:00:00', '15:00:00', 'confirmed', 0, '2026-10-07 10:15:32'),
(24, 1, 4, '2026-10-07', '13:00:00', '14:00:00', 'confirmed', 0, '2026-10-07 10:15:43'),
(25, 1, 2, '2026-10-07', '13:00:00', '14:00:00', 'confirmed', 0, '2026-10-07 10:17:11'),
(26, 1, 3, '2026-10-07', '13:00:00', '14:00:00', 'confirmed', 0, '2026-10-07 10:21:26'),
(27, 1, 1, '2026-10-07', '14:00:00', '15:00:00', 'confirmed', 1, '2026-10-07 11:31:33'),
(28, 1, 1, '2026-10-07', '17:00:00', '18:00:00', 'confirmed', 1, '2026-10-07 14:00:40'),
(29, 1, 1, '2026-10-07', '18:00:00', '19:00:00', 'confirmed', 1, '2026-10-07 14:00:57'),
(30, 8, 4, '2026-10-07', '17:00:00', '18:00:00', 'cancelled', 1, '2026-10-07 14:02:42'),
(31, 8, 3, '2026-10-07', '17:00:00', '18:00:00', 'cancelled', 1, '2026-10-07 14:03:49'),
(32, 8, 3, '2026-10-07', '18:00:00', '19:00:00', 'cancelled', 1, '2026-10-07 14:05:54'),
(33, 8, 1, '2026-10-07', '19:00:00', '20:00:00', 'confirmed', 1, '2026-10-07 14:06:35'),
(34, 9, 1, '2026-10-08', '08:00:00', '09:00:00', 'confirmed', 1, '2026-10-07 19:18:42'),
(35, 1, 4, '2026-10-08', '08:00:00', '09:00:00', 'cancelled', 1, '2026-10-07 19:21:44'),
(36, 8, 1, '2026-10-08', '12:00:00', '13:00:00', 'cancelled', 1, '2026-10-08 09:56:24'),
(37, 8, 1, '2026-10-08', '13:00:00', '14:00:00', 'confirmed', 1, '2026-10-08 09:59:13'),
(38, 8, 2, '2026-10-08', '16:00:00', '17:00:00', 'confirmed', 1, '2026-10-08 13:48:24'),
(39, 8, 1, '2026-10-08', '16:00:00', '17:00:00', 'confirmed', 1, '2026-10-08 13:48:37'),
(40, 1, 3, '2026-10-13', '08:00:00', '09:00:00', 'confirmed', 1, '2026-10-13 15:34:13'),
(41, 1, 4, '2026-10-13', '19:00:00', '20:00:00', 'cancelled', 1, '2026-10-13 16:02:16'),
(42, 10, 4, '2026-10-13', '21:00:00', '22:00:00', 'cancelled', 1, '2026-10-13 17:07:12'),
(43, 1, 4, '2026-10-13', '21:00:00', '22:00:00', 'confirmed', 1, '2026-10-13 18:02:26'),
(44, 1, 4, '2026-10-16', '21:00:00', '22:00:00', 'confirmed', 1, '2026-10-16 17:38:40'),
(45, 1, 2, '2026-10-17', '09:00:00', '10:00:00', 'cancelled', 1, '2026-10-16 20:16:48'),
(46, 1, 2, '2026-10-17', '11:00:00', '12:00:00', 'cancelled', 1, '2026-10-16 20:17:00'),
(47, 1, 2, '2026-10-17', '13:00:00', '14:00:00', 'cancelled', 1, '2026-10-16 20:28:19'),
(48, 1, 4, '2026-10-19', '14:00:00', '15:00:00', 'cancelled', 1, '2026-10-17 12:52:04'),
(49, 1, 6, '2026-10-18', '10:00:00', '11:00:00', 'cancelled', 1, '2026-10-17 13:38:54'),
(50, 1, 4, '2026-10-17', '20:00:00', '21:00:00', 'cancelled', 1, '2026-10-17 17:31:23'),
(51, 1, 3, '2026-10-17', '20:00:00', '21:00:00', 'confirmed', 1, '2026-10-17 17:34:17'),
(52, 1, 3, '2026-10-17', '21:00:00', '22:00:00', 'cancelled', 1, '2026-10-17 17:34:46'),
(53, 1, 2, '2026-10-17', '20:00:00', '21:00:00', 'cancelled', 1, '2026-10-17 17:38:18'),
(54, 4, 4, '2026-10-22', '08:00:00', '09:00:00', 'confirmed', 0, '2026-10-18 17:56:37'),
(55, 8, 4, '2026-10-20', '08:00:00', '09:00:00', 'confirmed', 1, '2026-10-18 18:03:05');

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `id` int(11) NOT NULL,
  `machine_name` varchar(50) NOT NULL,
  `machine_type` varchar(50) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`id`, `machine_name`, `machine_type`, `status`) VALUES
(1, 'Machine 1', 'Washing Machine', 'inactive'),
(2, 'Machine 2', 'Washing Machine', 'active'),
(3, 'Machine 3', 'Washing Machine', 'active'),
(4, 'Machine 4', 'Washing Machine', 'active'),
(5, 'DR1', 'Dryer', 'active'),
(6, 'DR2', 'Dryer', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resident_access`
--

CREATE TABLE `resident_access` (
  `id` int(11) NOT NULL,
  `resident_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_access`
--

INSERT INTO `resident_access` (`id`, `resident_id`, `full_name`, `email`, `is_used`, `created_at`) VALUES
(2, 'LW-QMTV-VSGT', 'John Smith', 'john@example.com', 0, '2026-10-07 11:57:31'),
(4, 'LW-AAEH-NNUK', 'shabrina arif chowdhury', 'sabrinachowdhury255@gmail.com', 0, '2026-10-07 13:08:39'),
(5, 'LW-SGDV-EJ93', 'sabrina arif', 'shabrinasyl2004@gmail.com', 0, '2026-10-07 13:11:37'),
(6, 'LW-BWX8-BD2U', 'mehrin arif chowdhury', 'mehrunarifchowdhury@gmail.com', 0, '2026-10-07 13:16:14'),
(7, 'LW-S4PK-JEZP', 'mehrin arif chowdhury', 'mehrinarifchowdhury@gmail.com', 1, '2026-10-07 13:16:43'),
(8, 'LW-K3NR-LKAL', 'mehran arif', 'arifmehran421@gmail.com', 1, '2026-10-07 13:35:29'),
(9, 'LW-C6FR-9JHA', 'AL AMIN', 'alaminali762111@gmail.com', 1, '2026-10-07 19:17:15'),
(10, 'LW-6DWZ-QF4J', 'MEHRAN ARIF CHY', 'mehranchy49@gmail.com', 1, '2026-10-13 17:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `resident_requests`
--

CREATE TABLE `resident_requests` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_requests`
--

INSERT INTO `resident_requests` (`id`, `full_name`, `email`, `username`, `password_hash`, `email_notifications_enabled`, `status`, `created_at`) VALUES
(1, 'mehran arif', 'arifmehran421@gmail.com', 'mehran arif', '$2y$10$scBWjGhYkUe.1l5ZpFFvUOzXEZTJRzeLgWgALDqbO2n07v5FI9Z0q', 1, 'approved', '2026-10-07 13:28:23'),
(2, 'AL AMIN', 'alaminali762111@gmail.com', 'AL AMIN', '$2y$10$ogl/63McfZ7vnH3qOAKMYe3uxcCi3wI.LjcnbnmKb2faOwyQ0DvVO', 1, 'approved', '2026-10-07 19:16:33'),
(3, 'MEHRAN ARIF CHY', 'mehranchy49@gmail.com', 'MEHRAN', '$2y$10$7DtK/.xbFXpXQQgLx8xSQ.TzQWuPSZvZ8dxqfjyO28XQxgGHiW1pu', 1, 'approved', '2026-10-13 17:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `resident_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('resident','admin') NOT NULL DEFAULT 'resident',
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `resident_id`, `name`, `username`, `email`, `password`, `role`, `email_notifications_enabled`) VALUES
(1, 'TEST001', 'Test User', 'AAL', 'test@example.com', '$2y$10$3GpG4OcAqzqzWPpnZTkju.ZxJc/GdheOd1QmMN/cdDv3Bog3NDX0G', 'resident', 1),
(2, '12345', 'shabrina chowdhury', NULL, 'sabrinachowdhury255@gmail.com', '$2y$10$5wmxwfIfcMobs7hWGn9i2eBtaRExP5aROQ/jrikZIP0vfuYg0hvm6', 'resident', 0),
(3, 'ADMIN001', 'System Administrator', NULL, 'admin@example.com', '$2y$12$fohp.EwK3uM3rqAy2NrNKedwd3bG0ycXlTdIQmW8H5pCPwTEfmZZK', 'admin', 0),
(4, 'shabi001', 'shabrina chowdhury', NULL, 'sabrinachowdhury25@gmail.com', '$2y$10$wEMEfxkgp.lIUNcl514.NOwJ7QZYGQ.GFAGKxs/hpIXhGBma5NQvS', 'resident', 0),
(5, 'Alamin7621', 'Al Amin', NULL, 'alaminali7621@gmail.com', '$2y$10$RrKmszFRkrQVADxH8F0dE./L1PP/i3hcr6gNjIc0FrN/Vhkm/Ofxe', 'resident', 0),
(6, 'LW-TEST-001', 'Test Resident', 'testresident', 'testresident@example.com', '$2y$10$R35co9P/cg6OQ0k0NwfOn.9MGVAHQABx4Pz6/8oTOrTicWUYAzpgm', 'resident', 0),
(7, 'LW-S4PK-JEZP', 'mehrin arif chowdhury', 'mehrin arif chowdhury', 'mehrinarifchowdhury@gmail.com', '$2y$10$RCDJm5KWKCKxEE1XxaObm.jpY3C3L.XSipCaTwdNQrJvcfO4j.Gru', 'resident', 0),
(8, 'LW-K3NR-LKAL', 'mehran arif', 'mehran arif', 'arifmehran421@gmail.com', '$2y$10$scBWjGhYkUe.1l5ZpFFvUOzXEZTJRzeLgWgALDqbO2n07v5FI9Z0q', 'resident', 1),
(9, 'LW-C6FR-9JHA', 'AL AMIN', 'AL AMIN', 'alaminali762111@gmail.com', '$2y$10$ogl/63McfZ7vnH3qOAKMYe3uxcCi3wI.LjcnbnmKb2faOwyQ0DvVO', 'resident', 1),
(10, 'LW-6DWZ-QF4J', 'MEHRAN ARIF CHY', 'MEHRAN', 'mehranchy49@gmail.com', '$2y$10$7DtK/.xbFXpXQQgLx8xSQ.TzQWuPSZvZ8dxqfjyO28XQxgGHiW1pu', 'resident', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `machine_id` (`machine_id`);

--
-- Indexes for table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `resident_access`
--
ALTER TABLE `resident_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_resident_id` (`resident_id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `resident_requests`
--
ALTER TABLE `resident_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `resident_id` (`resident_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resident_access`
--
ALTER TABLE `resident_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `resident_requests`
--
ALTER TABLE `resident_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_booking_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
