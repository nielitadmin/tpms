-- Migration: 006
-- Description: Create placements table
-- Date: 2026-04-28

CREATE TABLE `placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tp_id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `package` varchar(50) DEFAULT NULL,
  `placement_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tp_id` (`tp_id`),
  CONSTRAINT `placements_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
