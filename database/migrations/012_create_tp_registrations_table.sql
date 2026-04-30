-- Migration: 012
-- Description: Create tp_registrations table for detailed TP application data
-- Date: 2026-04-29

CREATE TABLE `tp_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(20) NOT NULL UNIQUE,
  `user_id` int(11) DEFAULT NULL,

  -- Section 1: Institute Details
  `institute_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `landline` varchar(15) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,

  -- Section 3: Authorized Signatory
  `signatory_name` varchar(100) NOT NULL,
  `signatory_father_name` varchar(100) NOT NULL,
  `signatory_designation` varchar(100) NOT NULL,
  `signatory_qualification` varchar(100) DEFAULT NULL,
  `signatory_experience` int(11) DEFAULT NULL,
  `signatory_id_type` varchar(50) NOT NULL,
  `signatory_id_number` varchar(50) NOT NULL,
  `signatory_address` text NOT NULL,
  `signatory_id_proof` varchar(255) DEFAULT NULL,
  `signatory_signature` varchar(255) DEFAULT NULL,

  -- Section 4: Premises & Infrastructure
  `premises_type` enum('Owned','Rented','Long Term Lease') NOT NULL,
  `carpet_area` int(11) NOT NULL,
  `computers` int(11) NOT NULL,
  `seating_capacity` int(11) NOT NULL,
  `internet_type` varchar(50) DEFAULT NULL,
  `layout_map` varchar(255) DEFAULT NULL,
  `building_photo` varchar(255) DEFAULT NULL,
  `premises_agreement` varchar(255) DEFAULT NULL,

  -- Section 9: Legal Status
  `legal_status` enum('1','2','3','4','5') NOT NULL COMMENT '1=Proprietorship, 2=Partnership, 3=Society/Trust, 4=Company, 5=Govt/PSU',
  -- Legal details (conditional based on legal_status)
  `prop_name` varchar(100) DEFAULT NULL,
  `gst_trade_licence` varchar(255) DEFAULT NULL,
  `partnership_date` date DEFAULT NULL,
  `partnership_reg_no` varchar(50) DEFAULT NULL,
  `partnership_deed` varchar(255) DEFAULT NULL,
  `society_reg_no` varchar(50) DEFAULT NULL,
  `society_reg_date` date DEFAULT NULL,
  `society_reg_cert` varchar(255) DEFAULT NULL,
  `society_moa` varchar(255) DEFAULT NULL,
  `cin_number` varchar(50) DEFAULT NULL,
  `incorporation_date` date DEFAULT NULL,
  `incorporation_cert` varchar(255) DEFAULT NULL,
  `company_moa` varchar(255) DEFAULT NULL,
  `dept_name` varchar(100) DEFAULT NULL,
  `govt_auth_letter` varchar(255) DEFAULT NULL,

  -- Section 12: Faculty Details
  `faculty1_name` varchar(100) NOT NULL,
  `faculty1_qualification` varchar(100) NOT NULL,
  `faculty1_exam` varchar(100) DEFAULT NULL,
  `faculty1_year` year(4) DEFAULT NULL,
  `faculty1_board` varchar(100) DEFAULT NULL,
  `faculty1_cert` varchar(255) DEFAULT NULL,

  `faculty2_name` varchar(100) DEFAULT NULL,
  `faculty2_qualification` varchar(100) DEFAULT NULL,
  `faculty2_exam` varchar(100) DEFAULT NULL,
  `faculty2_year` year(4) DEFAULT NULL,
  `faculty2_board` varchar(100) DEFAULT NULL,
  `faculty2_cert` varchar(255) DEFAULT NULL,

  -- Section 13: Experience Details
  `faculty1_exp_from` date DEFAULT NULL,
  `faculty1_exp_to` date DEFAULT NULL,
  `faculty1_exp_org` varchar(100) DEFAULT NULL,
  `faculty1_exp_desig` varchar(100) DEFAULT NULL,
  `faculty1_exp_id` varchar(50) DEFAULT NULL,

  `faculty2_exp_from` date DEFAULT NULL,
  `faculty2_exp_to` date DEFAULT NULL,
  `faculty2_exp_org` varchar(100) DEFAULT NULL,
  `faculty2_exp_desig` varchar(100) DEFAULT NULL,
  `faculty2_exp_id` varchar(50) DEFAULT NULL,

  -- Section 14: Financial & Placement
  `financial_year` varchar(10) NOT NULL,
  `turnover_it` decimal(15,2) DEFAULT NULL,
  `turnover_other` decimal(15,2) DEFAULT NULL,
  `tax_exempted` enum('Yes','No') DEFAULT NULL,
  `students_trained` int(11) DEFAULT NULL,
  `students_placed` int(11) DEFAULT NULL,

  -- Section 17: Document Uploads
  `doc_id_proof` varchar(255) DEFAULT NULL,
  `doc_signatory_sig` varchar(255) DEFAULT NULL,
  `doc_layout_map` varchar(255) DEFAULT NULL,
  `doc_reg_cert` varchar(255) DEFAULT NULL,
  `doc_franchise_agmt` varchar(255) DEFAULT NULL,
  `doc_registrar_reg` varchar(255) DEFAULT NULL,
  `doc_tax_reg` varchar(255) DEFAULT NULL,
  `doc_lease_deed` varchar(255) DEFAULT NULL,
  `doc_other` varchar(255) DEFAULT NULL,
  `doc_building_photos` varchar(255) DEFAULT NULL,

  -- Meta
  `declaration_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','submitted','under_review','approved','rejected') DEFAULT 'submitted',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  CONSTRAINT `fk_tp_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;