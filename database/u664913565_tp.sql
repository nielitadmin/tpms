-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 28, 2026 at 09:45 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u664913565_tp`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `tp_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `eligibility` varchar(255) NOT NULL,
  `carpet_area` varchar(255) DEFAULT NULL,
  `system_requirements` text DEFAULT NULL,
  `faculty_requirements` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `duration`, `eligibility`, `carpet_area`, `system_requirements`, `faculty_requirements`, `status`) VALUES
(1, 'O level (IT)', '540 Hours', 'Level 4 (18 Credits)', NULL, NULL, NULL, 'active'),
(2, 'A level', '1620 Hours', 'Level 5 (54 Credits)', NULL, NULL, NULL, 'active'),
(3, 'Course on Computer Concepts (CCC)', '80 Hours', 'Level 3 (3 Credits)', NULL, NULL, NULL, 'active'),
(4, 'Data Analysis Associate', '450 Hours', 'Level 3 (15 Credits)', NULL, NULL, NULL, 'active'),
(5, 'Cyber Security Assistant', '300 Hours', 'Level 3 (10 Credits)', NULL, NULL, NULL, 'active'),
(6, 'Junior Cyber Security Associate', '450 Hours', 'Level 4 (15 Credits)', NULL, NULL, NULL, 'active'),
(7, 'Cyber Security Associate', '540 Hours', 'Level 4.5 (18 Credits)', NULL, NULL, NULL, 'active'),
(8, 'Cloud Computing Assistant', '300 Hours', 'Level 3 (10 Credits)', NULL, NULL, NULL, 'active'),
(9, 'Junior Cloud Computing Associate', '450 Hours', 'Level 4 (15 Credits)', NULL, NULL, NULL, 'active'),
(10, 'Artificial Intelligence Assistant', '300 Hours', 'Level 3 (10 Credits)', NULL, NULL, NULL, 'active'),
(11, 'Artificial Intelligence Associate', '450 Hours', 'Level 4 (15 Credits)', NULL, NULL, NULL, 'active'),
(12, 'Artificial Intelligence Application Developer', '540 Hours', 'Level 4.5 (18 Credits)', NULL, NULL, NULL, 'active'),
(13, 'Cloud Computing with AWS and Azure', '120 Hours', 'Level 3 (4 Credits)', NULL, NULL, NULL, 'active'),
(14, 'Data Analysis with Python and SQL', '120 Hours', 'Level 3 (4 Credits)', NULL, NULL, NULL, 'active'),
(15, 'Basics of Python Programming', '30 Hours', 'Level 3 (1 Credit)', NULL, NULL, NULL, 'active'),
(16, 'Overview of AI Technology', '7.5 Hours', 'Level 3 (0.25 Credits)', NULL, NULL, NULL, 'active'),
(17, 'Overview of Data Science', '7.5 Hours', 'Level 3 (0.25 Credits)', NULL, NULL, NULL, 'active'),
(18, 'Scientific Assistant in Data Science for Life Sciences', '450 Hours', 'Level 4 (15 Credits)', NULL, NULL, NULL, 'active'),
(19, 'Multimedia Data Analyst', '660 Hours', 'Level 3.5 (22 Credits)', NULL, NULL, NULL, 'active'),
(20, 'ITeS BPO Executive - Voice', '330 Hours', 'Level 3 (11 Credits)', NULL, NULL, NULL, 'active'),
(21, 'Multimedia Development Associate', '330 Hours', 'Level 3 (11 Credits)', NULL, NULL, NULL, 'active'),
(22, 'Cyber Security and Social Media Analyst', '540 Hours', 'Level 3 (18 Credits)', NULL, NULL, NULL, 'active'),
(23, 'Full Stack Development Associate', '390 Hours', 'Level 4 (13 Credits)', NULL, NULL, NULL, 'active'),
(24, 'Essentials of Data Warehousing', '90 Hours', 'Level 3 (3 Credits)', NULL, NULL, NULL, 'active'),
(25, 'Essentials of Big Data', '90 Hours', 'Level 3 (3 Credits)', NULL, NULL, NULL, 'active'),
(26, 'Cyber Security for Cloud Infrastructure', '120 Hours', 'Level 3 (4 Credits)', NULL, NULL, NULL, 'active'),
(27, 'Vulnerability Assessment and Penetration Testing and IAM Essentials', '90 Hours', 'Level 3 (3 Credits)', NULL, NULL, NULL, 'active'),
(28, 'New Technologies Introduction', '60 Hours', 'Level 3 (2 Credits)', NULL, NULL, NULL, 'active'),
(29, 'Risk Control and Internal Audit - IT', '60 Hours', 'Level 3 (2 Credits)', NULL, NULL, NULL, 'active'),
(30, 'IT Professional Skills', '60 Hours', 'Level 3 (2 Credits)', NULL, NULL, NULL, 'active'),
(31, 'Basics of Artificial Intelligence & Data Science', '15 Hours', 'Level 3.5 (0.5 Credits)', NULL, NULL, NULL, 'active'),
(32, 'Introduction to Data Annotation', '15 Hours', 'Level 3.5 (0.5 Credits)', NULL, NULL, NULL, 'active'),
(33, 'Basics of Text Annotation', '7.5 Hours', 'Level 3.5 (0.25 Credits)', NULL, NULL, NULL, 'active'),
(34, 'Basics of Image & Video Annotation', '15 Hours', 'Level 3.5 (0.5 Credits)', NULL, NULL, NULL, 'active'),
(35, 'Basics of Audio Annotation', '7.5 Hours', 'Level 3.5 (0.25 Credits)', NULL, NULL, NULL, 'active'),
(36, 'Emerging trends in AI Assisted Annotation and Best Practices', '15 Hours', 'Level 3.5 (0.5 Credits)', NULL, NULL, NULL, 'active'),
(37, 'Applications of Data Annotation in Agriculture', '30 Hours', 'Level 3.5 (1 Credit)', NULL, NULL, NULL, 'active'),
(38, 'Applications of Data Annotation in Education', '30 Hours', 'Level 3.5 (1 Credit)', NULL, NULL, NULL, 'active'),
(39, 'Applications of Data Annotation in Healthcare', '30 Hours', 'Level 3.5 (1 Credit)', NULL, NULL, NULL, 'active'),
(40, 'Applications of Data Annotation in Manufacturing', '30 Hours', 'Level 3.5 (1 Credit)', NULL, NULL, NULL, 'active'),
(41, 'Introduction to Data Curation', '7.5 Hours', 'Level 3.5 (0.25 Credits)', NULL, NULL, NULL, 'active'),
(42, 'Introduction to Data Collection & Acquisition Methods', '22.5 Hours', 'Level 3.5 (0.75 Credits)', NULL, NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `helpdesk_videos`
--

CREATE TABLE `helpdesk_videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `video_url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `published_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `placements`
--

CREATE TABLE `placements` (
  `id` int(11) NOT NULL,
  `tp_id` int(11) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `package` varchar(50) DEFAULT NULL,
  `placement_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `tp_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `enrollment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tp_batches`
--

CREATE TABLE `tp_batches` (
  `id` int(11) NOT NULL,
  `tp_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `batch_timing` varchar(100) NOT NULL,
  `batch_capacity` int(11) NOT NULL DEFAULT 50,
  `deadline_date` date NOT NULL,
  `status` enum('active','completed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tp_batches`
--

INSERT INTO `tp_batches` (`id`, `tp_id`, `course_id`, `batch_number`, `batch_timing`, `batch_capacity`, `deadline_date`, `status`, `created_at`) VALUES
(1, 2, 1, 'RDH', '167567', 50, '2026-05-14', 'active', '2026-04-24 11:18:24'),
(2, 2, 1, 'RDH', '167567', 50, '2026-05-14', 'active', '2026-04-24 11:18:40'),
(3, 2, 2, 'erfet34', '32453', 50, '2026-04-08', 'active', '2026-04-27 06:40:21'),
(4, 2, 2, 'erfet34', '32453', 50, '2026-04-08', 'active', '2026-04-27 06:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `center_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text DEFAULT NULL,
  `gps_link` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','tp') NOT NULL DEFAULT 'tp',
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `center_id`, `name`, `email`, `phone`, `address`, `gps_link`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'ADMIN001', 'System Admin', 'admin@nielitbhubaneswar.in', '0000000000', NULL, NULL, '$2y$10$BLAHQ1xu1rOB83yBdd5ITeB1eFTn6makMN6yS4cUEReoEFBOj3s52', 'admin', 'active', '2026-04-23 05:42:20'),
(2, 'OD002', 'AICTE', 'aicte@gmail.com', '4561237894', NULL, NULL, '$2y$10$FOa6wMr1hs3HOJtC3vQl8.UCmtwqtfy5TT3yYElXzh5CYKS0JM36O', 'tp', 'active', '2026-04-23 06:36:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tp_id` (`tp_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `helpdesk_videos`
--
ALTER TABLE `helpdesk_videos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `placements`
--
ALTER TABLE `placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tp_id` (`tp_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tp_id` (`tp_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `tp_batches`
--
ALTER TABLE `tp_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tp_id` (`tp_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `center_id` (`center_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `helpdesk_videos`
--
ALTER TABLE `helpdesk_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `placements`
--
ALTER TABLE `placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tp_batches`
--
ALTER TABLE `tp_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `placements`
--
ALTER TABLE `placements`
  ADD CONSTRAINT `placements_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tp_batches`
--
ALTER TABLE `tp_batches`
  ADD CONSTRAINT `tp_batches_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tp_batches_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
