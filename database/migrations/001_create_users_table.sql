-- Migration: 001
-- Description: Create users table
-- Date: 2026-04-28

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `center_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text DEFAULT NULL,
  `gps_link` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','tp') NOT NULL DEFAULT 'tp',
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `center_id` (`center_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
