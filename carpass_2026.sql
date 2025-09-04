-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 04:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carpass_2026`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'FK to users.id (if action by user)',
  `admin_id` int(11) DEFAULT NULL COMMENT 'FK to admins.id (if action by admin)',
  `action` varchar(255) NOT NULL COMMENT 'ประเภทของกิจกรรมที่ทำ',
  `details` text DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม (JSON format)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP Address ของผู้ใช้งาน',
  `user_agent` text DEFAULT NULL COMMENT 'ข้อมูลอุปกรณ์ที่ใช้ (User Agent)',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Should be a hashed password',
  `title` varchar(100) NOT NULL COMMENT 'คำนำหน้า',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `position` varchar(255) DEFAULT NULL COMMENT 'ตำแหน่งงาน',
  `department` varchar(255) DEFAULT NULL COMMENT 'สังกัดของแอดมิน',
  `role` enum('superadmin','admin','viewer') NOT NULL DEFAULT 'admin' COMMENT 'ระดับสิทธิ์ (superadmin, admin, viewer)',
  `view_permission` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=ดูข้อมูลได้เฉพาะสังกัดตนเอง,1=ดูข้อมูลได้ทุกสังกัดทุกคน',
  `created_by` int(11) DEFAULT NULL COMMENT 'FK to admins.id referencing the creator of this admin account',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_brands`
--

CREATE TABLE `car_brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `display_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_key` varchar(20) NOT NULL COMMENT 'รหัสอ้างอิงผู้ใช้',
  `user_type` varchar(50) DEFAULT NULL COMMENT 'ประเภทผู้สมัคร (army, external)',
  `phone_number` varchar(15) NOT NULL COMMENT 'เบอร์โทรศัพท์',
  `national_id` varchar(20) NOT NULL COMMENT 'เลขบัตรประชาชน',
  `title` varchar(100) NOT NULL COMMENT 'คำนำหน้า',
  `firstname` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `lastname` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `dob` date DEFAULT NULL COMMENT 'วันเดือนปีเกิด',
  `gender` varchar(10) NOT NULL COMMENT 'เพศ',
  `address` text NOT NULL COMMENT 'ที่อยู่',
  `subdistrict` varchar(255) NOT NULL COMMENT 'ตำบล/แขวง',
  `district` varchar(255) NOT NULL COMMENT 'อำเภอ/เขต',
  `province` varchar(255) NOT NULL COMMENT 'จังหวัด',
  `zipcode` varchar(5) NOT NULL COMMENT 'รหัสไปรษณีย์',
  `photo_profile` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปถ่ายหน้าตรง',
  `work_department` varchar(255) DEFAULT NULL COMMENT 'หน่วยต้นสังกัด (สำหรับข้าราชการ)',
  `position` varchar(255) DEFAULT NULL COMMENT 'ตำแหน่ง (สำหรับข้าราชการ)',
  `official_id` varchar(10) DEFAULT NULL COMMENT 'เลขบัตรข้าราชการ (สำหรับข้าราชการ)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_requests`
--

CREATE TABLE `vehicle_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK อ้างอิง ID จากตาราง users',
  `request_key` varchar(20) NOT NULL COMMENT 'รหัสอ้างอิงคำร้องที่ไม่ซ้ำกัน',
  `search_id` varchar(20) DEFAULT NULL COMMENT 'รหัสสำหรับค้นหาและอ้างอิง (C/MYYMMDD-NNN)',
  `card_type` enum('internal','external') DEFAULT NULL COMMENT 'ประเภทบัตรผ่าน (internal = ภายใน, external = ภายนอก)',
  `vehicle_type` enum('รถยนต์','รถจักรยานยนต์') NOT NULL COMMENT 'ประเภทรถ',
  `brand` varchar(100) NOT NULL COMMENT 'ยี่ห้อรถ',
  `model` varchar(100) NOT NULL COMMENT 'รุ่นรถ',
  `color` varchar(50) NOT NULL COMMENT 'สีรถ',
  `license_plate` varchar(50) NOT NULL COMMENT 'เลขทะเบียนรถ',
  `province` varchar(100) NOT NULL COMMENT 'จังหวัด',
  `tax_expiry_date` date NOT NULL COMMENT 'วันสิ้นอายุภาษี',
  `owner_type` enum('self','other') NOT NULL COMMENT 'ความเป็นเจ้าของรถ (self = ตนเอง, other = ผู้อื่น)',
  `other_owner_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อเจ้าของรถ (กรณีเป็นรถผู้อื่น)',
  `other_owner_relation` varchar(100) DEFAULT NULL COMMENT 'ความเกี่ยวข้อง (กรณีเป็นรถผู้อื่น)',
  `photo_reg_copy` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปสำเนาทะเบียนรถ',
  `photo_tax_sticker` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปป้ายภาษี',
  `photo_front` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปถ่ายรถด้านหน้า',
  `photo_rear` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปถ่ายรถด้านหลัง',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT 'สถานะคำร้อง',
  `rejection_reason` text DEFAULT NULL COMMENT 'เหตุผลที่ไม่ผ่านการอนุมัติ',
  `approved_by_id` int(11) DEFAULT NULL COMMENT 'FK อ้างอิง ID แอดมินที่อนุมัติ จากตาราง admins',
  `approved_at` datetime DEFAULT NULL COMMENT 'วันเวลาที่อนุมัติ',
  `card_number` varchar(10) DEFAULT NULL COMMENT 'เลขที่บัตรผ่าน (4 หลัก)',
  `card_expiry_year` varchar(4) DEFAULT NULL COMMENT 'หมดอายุสิ้นปี (พ.ศ.)',
  `card_pickup_date` date DEFAULT NULL COMMENT 'วันที่คาดว่าจะได้รับบัตร',
  `card_pickup_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'สถานะการรับบัตร (0 = ยังไม่ได้รับ, 1 = รับแล้ว)',
  `card_pickup_by_admin_id` int(11) DEFAULT NULL COMMENT 'FK อ้างอิง ID แอดมินที่มอบบัตร',
  `card_pickup_at` datetime DEFAULT NULL COMMENT 'วันเวลาที่มอบบัตร',
  `edit_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'สถานะการแก้ไข (0 = ยังไม่เคยแก้ไข, 1 = แก้ไขแล้ว)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `car_brands`
--
ALTER TABLE `car_brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_key` (`user_key`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- Indexes for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_key` (`request_key`),
  ADD UNIQUE KEY `search_id` (`search_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by_id` (`approved_by_id`),
  ADD KEY `card_pickup_by_admin_id` (`card_pickup_by_admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_brands`
--
ALTER TABLE `car_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  ADD CONSTRAINT `vehicle_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vehicle_requests_ibfk_2` FOREIGN KEY (`approved_by_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `vehicle_requests_ibfk_3` FOREIGN KEY (`card_pickup_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
