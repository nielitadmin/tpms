-- Migration: 003
-- Description: Create students table
-- Date: 2026-04-28

CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tp_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tp_id` (`tp_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
