-- Migration: 005
-- Description: Create activities table
-- Date: 2026-04-28

CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tp_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tp_id` (`tp_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
