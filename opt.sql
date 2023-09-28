-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2023 at 12:32 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `opt`
--

-- --------------------------------------------------------

--
-- Table structure for table `cargos`
--

CREATE TABLE `cargos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cargos`
--

INSERT INTO `cargos` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'shipment-1', 'cargo_1', '2023-09-02 16:22:18', '2023-09-02 16:22:18'),
(2, 'shipment-2', 'cargo-2', '2023-09-02 16:27:30', '2023-09-02 16:27:30'),
(3, 'shipment-3', 'shipment-3', '2023-09-27 23:40:51', '2023-09-27 23:40:51'),
(4, 'shipment-4', 'shipment-4', '2023-09-27 23:40:56', '2023-09-27 23:40:56'),
(5, 'shipment-5', 'shipment-5', '2023-09-27 23:41:01', '2023-09-27 23:41:01'),
(6, 'shipment-6', 'shipment-6', '2023-09-27 23:41:06', '2023-09-27 23:41:06'),
(7, 'shipment-7', 'shipment-7', '2023-09-27 23:41:12', '2023-09-27 23:41:12'),
(8, 'shipment-8', 'shipment-8', '2023-09-27 23:41:17', '2023-09-27 23:41:17');

-- --------------------------------------------------------

--
-- Table structure for table `cargo_information`
--

CREATE TABLE `cargo_information` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cargo_id` bigint(20) UNSIGNED NOT NULL,
  `box_dimension` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cargo_information`
--

INSERT INTO `cargo_information` (`id`, `cargo_id`, `box_dimension`, `quantity`, `slug`, `created_at`, `updated_at`) VALUES
(1, 1, '1*1*1.70', 31, 'box-1', '2023-09-02 16:22:22', '2023-09-27 23:59:58'),
(2, 2, '1*1.2*1.70', 20, 'cargo-1box', '2023-09-02 16:31:05', '2023-09-28 00:00:42'),
(3, 2, '0.6*1.2*1.70', 10, 'cargo-2box', '2023-09-03 03:44:09', '2023-09-28 00:01:21'),
(4, 3, '2*2*1.50', 10, 'cargo-7box', '2023-09-27 23:47:01', '2023-09-28 00:01:52'),
(5, 4, '1.5*1.5*1.45', 8, 'cargo-4box', '2023-09-28 00:02:39', '2023-09-28 00:02:39'),
(6, 5, '0.60*1*1.65', 30, 'cargo-5box', '2023-09-28 00:03:14', '2023-09-28 00:03:14'),
(7, 6, '0.60*0.60*1.50', 8, 'cargo-6box', '2023-09-28 00:03:40', '2023-09-28 00:03:40'),
(8, 6, '1*1.2*1.50', 9, 'cargo-6box-1', '2023-09-28 00:04:14', '2023-09-28 00:04:14'),
(9, 7, '2*2*1.50', 5, 'cargo-7box-1', '2023-09-28 00:04:41', '2023-09-28 00:04:41'),
(10, 7, '1*0.60*1.45', 22, 'cargo-7box-2', '2023-09-28 00:05:07', '2023-09-28 00:05:07'),
(11, 8, '1*1.2*1.7', 50, 'cargo-8box', '2023-09-28 02:21:28', '2023-09-28 02:21:28');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2022_12_11_123119_create_visitor_infos_table', 1),
(6, '2023_09_02_200930_create_cargos_table', 1),
(7, '2023_09_02_200950_create_cargo_information_table', 1),
(8, '2023_09_02_201027_create_trucks_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trucks`
--

CREATE TABLE `trucks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `truck_type` varchar(255) NOT NULL,
  `max_weight` decimal(8,2) NOT NULL,
  `length` decimal(8,2) NOT NULL,
  `width` decimal(8,2) NOT NULL,
  `height` decimal(8,2) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trucks`
--

INSERT INTO `trucks` (`id`, `truck_type`, `max_weight`, `length`, `width`, `height`, `slug`, `created_at`, `updated_at`) VALUES
(1, '1 Ton Side grill', '1.00', '2.00', '1.10', '1.00', '1-ton-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(3, '1 Ton Box', '1.00', '2.00', '1.10', '1.10', '1-ton-box', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(4, '3 Ton Side grill', '3.00', '4.00', '1.85', '1.80', '3-ton-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(5, '3 Ton Box', '3.00', '4.00', '1.85', '1.70', '3-ton-box', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(6, '7 Ton Side grill', '7.00', '6.00', '2.44', '1.85', '7-ton-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(7, '7 Ton Box', '7.00', '6.00', '2.44', '1.70', '7-ton-box', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(8, '10 Ton Side grill', '10.00', '7.00', '2.40', '1.85', '10-ton-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(9, '10 Ton Box', '10.00', '7.00', '2.40', '1.75', '10-ton-box', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(10, '12m Flatbed Box', '21.00', '11.50', '2.45', '1.75', '12m-flatbed-box', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(11, '12m Flatbed Open', '21.00', '11.50', '2.45', '1.85', '12m-flatbed-open', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(12, '12m Flatbed Side grill', '21.00', '11.50', '2.45', '1.85', '12m-flatbed-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(13, '13.60m Flatbed Side grill', '21.00', '13.00', '2.45', '1.85', '13.60m-flatbed-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(14, '13.60m Side Curtain', '18.00', '13.00', '2.45', '1.75', '13.60m-side-curtain', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(15, '13.60m Box', '18.00', '13.00', '2.45', '1.75', '13.60m-box', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(16, '15.60m Side Curtain', '18.00', '14.25', '2.45', '1.75', '15.60m-side-curtain', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(17, '15.60m Side grill', '18.00', '14.25', '2.45', '1.85', '15.60m-side-grill', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(18, '12m Double Axle Heavy Duty 28 Tons', '28.00', '14.25', '2.45', '1.75', '12m-double-axle-heavy-duty-28-tons', '2023-09-03 22:44:05', '2023-09-03 22:44:05'),
(19, '14m Low Bed', '50.00', '14.00', '3.00', '4.50', '14m-low-bed', '2023-09-03 22:44:05', '2023-09-03 22:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@admin.com', NULL, '$2y$10$Jcr4Wp0AcXKBFa2wQ8xrMuiegXOHIQ9RjTdavLo3SDoLmDHF.ojiK', '', NULL, '2023-09-03 22:44:04', '2023-09-03 22:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_infos`
--

CREATE TABLE `visitor_infos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `countryName` varchar(255) DEFAULT NULL,
  `countryCode` varchar(255) DEFAULT NULL,
  `regionName` varchar(255) DEFAULT NULL,
  `regionCode` varchar(255) DEFAULT NULL,
  `cityName` varchar(255) DEFAULT NULL,
  `zipCode` varchar(255) DEFAULT NULL,
  `isoCode` varchar(255) DEFAULT NULL,
  `postalCode` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `metroCode` varchar(255) DEFAULT NULL,
  `areaCode` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cargos_slug_unique` (`slug`);

--
-- Indexes for table `cargo_information`
--
ALTER TABLE `cargo_information`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cargo_information_slug_unique` (`slug`),
  ADD KEY `cargo_information_cargo_id_foreign` (`cargo_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `trucks`
--
ALTER TABLE `trucks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trucks_slug_unique` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `visitor_infos`
--
ALTER TABLE `visitor_infos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cargo_information`
--
ALTER TABLE `cargo_information`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trucks`
--
ALTER TABLE `trucks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `visitor_infos`
--
ALTER TABLE `visitor_infos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cargo_information`
--
ALTER TABLE `cargo_information`
  ADD CONSTRAINT `cargo_information_cargo_id_foreign` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
