-- Migration: 002
-- Description: Create courses table
-- Date: 2026-04-28

CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `eligibility` varchar(255) NOT NULL,
  `carpet_area` varchar(255) DEFAULT NULL,
  `system_requirements` text DEFAULT NULL,
  `faculty_requirements` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
