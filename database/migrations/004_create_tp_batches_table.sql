-- Migration: 004
-- Description: Create tp_batches table
-- Date: 2026-04-28

CREATE TABLE `tp_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tp_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `batch_timing` varchar(100) NOT NULL,
  `batch_capacity` int(11) NOT NULL DEFAULT 50,
  `deadline_date` date NOT NULL,
  `status` enum('active','completed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tp_id` (`tp_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `tp_batches_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tp_batches_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
