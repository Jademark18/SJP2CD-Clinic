-- =============================================
-- ST. JOHN PAUL II COLLEGE OF DAVAO
-- School Clinic Management System Database
-- =============================================
CREATE DATABASE IF NOT EXISTS `clinic_db`;
USE `clinic_db`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `school_id` VARCHAR(8) DEFAULT NULL UNIQUE,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','nurse') NOT NULL DEFAULT 'nurse',
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(150) NOT NULL,
  `age` INT NOT NULL,
  `gender` ENUM('Male','Female','Other') NOT NULL,
  `course` VARCHAR(150) DEFAULT NULL,
  `year_level` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `emergency_contact_name` VARCHAR(150) DEFAULT NULL,
  `emergency_contact_number` VARCHAR(20) DEFAULT NULL,
  `blood_type` VARCHAR(10) DEFAULT NULL,
  `allergies` TEXT DEFAULT NULL,
  `status` ENUM('active','archived') DEFAULT 'active',
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `visit_date` DATE NOT NULL,
  `chief_complaint` TEXT NOT NULL,
  `diagnosis` TEXT DEFAULT NULL,
  `treatment` TEXT DEFAULT NULL,
  `medicines_given` TEXT DEFAULT NULL,
  `blood_pressure` VARCHAR(20) DEFAULT NULL,
  `temperature` VARCHAR(20) DEFAULT NULL,
  `pulse_rate` VARCHAR(20) DEFAULT NULL,
  `weight` VARCHAR(20) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `attended_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`attended_by`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medicine_name` VARCHAR(200) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(50) DEFAULT 'pcs',
  `expiry_date` DATE DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('available','low','out_of_stock','expired') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `medicine_usage` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medicine_id` INT NOT NULL,
  `patient_id` INT DEFAULT NULL,
  `quantity_used` INT NOT NULL,
  `used_by` INT NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`),
  FOREIGN KEY (`used_by`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `student_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_number` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `patient_id` INT DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `nurse_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nurse_name` VARCHAR(150) NOT NULL,
  `available_date` DATE NOT NULL,
  `available_time` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('available','unavailable') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Admin: login via /admin/login.php only (no school_id needed)
-- Default password for both: password (bcrypt)
INSERT IGNORE INTO `users` (`school_id`,`full_name`,`email`,`username`,`password`,`role`) VALUES
(NULL,'System Administrator','admin@sjpiicd.edu.ph','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin'),
(NULL,'System Administrator 2','admin2@sjpiicd.edu.ph','admin2','$2y$10$ZM4Yxjc64N6iW85OFnbM8ek0SUyNUEYm768/oNNU3ovkSahItkPbC','admin'),
('20240001','School Nurse','nurse@sjpiicd.edu.ph','nurse','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','nurse');

INSERT IGNORE INTO `student_accounts` (`student_number`,`full_name`,`email`,`password`,`status`) VALUES
('20231001','Juan dela Cruz','juan@sjpiicd.edu.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','active'),
('20231002','Maria Santos','maria@sjpiicd.edu.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','active'),
('20231003','Pedro Garcia','pedro@sjpiicd.edu.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','active');

INSERT IGNORE INTO `nurse_availability` (`user_id`,`nurse_name`,`available_date`,`available_time`,`status`) VALUES
(2,'School Nurse','2026-05-15','08:00 AM - 12:00 PM','available'),
(2,'School Nurse','2026-05-16','01:00 PM - 05:00 PM','available'),
(2,'School Nurse','2026-05-17','08:00 AM - 12:00 PM','available');

INSERT IGNORE INTO `medicines` (`medicine_name`,`category`,`quantity`,`unit`,`expiry_date`,`status`) VALUES
('Paracetamol 500mg','Analgesic',100,'tablets','2026-12-31','available'),
('Amoxicillin 500mg','Antibiotic',50,'capsules','2026-06-30','available'),
('Ibuprofen 200mg','NSAID',80,'tablets','2026-10-31','available'),
('Cetirizine 10mg','Antihistamine',30,'tablets','2026-08-31','available'),
('Antacid Tablet','GI Medicine',60,'tablets','2026-11-30','available'),
('Betadine Solution','Antiseptic',10,'bottles','2026-09-30','available'),
('Oral Rehydration Salts','Electrolyte',20,'sachets','2026-12-31','available'),
('Vitamin C 500mg','Supplement',5,'tablets','2025-06-30','low');
