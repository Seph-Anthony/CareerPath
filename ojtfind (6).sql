-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 03, 2025 at 05:30 PM
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
  `description` varchar(200) NOT NULL,
  `image_des` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`company_id`, `user_id`, `company_name`, `industry`, `address`, `contact_person`, `email`, `phone_number`, `description`, `image_des`) VALUES
(1, 2, 'PNP', 'Marketing', 'Ward 2 Minglanilla Cebu', 'Mike Bustamante', 'generalgenx60@gmail.com', '12345678911', '', ''),
(2, 5, 'BDO', 'Marketing', 'Ward 3 Minglanilla Cebu', 'Larry Boss', 'zennia@gmail.com', '12345678911', '', ''),
(3, 9, 'Azzella Properties', 'Real Estate', 'Lipata Minglanilla Cebu', 'Dave Joseph Cruz', 'azzella@gmail.com', '09345674532', '', ''),
(4, 12, 'Julies BakeShop', 'Bakery', 'Ward 2 Minglanilla Cebu', 'Dina Ko De la Cruz', 'julies@gmail.com', '09123456789', '', ''),
(5, 13, 'Julies BakeShop', 'Bakery', 'Ward 2 Minglanilla Cebu', 'Dina Ko De la Cruz', 'julies@gmail.com', '09123456789', '', ''),
(6, 14, 'Chowking', 'Fast Food Restaurant', 'Lipata Minglanilla Cebu', 'Chowfan De la Fienta', 'chowchow@gmail.com', '09675454321', '', '');

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
(2, 10, 'Andry Gemao Cruz', '678910', 'OJT Coordinator', 'BS in Information Technology', 'andry@gmail.com', '09123456781');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

CREATE TABLE `evaluation` (
  `evaluation_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `coordinator_id` int(11) NOT NULL,
  `score` decimal(10,0) NOT NULL,
  `remark` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 6, 2, '2025-11-02 17:09:29', 'Reviewed'),
(2, 5, 2, '2025-11-03 14:39:51', 'Reviewed');

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
(2, 3, 'OJT Employees to Handle the computer processing of the document', 'This is just a test again to see if it will work', 'Computer literate and good in communicating skills', 3, '2025-11-01 17:53:39.317653', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `invites`
--

CREATE TABLE `invites` (
  `invite_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(100) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement`
--

CREATE TABLE `requirement` (
  `requirement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(50) NOT NULL,
  `file_path` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `coordinator_id` int(11) NOT NULL,
  `validated_at` timestamp(6) NULL DEFAULT NULL,
  `uploaded_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(6, 11, 'Lance', 'Mayormita', 'BS in Information Technology', '2nd Year', 'mrlance@gmail.com', '09765435721', 'I love to play games and have some fun with the co', 'Active');

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
(14, 'chowchow', '$2y$10$xnnARrcGh7sxC2Q9lhGKX.TbKESNhk8MemGDOXK9V881W/ILaRXK.', 'company', 'Pending');

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
-- Indexes for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `app_id` (`application_id`),
  ADD KEY `st_id` (`student_id`),
  ADD KEY `c_id` (`company_id`),
  ADD KEY `coor_id` (`coordinator_id`);

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
-- Indexes for table `invites`
--
ALTER TABLE `invites`
  ADD PRIMARY KEY (`invite_id`),
  ADD KEY `com_id` (`company_id`),
  ADD KEY `stud-id` (`student_id`);

--
-- Indexes for table `requirement`
--
ALTER TABLE `requirement`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `coordinator_id` (`coordinator_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `student-users` (`user_id`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coordinator`
--
ALTER TABLE `coordinator`
  MODIFY `coordinator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `intern_application`
--
ALTER TABLE `intern_application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `intern_posting`
--
ALTER TABLE `intern_posting`
  MODIFY `posting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invites`
--
ALTER TABLE `invites`
  MODIFY `invite_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement`
--
ALTER TABLE `requirement`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
-- Constraints for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD CONSTRAINT `app_id` FOREIGN KEY (`application_id`) REFERENCES `application` (`application_id`),
  ADD CONSTRAINT `c_id` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`),
  ADD CONSTRAINT `coor_id` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinator` (`coordinator_id`),
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
-- Constraints for table `invites`
--
ALTER TABLE `invites`
  ADD CONSTRAINT `com_id` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`),
  ADD CONSTRAINT `stud-id` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `requirement`
--
ALTER TABLE `requirement`
  ADD CONSTRAINT `coordinator_id` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinator` (`coordinator_id`),
  ADD CONSTRAINT `student_id` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student-users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
