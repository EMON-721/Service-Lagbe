-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 03:11 PM
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
-- Database: `loklagbe`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `is_admin` (`user_id` INT) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE user_role VARCHAR(20);
    SELECT role INTO user_role FROM users WHERE id = user_id LIMIT 1;
    RETURN user_role = 'admin';
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `price` decimal(10,2) NOT NULL,
  `address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `provider_id`, `service_id`, `booking_date`, `booking_time`, `status`, `price`, `address`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 4, '2025-12-01', '09:00:00', 'completed', 3000.00, 'A, B, Dhaka 234, Bangladesh', '', '2025-11-30 12:06:44', '2025-11-30 13:30:49'),
(2, 3, 1, 5, '2025-12-01', '09:00:00', 'cancelled', 800.00, 'A, B, Dhaka 234, Bangladesh', 'df', '2025-11-30 14:10:25', '2025-11-30 14:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_images`
--

CREATE TABLE `hero_images` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero_images`
--

INSERT INTO `hero_images` (`id`, `image_path`, `title`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '/placeholder.svg?height=400&width=500', 'Default Hero Image', 1, '2025-11-30 11:46:32', '2025-11-30 11:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `provider_services`
--

CREATE TABLE `provider_services` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provider_services`
--

INSERT INTO `provider_services` (`id`, `provider_id`, `service_id`, `price`, `created_at`) VALUES
(1, 1, 1, 500.00, '2025-11-30 11:35:44'),
(2, 1, 2, 300.00, '2025-11-30 11:35:44'),
(3, 1, 3, 2000.00, '2025-11-30 11:35:44'),
(4, 1, 4, 3000.00, '2025-11-30 11:35:44'),
(5, 1, 5, 800.00, '2025-11-30 11:35:44'),
(6, 1, 6, 400.00, '2025-11-30 11:35:44'),
(7, 1, 7, 500.00, '2025-11-30 11:35:44'),
(8, 1, 8, 600.00, '2025-11-30 11:35:44'),
(9, 1, 9, 1500.00, '2025-11-30 11:35:44'),
(10, 1, 10, 2000.00, '2025-11-30 11:35:44'),
(11, 1, 11, 1000.00, '2025-11-30 11:35:44'),
(12, 1, 12, 5000.00, '2025-11-30 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `name`, `description`, `price`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Furniture Repair', 'Fix broken furniture and restore its functionality', 500.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(2, 1, 'Door Repair', 'Fix door hinges, handles, and locks', 300.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(3, 2, 'Home Deep Cleaning', 'Thorough cleaning of your entire home', 2000.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(4, 2, 'Office Cleaning', 'Professional cleaning services for offices', 3000.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(5, 3, 'Electrical Wiring', 'Installation and repair of electrical wiring', 800.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(6, 3, 'Fan Installation', 'Installation of ceiling and wall fans', 400.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(7, 4, 'Pipe Leakage Repair', 'Fix leaking pipes and prevent water damage', 500.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(8, 4, 'Toilet Repair', 'Fix toilet issues and replace parts if needed', 600.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(9, 5, 'Interior Painting', 'Professional painting for interior walls', 1500.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(10, 5, 'Exterior Painting', 'Weather-resistant painting for exterior walls', 2000.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(11, 6, 'Furniture Moving', 'Safe transportation of furniture within the city', 1000.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(12, 6, 'Home Shifting', 'Complete home relocation services', 5000.00, NULL, '2025-11-30 11:35:44', '2025-11-30 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `description`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Home Repair', 'Professional repair services for all your household needs', 'fa-tools', '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(2, 'Cleaning', 'Expert cleaning services for homes and offices', 'fa-broom', '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(3, 'Electrical', 'Certified electricians for all electrical work', 'fa-bolt', '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(4, 'Plumbing', 'Reliable plumbing services for any water-related issues', 'fa-tint', '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(5, 'Painting', 'Professional painting services for interior and exterior', 'fa-paint-roller', '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(6, 'Transport', 'Reliable transportation services for goods and people', 'fa-car', '2025-11-30 11:35:44', '2025-11-30 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `service_providers`
--

CREATE TABLE `service_providers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_providers`
--

INSERT INTO `service_providers` (`id`, `user_id`, `bio`, `experience`, `rating`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 1, 'Experienced service provider with expertise in multiple areas', 5, 4.80, 1, '2025-11-30 11:35:44', '2025-11-30 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('user','admin','provider') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `profile_image`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@loklagbe.com', '01700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', '2025-11-30 11:35:44', '2025-11-30 11:35:44'),
(2, 'rony', 'rony@gmail.com', '01567897643', '$2y$10$kCMFvG2A1OIaD3O3Itji/ue2iIvinzI3fMKe2KyE167riT9C6eY3S', NULL, 'provider', '2025-11-30 11:48:21', '2025-11-30 12:01:34'),
(3, 'Minhaz', 'minhaz@gmail.com', '01345678675', '$2y$10$OjYprAZKhuBHW3mWeIEe1.OQtnoBpamXIA9mAfCAD7NGAtE5/7SOK', NULL, 'user', '2025-11-30 12:04:14', '2025-11-30 12:04:14'),
(4, 'Emon', 'emon@gmail.com', '01345678673', '$2y$10$c2MrVq8ke6TUq/pFJa7/BubuunvmKADXUAomPhyBwrFaBxg4peT6G', NULL, 'admin', '2025-11-30 12:09:37', '2025-11-30 12:10:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Bangladesh',
  `is_default` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `street`, `city`, `state`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 3, 'A', 'B', 'Dhaka', '234', 'Bangladesh', 1, '2025-11-30 12:06:44', '2025-11-30 12:06:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_images`
--
ALTER TABLE `hero_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_services`
--
ALTER TABLE `provider_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hero_images`
--
ALTER TABLE `hero_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `provider_services`
--
ALTER TABLE `provider_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `provider_services`
--
ALTER TABLE `provider_services`
  ADD CONSTRAINT `provider_services_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `provider_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD CONSTRAINT `service_providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
