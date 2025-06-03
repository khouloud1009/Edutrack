-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 02, 2025 at 10:47 PM
-- Server version: 8.0.35
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edutrack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id_admin` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `id_admin` (`id_admin`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id_admin`, `nom`, `password`, `username`) VALUES
(1, 'Ahmed Bennani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'a.bennani'),
(2, 'Fatima Alaoui', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'f.alaoui'),
(3, 'Omar Chakir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'o.chakir');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id_att` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `heure` time DEFAULT NULL,
  `date` date DEFAULT NULL,
  `id_etu` int DEFAULT NULL,
  `id_course` int DEFAULT NULL,
  PRIMARY KEY (`id_att`),
  UNIQUE KEY `id_att` (`id_att`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id_att`, `heure`, `date`, `id_etu`, `id_course`) VALUES
(1, '08:00:00', '2025-05-15', 1, 1),
(2, '08:00:00', '2025-05-15', 2, 2),
(3, '08:00:00', '2025-05-15', 3, 3),
(4, '08:00:00', '2025-05-16', 1, 1),
(5, '08:00:00', '2025-05-16', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

DROP TABLE IF EXISTS `attendance_sessions`;
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id_session` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` int DEFAULT NULL,
  `id_course` int DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `actual_end_time` timestamp NULL DEFAULT NULL,
  `duration_minutes` int DEFAULT '120',
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_session`),
  UNIQUE KEY `id_session` (`id_session`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id_session`, `id_admin`, `id_course`, `start_time`, `end_time`, `actual_end_time`, `duration_minutes`, `status`, `created_at`) VALUES
(1, 2, 2, '2025-06-01 22:59:08', '2025-06-01 23:59:08', '2025-06-01 23:00:16', 60, 'stopped', '2025-06-01 22:59:08'),
(2, 1, 1, '2025-06-02 13:35:10', '2025-06-02 14:35:10', '2025-06-02 13:36:37', 60, 'stopped', '2025-06-02 13:35:10'),
(15, 1, 1, '2025-06-02 19:45:44', '2025-06-02 20:45:44', '2025-06-02 19:46:54', 60, 'stopped', '2025-06-02 19:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `cours`
--

DROP TABLE IF EXISTS `cours`;
CREATE TABLE IF NOT EXISTS `cours` (
  `id_course` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `id_admin` int DEFAULT NULL,
  PRIMARY KEY (`id_course`),
  UNIQUE KEY `id_course` (`id_course`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cours`
--

INSERT INTO `cours` (`id_course`, `titre`, `niveau`, `id_admin`) VALUES
(1, 'Mathématiques Appliquées', 'Licence 2', 1),
(2, 'Informatique Avancée', 'Master 1', 2),
(3, 'Gestion de Projet', 'Licence 3', 3);

-- --------------------------------------------------------

--
-- Table structure for table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
CREATE TABLE IF NOT EXISTS `etudiants` (
  `id_etu` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `image` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_etu`),
  UNIQUE KEY `id_etu` (`id_etu`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `etudiants`
--

INSERT INTO `etudiants` (`id_etu`, `nom`, `prenom`, `niveau`, `image`, `username`, `password`) VALUES
(1, 'Idrissi', 'Youssef', 'Licence 2', 1, 'y.idrissi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'Benali', 'Aicha', 'Master 1', 2, 'a.benali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'Tazi', 'Mehdi', 'Licence 3', 3, 'm.tazi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(4, 'Rahel', 'Khouloud', 'Licence 3', 1, 'k.rahel', '$2y$10$YlhLL91SpfLtdqHaNuZglergs7boO4amEAWX/5wm//B5nqGbqe3iC'),
(5, 'Rafii', 'Aya', 'Licence 3', 1, 'a.rafii', '$2y$10$eeLvYQghYrGB6vSa2MDyNu3V9m.j6QhE8G71RcUqYQ90oml4yvSCO');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id_event` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin` int DEFAULT NULL,
  `id_course` int DEFAULT NULL,
  `description` text,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id_event`),
  UNIQUE KEY `id_event` (`id_event`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id_event`, `id_admin`, `id_course`, `description`, `date`) VALUES
(1, 1, 1, 'Examen de Mathématiques Appliquées', '2025-06-10'),
(2, 2, 2, 'Projet final Informatique', '2025-06-15'),
(3, 3, 3, 'Présentation Gestion de Projet', '2025-06-20');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `id_grades` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `grade` float DEFAULT NULL,
  `matiere` varchar(100) DEFAULT NULL,
  `id_etu` int DEFAULT NULL,
  `id_course` int DEFAULT NULL,
  `id_admin` int DEFAULT NULL,
  PRIMARY KEY (`id_grades`),
  UNIQUE KEY `id_grades` (`id_grades`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id_grades`, `grade`, `matiere`, `id_etu`, `id_course`, `id_admin`) VALUES
(1, 16.5, 'Algèbre', 1, 1, 1),
(2, 14, 'Analyse', 1, 1, 1),
(3, 18, 'Programmation', 2, 2, 2),
(4, 15.5, 'Base de données', 2, 2, 2),
(5, 13.5, 'Management', 3, 3, 3),
(6, 16, 'Leadership', 3, 3, 3);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
