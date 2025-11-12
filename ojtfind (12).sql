-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 12, 2025 at 07:31 PM
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
-- Database: `ojtfind`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity-log`
--

CREATE TABLE `activity-log` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `activity_des` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity-log`
--

INSERT INTO `activity-log` (`log_id`, `student_id`, `company_id`, `activity_des`, `created_at`) VALUES
(2, 6, 3, 'Completed project setup and initial database schema design.', '2025-11-07 17:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `application_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `posting_id` int(11) NOT NULL,
  `status` varchar(150) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(50) NOT NULL,
  `industry` varchar(50) NOT NULL,
  `address` varchar(50) NOT NULL,
  `contact_person` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `description` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`company_id`, `user_id`, `company_name`, `industry`, `address`, `contact_person`, `email`, `phone_number`, `description`) VALUES
(1, 2, 'PNP', 'Marketing', 'Ward 2 Minglanilla Cebu', 'Mike Bustamante', 'generalgenx60@gmail.com', '12345678911', ''),
(2, 5, 'BDO', 'Marketing', 'Ward 3 Minglanilla Cebu', 'Larry Boss', 'zennia@gmail.com', '12345678911', ''),
(3, 9, 'Azzella Properties', 'Real Estate', 'Lipata Minglanilla Cebu', 'Dave Joseph Cruz', 'azzella@gmail.com', '09345674532', 'wow I love pizza'),
(4, 12, 'Julies BakeShop', 'Bakery', 'Ward 2 Minglanilla Cebu', 'Dina Ko De la Cruz', 'julies@gmail.com', '09123456789', ''),
(5, 13, 'Julies BakeShop', 'Bakery', 'Ward 2 Minglanilla Cebu', 'Dina Ko De la Cruz', 'julies@gmail.com', '09123456789', ''),
(6, 14, 'Chowking', 'Fast Food Restaurant', 'Lipata Minglanilla Cebu', 'Chowfan De la Fienta', 'chowchow@gmail.com', '09675454321', ''),
(7, 17, 'PNP', 'Police', 'Ward 2 Minglanilla Cebu', 'Mike Bustamante', 'pnp@gmail.com', '12345678911', 'Saludo Sayu Bawal Corrupt dito boss'),
(8, 19, 'Testing Company', 'Testing', 'Ward 2 Minglanilla Cebu', 'Chowfan De la Fienta', 'test2@gmail.com', '12345678901', ''),
(9, 21, 'Skillet', 'Band', 'California', 'Jen Ledger', 'skillet@gmail.com', '09675454321', '');

-- --------------------------------------------------------

--
-- Table structure for table `coordinator`
--

