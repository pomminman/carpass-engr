-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2025 at 04:25 AM
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
  `photo_profile` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปโปรไฟล์ของแอดมิน',
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

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `photo_profile`, `username`, `password`, `title`, `firstname`, `lastname`, `phone_number`, `position`, `department`, `role`, `view_permission`, `created_by`, `created_at`) VALUES
(1, '', 'pommin_in', '$2y$10$yJPG22jldUfy4wU/toj72e/wWnmrUOCR6/NW/Ea9uBnwCMywVfLAy', 'ร.ท.', 'พรหมินทร์', 'อินทมาตย์', '0875692155', 'น.ควบคุมข้อมูล', 'กยข.กช.', 'superadmin', 1, NULL, '2025-09-03 16:22:12'),
(2, '', 'admin01', '$2y$10$abAcYaAf7nRlGwUl.26gA.tkR3VpzlRvRpNQW2U1J9USGUYr6tybu', 'ร.ต', 'สมชาติ', 'ดีใจ', NULL, NULL, 'กยข.กช.', 'admin', 0, 1, '2025-09-22 06:46:28'),
(3, '', 'admin02', '$2y$10$yW.zNnkqzFHjCSuCLn3f7ekk/WRENgL/BB4oP.OXAm1sLKTaL1LmK', 'หกด', 'หกด', 'หกด', NULL, NULL, 'กยข.กช.', 'viewer', 0, 1, '2025-09-22 14:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `application_periods`
--

CREATE TABLE `application_periods` (
  `id` int(11) NOT NULL,
  `period_name` varchar(255) NOT NULL COMMENT 'ชื่อรอบการสมัคร เช่น รอบปี 2569',
  `start_date` date NOT NULL COMMENT 'วันที่เริ่มเปิดรับสมัคร',
  `end_date` date NOT NULL COMMENT 'วันที่สิ้นสุดการรับสมัคร',
  `card_expiry_date` date NOT NULL COMMENT 'วันที่บัตรหมดอายุสำหรับรอบนี้',
  `is_active` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = ไม่ใช้งาน, 1 = เปิดใช้งานอยู่'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `application_periods`
--

INSERT INTO `application_periods` (`id`, `period_name`, `start_date`, `end_date`, `card_expiry_date`, `is_active`) VALUES
(1, 'รอบปี 2569', '2024-10-01', '2026-10-01', '2026-12-31', 1),
(2, 'รอบปี 2570', '2026-10-01', '2027-10-01', '2027-12-31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `approved_user_data`
--

CREATE TABLE `approved_user_data` (
  `id` int(11) NOT NULL COMMENT 'ID หลักของตาราง (Primary Key)',
  `request_id` int(11) NOT NULL COMMENT 'FK อ้างอิงถึง id ของคำร้องในตาราง vehicle_requests',
  `original_user_id` int(11) NOT NULL COMMENT 'FK อ้างอิงถึง id เดิมของผู้ใช้ในตาราง users',
  `user_type` varchar(50) DEFAULT NULL COMMENT 'ประเภทผู้สมัคร (army, external) ณ เวลาที่อนุมัติ',
  `phone_number` varchar(15) NOT NULL COMMENT 'เบอร์โทรศัพท์ ณ เวลาที่อนุมัติ',
  `national_id` varchar(20) NOT NULL COMMENT 'เลขบัตรประชาชน ณ เวลาที่อนุมัติ',
  `title` varchar(100) NOT NULL COMMENT 'คำนำหน้าชื่อ ณ เวลาที่อนุมัติ',
  `firstname` varchar(255) NOT NULL COMMENT 'ชื่อจริง ณ เวลาที่อนุมัติ',
  `lastname` varchar(255) NOT NULL COMMENT 'นามสกุล ณ เวลาที่อนุมัติ',
  `dob` date DEFAULT NULL COMMENT 'วันเดือนปีเกิด ณ เวลาที่อนุมัติ',
  `gender` varchar(10) NOT NULL COMMENT 'เพศ ณ เวลาที่อนุมัติ',
  `address` text NOT NULL COMMENT 'ที่อยู่ ณ เวลาที่อนุมัติ',
  `subdistrict` varchar(255) NOT NULL COMMENT 'ตำบล/แขวง ณ เวลาที่อนุมัติ',
  `district` varchar(255) NOT NULL COMMENT 'อำเภอ/เขต ณ เวลาที่อนุมัติ',
  `province` varchar(255) NOT NULL COMMENT 'จังหวัด ณ เวลาที่อนุมัติ',
  `zipcode` varchar(5) NOT NULL COMMENT 'รหัสไปรษณีย์ ณ เวลาที่อนุมัติ',
  `photo_profile` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปโปรไฟล์ ณ เวลาที่อนุมัติ',
  `work_department` varchar(255) DEFAULT NULL COMMENT 'หน่วยงาน/สังกัด ณ เวลาที่อนุมัติ (ถ้ามี)',
  `position` varchar(255) DEFAULT NULL COMMENT 'ตำแหน่ง ณ เวลาที่อนุมัติ (ถ้ามี)',
  `official_id` varchar(10) DEFAULT NULL COMMENT 'เลขบัตรข้าราชการ ณ เวลาที่อนุมัติ (ถ้ามี)',
  `snapshotted_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันและเวลาที่ข้อมูลนี้ถูกบันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ตารางเก็บสำเนาข้อมูลผู้ใช้ ณ เวลาที่คำร้องอนุมัติ';

-- --------------------------------------------------------

--
-- Table structure for table `car_brands`
--

CREATE TABLE `car_brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `car_brands`
--

INSERT INTO `car_brands` (`id`, `name`, `display_order`) VALUES
(1, 'ALFA ROMEO', 1),
(2, 'ALPHA VOLANTIS', 2),
(3, 'APIRILIA', 3),
(4, 'ASTON MARTIN', 4),
(5, 'AUDI', 5),
(6, 'AVATR', 6),
(7, 'BENELLI', 7),
(8, 'BENTLEY', 8),
(9, 'BMW', 9),
(10, 'BYD', 10),
(11, 'CHANGAN', 11),
(12, 'CHERY', 12),
(13, 'CHEVROLET', 13),
(14, 'CITROEN', 14),
(15, 'CYCLONE', 15),
(16, 'DAEWOO', 16),
(17, 'DAIHATSU', 17),
(18, 'DECO', 18),
(19, 'DEEPAL', 19),
(20, 'DODGE', 20),
(21, 'DONGFENG', 21),
(22, 'DUCATI', 22),
(23, 'EM', 23),
(24, 'FERRARI', 24),
(25, 'FIAT', 25),
(26, 'FORD', 26),
(27, 'FOTON', 27),
(28, 'GAC', 28),
(29, 'GAC AION', 29),
(30, 'GPX', 30),
(31, 'GWM', 31),
(32, 'HARLEY-DAVIDSON', 32),
(33, 'HAVAL', 33),
(34, 'H SEM', 34),
(35, 'HINO', 35),
(36, 'HONDA', 36),
(37, 'HUMMER', 37),
(38, 'HUSQVARNA', 38),
(39, 'HYUNDAI', 39),
(40, 'INDIAN MOTORCYCLE', 40),
(41, 'INFINITI', 41),
(42, 'ISUZU', 42),
(43, 'JAC', 43),
(44, 'JEEP', 44),
(45, 'JAECOO', 45),
(46, 'KAWASAKI', 46),
(47, 'KEEWAY', 47),
(48, 'KIA', 48),
(49, 'KTM', 49),
(50, 'LAMBRETTA', 50),
(51, 'LAMBORGHINI', 51),
(52, 'LAND ROVER', 52),
(53, 'LEAPMOTOR', 53),
(54, 'LEXUS', 54),
(55, 'LION EV', 55),
(56, 'LOTUS', 56),
(57, 'MAN', 57),
(58, 'MASERATI', 58),
(59, 'MAYBACH', 59),
(60, 'MAZDA', 60),
(61, 'MCLAREN', 61),
(62, 'MERCEDES-BENZ', 62),
(63, 'MG', 63),
(64, 'MINI', 64),
(65, 'MITSUBISHI', 65),
(66, 'NETA', 66),
(67, 'NISSAN', 67),
(68, 'OBAIC', 68),
(69, 'OMODA', 69),
(70, 'OPEL', 70),
(71, 'ORA', 71),
(72, 'PEUGEOT', 72),
(73, 'PLATINUM', 73),
(74, 'PORSCHE', 74),
(75, 'PROTON', 75),
(76, 'RAM', 76),
(77, 'RENAULT', 77),
(78, 'ROLLS-ROYCE', 78),
(79, 'ROVER', 79),
(80, 'ROYAL ALLOY', 80),
(81, 'ROYAL ENFIELD', 81),
(82, 'RYUKA', 82),
(83, 'SAAB', 83),
(84, 'SANGYONG', 84),
(85, 'STALLIONS', 85),
(86, 'STROM', 86),
(87, 'SUBARU', 87),
(88, 'SUZUKI', 88),
(89, 'SYM', 89),
(90, 'TATA', 90),
(91, 'TESLA', 91),
(92, 'THAIRUNG', 92),
(93, 'TIGER', 93),
(94, 'TOYOTA', 94),
(95, 'TRIUMPH', 95),
(96, 'VESPA', 96),
(97, 'VOLKSWAGEN', 97),
(98, 'VOLVO', 98),
(99, 'WILLYS', 99),
(100, 'WULING', 100),
(101, 'XPENG', 101),
(102, 'YAMAHA', 102),
(103, 'ZEEKR', 103),
(104, 'ZONTES', 104);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `display_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `display_order`) VALUES
(1, 'สง.จก.กช.', 1),
(2, 'สง.รอง จก.กช.(1)', 2),
(3, 'สง.รอง จก.กช.(2)', 3),
(4, 'สง.เสธ.กช.', 4),
(5, 'สง.รอง เสธ.กช.(1)', 5),
(6, 'สง.รอง เสธ.กช.(2)', 6),
(7, 'กกพ.กช.', 7),
(8, 'กยข.กช.', 8),
(9, 'กกบ.กช.', 9),
(10, 'กกร.กช.', 10),
(11, 'กปช.กช.', 11),
(12, 'กกส.กช.', 12),
(13, 'กคช.กช.', 13),
(14, 'กวก.กช.', 14),
(15, 'กจห.กช.', 15),
(16, 'กบร.กช.', 16),
(17, 'กชฝ.กช.', 17),
(18, 'กอ.กส.กช.', 18),
(19, 'ผธก.กช.', 19),
(20, 'ผกง.กช.', 20),
(21, 'รร.ช.กช.', 21),
(22, 'พัน.นร.รร.ช.กช.', 22),
(23, 'พัน.บร.กบร.กช.', 23),
(24, 'ช.21', 24),
(25, 'ร้อย.ช.ซบร.หนัก', 25),
(26, 'ร้อย.ช.ซบร.สนาม', 26),
(27, 'ที่ปรึกษา กช.', 27),
(28, 'นปก.กช.', 28),
(29, 'ฝ่ายกิจการพิเศษ กช.', 29),
(30, 'ศูนย์กีฬา กช.', 30),
(31, 'รร.โยธินวิทยา', 31),
(32, 'สหกรณ์ออมทรัพย์ค่ายภาณุรังษี', 32),
(33, 'ร้านค้าสหกรณ์ค่ายภาณุรังษี', 33),
(34, 'มทบ.16', 34),
(35, 'รพ.ค่ายภาณุรังษี', 35),
(36, 'บก.พล.ช.', 36),
(37, 'ช.11', 37),
(38, 'ช.11 พัน.111', 38),
(39, 'ช.11 พัน.602', 39),
(40, 'พัน.ช.คมศ.พล.ช.', 40),
(41, 'ช.ร้อย.14', 41),
(42, 'ช.ร้อย.18', 42),
(43, 'ช.ร้อย.115', 43),
(44, 'ช.พัน.51', 44),
(45, 'ตอน ช.93', 45),
(46, 'พัน.ช.กช.ร้อย.1', 46),
(47, 'ช.1', 47),
(48, 'ช.1 พัน.52', 48),
(49, 'ช.1 พัน.112', 49),
(50, 'พล.พัฒนา.1', 50);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_key` varchar(20) NOT NULL COMMENT 'รหัสอ้างอิงผู้ใช้',
  `user_type` varchar(50) DEFAULT NULL COMMENT 'ประเภทผู้สมัคร (army, external)',
  `phone_number` varchar(15) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `national_id` varchar(20) DEFAULT NULL COMMENT 'เลขบัตรประชาชน',
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
  `photo_profile_thumb` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปถ่ายหน้าตรง (thumbnail)',
  `work_department` varchar(255) DEFAULT NULL COMMENT 'หน่วยต้นสังกัด (สำหรับข้าราชการ)',
  `position` varchar(255) DEFAULT NULL COMMENT 'ตำแหน่ง (สำหรับข้าราชการ)',
  `official_id` varchar(10) DEFAULT NULL COMMENT 'เลขบัตรข้าราชการ (สำหรับข้าราชการ)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_admin_id` int(11) DEFAULT NULL COMMENT 'FK to admins.id if created by an admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL COMMENT 'ID หลักของตารางยานพาหนะ (Primary Key)',
  `user_id` int(11) NOT NULL COMMENT 'FK อ้างอิงถึงผู้ใช้ที่เป็นเจ้าของข้อมูลรถคันนี้ (Foreign Key to users.id)',
  `license_plate` varchar(50) NOT NULL COMMENT 'เลขทะเบียนรถ',
  `province` varchar(100) NOT NULL COMMENT 'จังหวัดของทะเบียนรถ',
  `vehicle_type` enum('รถยนต์','รถจักรยานยนต์') NOT NULL COMMENT 'ประเภทของยานพาหนะ',
  `brand` varchar(100) NOT NULL COMMENT 'ยี่ห้อรถ',
  `model` varchar(100) NOT NULL COMMENT 'รุ่นรถ',
  `color` varchar(50) NOT NULL COMMENT 'สีรถ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันและเวลาที่สร้างข้อมูล',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันและเวลาที่มีการอัปเดตข้อมูลล่าสุด',
  `created_by_admin_id` int(11) DEFAULT NULL COMMENT 'FK to admins.id if created by an admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ตารางสำหรับเก็บข้อมูลหลักของยานพาหนะแต่ละคัน';

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_requests`
--

CREATE TABLE `vehicle_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK อ้างอิง ID จากตาราง users',
  `vehicle_id` int(11) NOT NULL COMMENT 'FK อ้างอิงถึง ID ของรถในตาราง vehicles',
  `period_id` int(11) DEFAULT NULL COMMENT 'FK to application_periods.id',
  `request_key` varchar(20) NOT NULL COMMENT 'รหัสอ้างอิงคำร้องที่ไม่ซ้ำกัน',
  `search_id` varchar(20) DEFAULT NULL COMMENT 'รหัสสำหรับค้นหาและอ้างอิง (C/MYYMMDD-NNN)',
  `card_type` enum('internal','external') DEFAULT NULL COMMENT 'ประเภทบัตรผ่าน (internal = ภายใน, external = ภายนอก)',
  `qr_code_path` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์ QR Code',
  `tax_expiry_date` date DEFAULT NULL COMMENT 'วันสิ้นอายุภาษี',
  `owner_type` enum('self','other') NOT NULL COMMENT 'ความเป็นเจ้าของรถ (self = ตนเอง, other = ผู้อื่น)',
  `other_owner_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อเจ้าของรถ (กรณีเป็นรถผู้อื่น)',
  `other_owner_relation` varchar(100) DEFAULT NULL COMMENT 'ความเกี่ยวข้อง (กรณีเป็นรถผู้อื่น)',
  `photo_reg_copy` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปสำเนาทะเบียนรถ',
  `photo_tax_sticker` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปป้ายภาษี',
  `photo_front` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปถ่ายรถด้านหน้า',
  `photo_rear` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์รูปถ่ายรถด้านหลัง',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT 'สถานะคำร้อง',
  `rejection_reason` text DEFAULT NULL COMMENT 'เหตุผลที่ไม่ผ่านการอนุมัติ',
  `approved_by_id` int(11) DEFAULT NULL COMMENT 'FK อ้างอิง ID แอดมินที่อนุมัติ จากตาราง admins',
  `approved_at` datetime DEFAULT NULL COMMENT 'วันเวลาที่อนุมัติ',
  `card_number` varchar(10) DEFAULT NULL COMMENT 'เลขที่บัตรผ่าน (4 หลัก)',
  `card_expiry` date DEFAULT NULL COMMENT 'วันที่บัตรหมดอายุ',
  `card_pickup_date` date DEFAULT NULL COMMENT 'วันที่คาดว่าจะได้รับบัตร',
  `card_pickup_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'สถานะการรับบัตร (0 = ยังไม่ได้รับ, 1 = รับแล้ว)',
  `card_pickup_by_admin_id` int(11) DEFAULT NULL COMMENT 'FK อ้างอิง ID แอดมินที่มอบบัตร',
  `card_pickup_at` datetime DEFAULT NULL COMMENT 'วันเวลาที่มอบบัตร',
  `edit_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'สถานะการแก้ไข (0 = ยังไม่เคยแก้ไข, 1 = แก้ไขแล้ว)',
  `created_by_admin_id` int(11) DEFAULT NULL COMMENT 'FK to admins.id if created by an admin',
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
-- Indexes for table `application_periods`
--
ALTER TABLE `application_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approved_user_data`
--
ALTER TABLE `approved_user_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`) COMMENT 'กำหนดให้ 1 คำร้องมีข้อมูล snapshot ได้เพียง 1 ชุด',
  ADD KEY `original_user_id` (`original_user_id`) COMMENT 'Index สำหรับการค้นหาข้อมูลจาก user id เดิม';

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
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `fk_users_to_admins_creator` (`created_by_admin_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_license_province` (`license_plate`,`province`) COMMENT 'ป้องกันการสร้างข้อมูลรถทะเบียนซ้ำในจังหวัดเดียวกัน',
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_vehicles_to_admins_creator` (`created_by_admin_id`);

--
-- Indexes for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_key` (`request_key`),
  ADD UNIQUE KEY `search_id` (`search_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `approved_by_id` (`approved_by_id`),
  ADD KEY `card_pickup_by_admin_id` (`card_pickup_by_admin_id`),
  ADD KEY `period_id` (`period_id`),
  ADD KEY `fk_requests_to_admins_creator` (`created_by_admin_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `application_periods`
--
ALTER TABLE `application_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `approved_user_data`
--
ALTER TABLE `approved_user_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID หลักของตาราง (Primary Key)';

--
-- AUTO_INCREMENT for table `car_brands`
--
ALTER TABLE `car_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID หลักของตารางยานพาหนะ (Primary Key)';

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
-- Constraints for table `approved_user_data`
--
ALTER TABLE `approved_user_data`
  ADD CONSTRAINT `fk_snapshot_to_requests` FOREIGN KEY (`request_id`) REFERENCES `vehicle_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_snapshot_to_users` FOREIGN KEY (`original_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_to_admins_creator` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicles_to_admins_creator` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  ADD CONSTRAINT `fk_requests_to_admins_approved` FOREIGN KEY (`approved_by_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_to_admins_creator` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_to_admins_pickup` FOREIGN KEY (`card_pickup_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_to_periods` FOREIGN KEY (`period_id`) REFERENCES `application_periods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_to_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_to_vehicles` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
