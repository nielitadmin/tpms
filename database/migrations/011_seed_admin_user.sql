-- Migration: 011
-- Description: Seed default admin user
-- Date: 2026-04-28
-- Note: Password is bcrypt hashed. Change immediately after first login.

INSERT INTO `users` (`center_id`, `name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('ADMIN001', 'System Admin', 'admin@nielitbhubaneswar.in', '0000000000', '$2y$10$BLAHQ1xu1rOB83yBdd5ITeB1eFTn6makMN6yS4cUEReoEFBOj3s52', 'admin', 'active');