CREATE TABLE `coordinator` (
  `coordinator_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coordinator`
--

INSERT INTO `coordinator` (`coordinator_id`, `user_id`, `full_name`, `employee_id`, `position`, `department`, `email`, `contact_number`) VALUES
(1, 4, 'Maria Dela Cruz', '12345', 'Coordinator', 'BS in Information Technology', 'lloyd@gmail.com', '12345678911'),
(2, 10, 'Andry Gemao CruzZZ', '678910', 'OJT Coordinator', 'BS in Information Technology', 'andry@gmail.com', '09123456781');

-- --------------------------------------------------------

--
-- Table structure for table `coordinator_log`
--

CREATE TABLE `coordinator_log` (
  `log_id` int(11) NOT NULL,
  `actor_user_id` int(11) DEFAULT NULL COMMENT 'The user who initiated the action (FK to users.user_id)',
  `actor_role` varchar(50) NOT NULL COMMENT 'Role of the actor (student, coordinator, company, system)',
  `target_entity` varchar(50) NOT NULL COMMENT 'Type of entity involved (student, company, posting, requirement, user)',
  `target_id` int(11) DEFAULT NULL COMMENT 'Primary ID of the target entity (e.g., student_id, company_id)',
  `action_type` varchar(100) NOT NULL COMMENT 'Category of action (USER_REGISTER, POSTING_CREATED, REQ_APPROVED, etc.)',
  `description` text NOT NULL COMMENT 'Detailed, human-readable description of the activity',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coordinator_log`
--

INSERT INTO `coordinator_log` (`log_id`, `actor_user_id`, `actor_role`, `target_entity`, `target_id`, `action_type`, `description`, `created_at`) VALUES
(1, 1, 'student', 'student', 1, 'USER_REGISTER', 'New Student Account created: **Joseph Christian** (BSIT, 2nd Year).', '2025-11-12 16:08:36'),
(2, 9, 'company', 'company', 3, 'USER_REGISTER', 'New Company Account registered: **Azzella Properties** (Real Estate).', '2025-11-12 16:08:36'),
(3, 10, 'coordinator', 'coordinator', 2, 'USER_REGISTER', 'New Coordinator Account registered: **Andry Gemao CruzZZ** (BS in Information Technology).', '2025-11-12 16:08:36'),
(4, 9, 'company', 'posting', 3, 'POSTING_CREATED', 'Azzella Properties created a new posting: **FOR THE HR DEPARTMENT** (ID 3).', '2025-11-12 16:08:36'),
(5, 10, 'coordinator', 'requirement', 2, 'REQUIREMENT_APPROVED', 'Coordinator Andry approved the **Information** requirement for student Chris Arambala.', '2025-11-12 16:08:36'),
(6, 16, 'student', 'student', 8, 'USER_REGISTER', 'New Student Account created: **Christian Yongzon** (BSIT, 3rd Year).', '2025-11-12 16:08:36'),
(7, NULL, '', '', NULL, '', 'Student user **Chris Arambala** (Username: **thirdthird**) successfully signed in.', '2025-11-12 16:40:10'),
(8, NULL, '', '', NULL, '', 'A new student, **Kaloy Jeven** (Username: **kaloykaloy**), successfully registered.', '2025-11-12 16:44:06'),
(10, NULL, '', '', NULL, '', 'Company user **Azzella Properties** (Username: **azzella**) successfully signed in.', '2025-11-12 16:58:49'),
(11, NULL, '', '', NULL, '', 'A new student, **JEPJEP Abella** (Username: **jepjepjep**), successfully registered.', '2025-11-12 17:07:51'),
(12, NULL, '', '', NULL, '', 'Student user **JEPJEP Abella** (Username: **jepjepjep**) successfully signed in.', '2025-11-12 17:08:22'),
(13, NULL, '', '', NULL, '', 'A new company, **Skillet** (Username: **skilletskillet**), registered with status **Pending**.', '2025-11-12 17:10:24'),
(14, NULL, '', '', NULL, '', 'Coordinator **Andry Gemao CruzZZ** (Username: **andry123456**) successfully signed in.', '2025-11-12 17:17:05'),
(15, NULL, '', '', NULL, '', 'Student user **Chris Arambala** (Username: **thirdthird**) successfully signed in.', '2025-11-12 17:21:41'),
(16, NULL, '', '', NULL, '', 'Student user **Chris Arambala** (Username: **thirdthird**) successfully signed in.', '2025-11-12 17:40:14'),
(17, NULL, '', '', NULL, '', 'Student **Chris Arambala** (User: **thirdthird**) successfully **updated their personal profile** details.', '2025-11-12 17:41:30'),
(18, NULL, '', '', NULL, '', 'Student **Chris Arambala** (User: **thirdthird**) successfully **uploaded a requirement**: **Information** (File: **APPLICATION LETTER.docx**).', '2025-11-12 17:41:41'),
(19, NULL, '', '', NULL, '', 'Coordinator **Andry Gemao CruzZZ** (Username: **andry123456**) successfully signed in.', '2025-11-12 17:41:54'),
(20, NULL, '', '', NULL, '', 'Student user **Chris Arambala** (Username: **thirdthird**) successfully signed in.', '2025-11-12 17:42:29'),
(21, NULL, '', '', NULL, '', 'Student **Chris Arambala** (User: **thirdthird**) submitted a **Daily Log** for **2025-10-12** (12.0 hours).', '2025-11-12 18:00:25'),
(22, NULL, '', '', NULL, '', 'Coordinator **Andry Gemao CruzZZ** (Username: **andry123456**) successfully signed in.', '2025-11-12 18:00:45'),
(23, NULL, '', '', NULL, '', 'Student user **Chris Arambala** (Username: **thirdthird**) successfully signed in.', '2025-11-12 18:01:11'),
(24, NULL, '', '', NULL, '', 'Student **Chris Arambala** (User: **thirdthird**) submitted a **Daily Log** for **1212-11-12** (12.0 hours).', '2025-11-12 18:02:17'),
(25, NULL, '', '', NULL, '', 'Coordinator **Andry Gemao CruzZZ** (Username: **andry123456**) successfully signed in.', '2025-11-12 18:03:07'),
(26, NULL, '', '', NULL, '', 'Student user **Chris Arambala** (Username: **thirdthird**) successfully signed in.', '2025-11-12 18:06:34'),
(27, NULL, '', '', NULL, '', 'Student **Chris Arambala** (User: **thirdthird**) submitted a **Daily Log** for **2025-11-11** (1.0 hours).', '2025-11-12 18:12:11'),
(28, NULL, '', '', NULL, '', 'Company user **Azzella Properties** (Username: **azzella**) successfully signed in.', '2025-11-12 18:12:51'),
(29, NULL, '', '', NULL, '', 'Company **Azzella Properties** (User: **azzella**) submitted a new internship post **\\\'HARDWARE MAINTENANCE\\\'** for **2** slots. Status: **Pending Review**.', '2025-11-12 18:16:29'),
(30, NULL, '', '', NULL, '', 'Company **Azzella Properties** (User: **azzella**) submitted an **evaluation** for student **Chris Arambala**. Rating: **5/5**. Final Status set to **Completed**.', '2025-11-12 18:22:04'),
(31, NULL, '', '', NULL, '', 'Coordinator **Andry Gemao CruzZZ** (Username: **andry123456**) successfully signed in.', '2025-11-12 18:22:43'),
(32, NULL, '', '', NULL, '', 'Company user **Azzella Properties** (Username: **azzella**) successfully signed in.', '2025-11-12 18:23:12'),
(33, NULL, '', '', NULL, '', 'Company **Azzella Properties** (User: **azzella**) successfully updated its profile information. New Name: **Azzella Properties**.', '2025-11-12 18:28:50'),
(34, NULL, '', '', NULL, '', 'Coordinator **Andry Gemao CruzZZ** (Username: **andry123456**) successfully signed in.', '2025-11-12 18:29:25');

-- --------------------------------------------------------

--
-- Table structure for table `daily_log`
--

CREATE TABLE `daily_log` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `hours_logged` decimal(4,2) NOT NULL,
  `activities_performed` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by_company_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_log`
--

INSERT INTO `daily_log` (`log_id`, `student_id`, `application_id`, `log_date`, `hours_logged`, `activities_performed`, `status`, `submitted_at`, `approved_by_company_id`) VALUES
(1, 5, 5, '2025-02-22', 8.00, 'I\'ve did a lot of tasked today but I won\'t be saying them for now since this is just a test', 'Approved', '2025-11-08 18:38:42', 3),
(2, 7, 6, '2025-10-12', 12.00, 'I did everything as you said', 'Pending', '2025-11-12 18:00:25', NULL),
(3, 7, 6, '1212-11-12', 12.00, '121212', 'Pending', '2025-11-12 18:02:17', NULL),
(4, 7, 6, '2025-11-11', 1.00, '121212121', 'Pending', '2025-11-12 18:12:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

CREATE TABLE `evaluation` (
  `evaluation_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(10,0) NOT NULL,
  `remark` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation`
--

INSERT INTO `evaluation` (`evaluation_id`, `application_id`, `company_id`, `student_id`, `score`, `remark`, `submitted_at`) VALUES
(5, 3, 3, 6, 5, 'He is a very good student who does well on his job', '2025-11-08 15:38:05'),
(6, 6, 3, 7, 5, 'He\'s very good in the things that he do', '2025-11-12 18:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `intern_application`
--

CREATE TABLE `intern_application` (
  `application_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `posting_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Reviewed','Interview Scheduled','Hired','Rejected') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `intern_application`
--

INSERT INTO `intern_application` (`application_id`, `student_id`, `posting_id`, `application_date`, `status`) VALUES
(3, 6, 3, '2025-11-05 17:31:26', 'Hired'),
(4, 6, 4, '2025-11-08 16:30:57', 'Rejected'),
(5, 5, 3, '2025-11-08 18:34:55', 'Pending'),
(6, 7, 3, '2025-11-12 12:19:49', ''),
(7, 7, 1, '2025-11-12 17:59:17', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `intern_posting`
--

CREATE TABLE `intern_posting` (
  `posting_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `slot_available` int(11) NOT NULL,
  `create_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `intern_posting`
--

INSERT INTO `intern_posting` (`posting_id`, `company_id`, `title`, `description`, `requirements`, `slot_available`, `create_at`, `status`) VALUES
(1, 5, 'Julies Bakery Worker Employee', 'This is just a test to prove if it will work or not', 'Good Attitude, Good Communicating skills, and Good math skills', 2, '2025-11-01 17:42:19.934600', 'Active'),
(3, 3, 'FOR THE HR DEPARTMENT', 'Good with computers or technology in general', 'Be knowledgeable enough for doing the coding', 2, '2025-11-05 16:59:39.593591', 'Active'),
(4, 3, 'CONTRACTORS ASSISTANT IN BUILDING THE DECAS', 'Needing for assistance in creating the buildings on the lipata part', 'good in math and everything', 3, '2025-11-07 16:19:23.343439', 'Pending Review'),
(5, 3, 'HARDWARE MAINTENANCE', 'This job is hard but will create men', 'must be good in handling with computer parts', 2, '2025-11-12 18:16:28.781003', 'Pending Review');

-- --------------------------------------------------------

--
-- Table structure for table `intern_tasks`
--

CREATE TABLE `intern_tasks` (
  `task_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Awaiting Review','Completed','Canceled') DEFAULT 'Pending',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `intern_tasks`
--

INSERT INTO `intern_tasks` (`task_id`, `application_id`, `company_id`, `student_id`, `task_title`, `task_description`, `due_date`, `status`, `assigned_at`) VALUES
(1, 3, 3, 6, 'Set UP a Web Server for the HR Department', 'Make sure to have a proper knowledge before doing this project and don\'t be shy to try and ask your seniors!', '2222-02-22', 'Awaiting Review', '2025-11-08 15:54:06'),
(2, 5, 3, 5, 'I want you to make a big motherboard', 'Try asking for help', '2227-02-24', 'Pending', '2025-11-08 18:45:10');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `course` varchar(50) NOT NULL,
  `year_level` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `description` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `user_id`, `first_name`, `last_name`, `course`, `year_level`, `email`, `phone_number`, `description`, `status`) VALUES
(1, 1, 'Joseph', 'Christian', 'BSIT', '2nd Year', 'example@gmail.com', '09675432134', 'I love to cheer myself by telling how bad I am', 'Active'),
(2, 3, 'Larry', 'Bustamante', 'BSIT', '1st Year', 'larlar@gmail.com', '09675454532', 'I love food', 'Active'),
(3, 6, 'Joseph', 'Arambala', 'BS in Information Technology', '3rd Year', 'joseph@gmail.com', '09876543211', 'I love to code a lot', 'Active'),
(4, 7, 'Larry', 'Mike', 'BS in Criminology', '3rd Year', 'Mikeeey@gmail.com', '09765453321', 'I love to shoot guns', 'Active'),
(5, 8, 'Joseph', 'Anthony', 'BS in Information Technology', '3rd Year', 'anthony@gmail.com', '09878656745', 'I love to code a lot', 'Active'),
(6, 11, 'Lance', 'Mayormita', 'BS in Information Technology', '2nd Year', 'mrlance@gmail.com', '09765435721', 'I love to play games and have some fun with the co', 'Active'),
(7, 15, 'Chris', 'Arambala', 'BS in Business Administration', '3rd Year', 'third@gmail.com', '12345678901', 'I love to code a lottt', 'Active'),
(8, 16, 'Christian', 'Yongzon', 'BS in Information Technology', '3rd Year', 'chano@sample.com', '12345678901', 'I want to code this', 'Active'),
(9, 18, 'Kaloy', 'Jeven', 'BS in Criminology', '2nd Year', 'kaloy@gmail.com', '12345678901', 'hahaha', 'Active'),
(10, 20, 'JEPJEP', 'Abella', 'BS in Business Administration', '4th Year', 'jepjep@gmail.com', '09345674532', 'I love going to the gym', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `student_requirements`
--

CREATE TABLE `student_requirements` (
  `requirement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL COMMENT 'e.g., Resume, Waiver, Endorsement Letter, Academic Form',
  `file_path` varchar(255) NOT NULL COMMENT 'Server path or URL to the uploaded file',
  `file_name` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `coordinator_id` int(11) DEFAULT NULL COMMENT 'The coordinator who last reviewed the document',
  `review_date` timestamp NULL DEFAULT NULL COMMENT 'The date the document was last reviewed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_requirements`
--

INSERT INTO `student_requirements` (`requirement_id`, `student_id`, `document_type`, `file_path`, `file_name`, `upload_date`, `approval_status`, `coordinator_id`, `review_date`) VALUES
(1, 7, 'MOA', 'uploads/requirements/7_1762952246_MOA.docx', 'APPLICATION LETTER.docx', '2025-11-12 12:57:26', 'Approved', 2, '2025-11-12 13:19:36'),
(2, 7, 'Information', 'uploads/requirements/7_1762954256_Information.docx', 'APPLICATION LETTER.docx', '2025-11-12 13:30:56', 'Approved', 2, '2025-11-12 13:31:53'),
(3, 7, 'Information', 'uploads/requirements/7_1762969301_Information.docx', 'APPLICATION LETTER.docx', '2025-11-12 17:41:41', 'Pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `status`) VALUES
(1, 'joseph', '$2y$10$4Mz0eZd7FlU.75fx/jDdBuYrzHyb76LIyEIv8MQCPAJ', 'student', 'Active'),
(2, 'mike', '$2y$10$GMBBYfftm9VWdIsvq8z.9u6d8hNaEls6qGJzO8J5TYL', 'company', 'Active'),
(3, 'loyd', '$2y$10$fLXM2B.0cEc.d1WrxXYSKeCmAmxP0hQuQoebHkP3po4', 'student', 'Active'),
(4, 'mikeeyy', '$2y$10$t5GfKvgM52BtZjNpjqfra.o.I0xyiaKM6J5ymvbW2aG', 'coordinator', 'Active'),
(5, 'larry', '$2y$10$zu2K7N2LUXE0LMEClxYcjelyp1d4Mc5D0WGBQ8iolH6', 'company', 'Active'),
(6, 'sepsep', '$2y$10$Mvrhy4BlrZ4Fm8aGoqjIsOTTuoY8nu0UgNxNAVxolRV', 'student', 'Active'),
(7, 'larlarlar', '$2y$10$n.CPNM7kA.OpT5jeEb3Nw.QGmYzP6d6HznkbKhM3KXu', 'student', 'Active'),
(8, 'testsubject', '$2y$10$cZjcRiy8nTUorNV538OFhOTFSqiEN9sQ1Ebrwa1yT0rE/Szk5fe4a', 'student', 'Active'),
(9, 'azzella', '$2y$10$nNR1MhJkEld7ONwXggwzR.d.ShtoHgUJUR1ic1YcJ4ePcjHaOhRuy', 'company', 'Active'),
(10, 'andry123456', '$2y$10$LiyVug7Tv9WQmESEw6ZbauhoGeof/AJqM22scA9Zn040IrNo0T0Fe', 'coordinator', 'Active'),
(11, 'lancelance', '$2y$10$34uKePT68M1/t5nMibM7ROFuH5r88kPi.z0raeIFYj/7k/9ZG7cEG', 'student', 'Active'),
(12, 'juliesbake', '$2y$10$WuwRyxu/9auxFEdUJnVqEOVnn2f.7FvdLsgcegnBDRDNT1pMYAySq', 'company', 'Active'),
(13, 'juliesss', '$2y$10$JmT.nSdYPcgA6hjR8fPuW.j6epiVEaMRuis4FkHpdyczDJRyiEEmy', 'company', 'Active'),
(14, 'chowchow', '$2y$10$xnnARrcGh7sxC2Q9lhGKX.TbKESNhk8MemGDOXK9V881W/ILaRXK.', 'company', 'Pending'),
(15, 'thirdthird', '$2y$10$4JTTgdzCG8Q8HPbrK.JDW.CuHqKQi7cOMwOV/FlosTHAsR1MlxHwq', 'student', 'Active'),
(16, 'Chano', '$2y$10$xFeWzQXLBO7/bREXUoB2Huxw7q6JZ5T7YsUWh8PAQkQw4wXehPZD6', 'student', 'Active'),
(17, 'SenBato', '$2y$10$mqL5oUPn.CAKcEGvOlgvkeWnbhJkH9rRmI361FNDG8t95rmxX4PRy', 'company', 'Active'),
(18, 'kaloykaloy', '$2y$10$xA/lMRwQUZk2thtYhb2V1.XekK12mxTKZi.YM5Xe1q6FGmjpO4K0e', 'student', 'Active'),
(19, 'testtesttest', '$2y$10$WIpSwQh7q.06o9U1U9XLZurSupmjHIe2ftXQV6NJInKoEDsO/6dCa', 'company', 'Pending'),
(20, 'jepjepjep', '$2y$10$w8VmfNByWBxSxiGXxclhOe/ikxbBbJsekuq3NeEVIXuiBbVo99doa', 'student', 'Active'),
(21, 'skilletskillet', '$2y$10$KMa7eSoKqy1JnTgv0bAuLuLpX7ZZEHThBeJ/0CeWDXHMKjr.Zyjeq', 'company', 'Pending');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity-log`
--
ALTER TABLE `activity-log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `students_id` (`student_id`),
  ADD KEY `companies_id` (`company_id`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `stu_id` (`student_id`),
  ADD KEY `post` (`posting_id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`company_id`),
  ADD KEY `company_id` (`user_id`);

--
-- Indexes for table `coordinator`
--
ALTER TABLE `coordinator`
  ADD PRIMARY KEY (`coordinator_id`),
  ADD KEY `coordinate_id` (`user_id`);

--
-- Indexes for table `coordinator_log`
--
ALTER TABLE `coordinator_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_log_actor_user` (`actor_user_id`);

--
-- Indexes for table `daily_log`
--
ALTER TABLE `daily_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_log_student` (`student_id`),
  ADD KEY `fk_log_application` (`application_id`),
  ADD KEY `fk_log_company_approver` (`approved_by_company_id`);

--
-- Indexes for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `app_id` (`application_id`),
  ADD KEY `st_id` (`student_id`),
  ADD KEY `c_id` (`company_id`);

--
-- Indexes for table `intern_application`
--
ALTER TABLE `intern_application`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `posting_id` (`posting_id`);

--
-- Indexes for table `intern_posting`
--
ALTER TABLE `intern_posting`
  ADD PRIMARY KEY (`posting_id`),
  ADD KEY `comp_id` (`company_id`);

--
-- Indexes for table `intern_tasks`
--
ALTER TABLE `intern_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_task_application` (`application_id`),
  ADD KEY `fk_task_company` (`company_id`),
  ADD KEY `fk_task_student` (`student_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `student-users` (`user_id`);

--
-- Indexes for table `student_requirements`
--
ALTER TABLE `student_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `fk_req_coordinator` (`coordinator_id`),
  ADD KEY `idx_student_doc` (`student_id`,`document_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity-log`
--
ALTER TABLE `activity-log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `coordinator`
--
ALTER TABLE `coordinator`
  MODIFY `coordinator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coordinator_log`
--
ALTER TABLE `coordinator_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `daily_log`
--
ALTER TABLE `daily_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `intern_application`
--
ALTER TABLE `intern_application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `intern_posting`
--
ALTER TABLE `intern_posting`
  MODIFY `posting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `intern_tasks`
--
ALTER TABLE `intern_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_requirements`
--
ALTER TABLE `student_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity-log`
--
ALTER TABLE `activity-log`
  ADD CONSTRAINT `companies_id` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`),
  ADD CONSTRAINT `students_id` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `post` FOREIGN KEY (`posting_id`) REFERENCES `intern_posting` (`posting_id`),
  ADD CONSTRAINT `stu_id` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `company`
--
ALTER TABLE `company`
  ADD CONSTRAINT `company_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `coordinator`
--
ALTER TABLE `coordinator`
  ADD CONSTRAINT `coordinate_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `daily_log`
--
ALTER TABLE `daily_log`
  ADD CONSTRAINT `fk_log_application` FOREIGN KEY (`application_id`) REFERENCES `intern_application` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_company_approver` FOREIGN KEY (`approved_by_company_id`) REFERENCES `company` (`company_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD CONSTRAINT `c_id` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`),
  ADD CONSTRAINT `fk_intern_application_id` FOREIGN KEY (`application_id`) REFERENCES `intern_application` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `st_id` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `intern_application`
--
ALTER TABLE `intern_application`
  ADD CONSTRAINT `intern_application_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `intern_application_ibfk_2` FOREIGN KEY (`posting_id`) REFERENCES `intern_posting` (`posting_id`) ON DELETE CASCADE;

--
-- Constraints for table `intern_posting`
--
ALTER TABLE `intern_posting`
  ADD CONSTRAINT `comp_id` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`);

--
-- Constraints for table `intern_tasks`
--
ALTER TABLE `intern_tasks`
  ADD CONSTRAINT `fk_task_application` FOREIGN KEY (`application_id`) REFERENCES `intern_application` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student-users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `student_requirements`
--
ALTER TABLE `student_requirements`
  ADD CONSTRAINT `fk_req_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinator` (`coordinator_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_req_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
