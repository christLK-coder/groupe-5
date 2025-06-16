-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 14, 2025 at 10:27 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hopital`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `image_admin` text,
  `telephone` varchar(20) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `nom`, `prenom`, `email`, `mot_de_passe`, `image_admin`, `telephone`, `date_creation`) VALUES
(1, 'Nama', 'Luciano', 'lucianonama12345@gmail.com', '$2y$10$xB7NMBCza7k.RLyTFE2R.eoV.DlMo/UWl2ObCVVIM2Ph67MPikkiG', NULL, '525525252552', '2025-06-12 14:30:29');

-- --------------------------------------------------------

--
-- Table structure for table `commentaire`
--

DROP TABLE IF EXISTS `commentaire`;
CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `date_commentaire` datetime DEFAULT CURRENT_TIMESTAMP,
  `nom` varchar(100) NOT NULL,
  PRIMARY KEY (`id_commentaire`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `commentaire`
--

INSERT INTO `commentaire` (`id_commentaire`, `contenu`, `date_commentaire`, `nom`) VALUES
(13, 'votre site est un mauvais site il a detruit ma vie', '2025-06-13 18:13:55', 'Inconnu'),
(12, 'Votre site est un tres bon site j\'ai adorer', '2025-06-13 18:13:29', 'Inconnu'),
(14, 'j\'ai aimé le site surtout pour le system de commentation', '2025-06-13 18:14:59', 'Christ'),
(15, 'Votre site ma beaucoup aidé dans la santé vraiment beaucoup', '2025-06-13 18:16:37', 'Luciano');

-- --------------------------------------------------------

--
-- Table structure for table `conversation`
--

DROP TABLE IF EXISTS `conversation`;
CREATE TABLE IF NOT EXISTS `conversation` (
  `id_conversation` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `id_medecin` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_conversation`),
  UNIQUE KEY `id_patient` (`id_patient`,`id_medecin`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `conversation`
--

INSERT INTO `conversation` (`id_conversation`, `id_patient`, `id_medecin`, `date_creation`) VALUES
(1, 13, 1, '2025-06-10 18:45:33'),
(2, 13, 2, '2025-06-11 01:28:58'),
(3, 13, 3, '2025-06-13 00:52:45'),
(4, 13, 6, '2025-06-13 14:09:19');

-- --------------------------------------------------------

--
-- Table structure for table `diagnostic`
--

DROP TABLE IF EXISTS `diagnostic`;
CREATE TABLE IF NOT EXISTS `diagnostic` (
  `id_diagnostic` int NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `id_rdv` int NOT NULL,
  `date_diagnostic` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_diagnostic`),
  UNIQUE KEY `id_rdv` (`id_rdv`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `diagnostic`
--

INSERT INTO `diagnostic` (`id_diagnostic`, `contenu`, `id_rdv`, `date_diagnostic`) VALUES
(1, 'VHttt', 44, '2025-06-13 11:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `medecin`
--

DROP TABLE IF EXISTS `medecin`;
CREATE TABLE IF NOT EXISTS `medecin` (
  `id_medecin` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `id_service` int NOT NULL,
  `id_specialite` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text,
  `statut_disponible` tinyint(1) DEFAULT '1',
  `image_medecin` text,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `biographie` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sexe` text NOT NULL,
  `valide` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_medecin`),
  UNIQUE KEY `email` (`email`),
  KEY `id_service` (`id_service`),
  KEY `id_specialite` (`id_specialite`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medecin`
--

INSERT INTO `medecin` (`id_medecin`, `nom`, `prenom`, `id_service`, `id_specialite`, `email`, `mot_de_passe`, `telephone`, `adresse`, `statut_disponible`, `image_medecin`, `date_inscription`, `biographie`, `sexe`, `valide`) VALUES
(1, 'Nga', 'christine', 1, 1, 'nga.christine@example.com', '$2y$10$abcdefghijklmnopqrstuvwxyza.abcdefghijkl', '525525252552', 'Nsam', 1, '1.jpg', '2025-06-10 18:33:51', 'Le Dr. Luciano, médecin passionné, est reconnu pour son approche créative des soins. Toujours à la recherche de solutions innovantes, il allie science et humanité pour offrir une médecine personnalisée et efficace à ses patients.', 'Femme', 1),
(2, 'Abanda', 'Paul', 1, 1, 'jean.dupont@hopital.com', '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmno', '0123456789', '12 Rue des Médecins, 75000 Paris', 1, 'dr_jean_dupont.jpg', '2025-06-11 01:27:39', 'Le Dr. Jean Dupont est un cardiologue expérimenté, spécialisé dans les maladies coronariennes et l\'hypertension. Il est reconnu pour son approche patiente et son dévouement.', 'Homme', 1),
(3, 'Nama', 'luciano', 2, 3, 'lucianonama123@gmail.com', '$2y$10$BICEEcRdwe4zs.kJBDyNcOQTAZDANeMCHtNtWXYyiZOWCZjMdr6oS', '657867965', '333333', 1, 'medecin_684c11ee0c836.jpg', '2025-06-12 23:44:17', 'IL EST Tres FORT', 'Homme', 1),
(5, 'Nama', 'sandra', 1, 3, 'kamdeusandra3@gmail.com', '$2y$10$5Ohx3LFOTM0Pc0VOKzMogeC.t7ETMH4/7Mh3/jZc9HSTg6D2WIPmW', '657867965', '333333', 1, 'bleach 01 [www.vikitech.com].jpg', '2025-06-13 00:49:56', 'IL EST FORT', 'Homme', 1),
(6, 'Christ', 'Armand', 1, 1, 'christarmandlemongo@gmail.com', '$2y$10$by9mJNwqjAjn1EDQGSJWNOCn2j88LzRBEym0EBePId0FMmAAb51N.', '657867963', 'Titi garage', 1, 'Ee9RHdCWAAAg7jO.jpg', '2025-06-13 14:01:58', 'diplome', 'Homme', 1);

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `id_message` int NOT NULL AUTO_INCREMENT,
  `id_conversation` int NOT NULL,
  `id_expediteur` int NOT NULL,
  `type_expediteur` enum('patient','medecin') NOT NULL,
  `contenu` text NOT NULL,
  `date_message` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_message`),
  KEY `id_conversation` (`id_conversation`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`id_message`, `id_conversation`, `id_expediteur`, `type_expediteur`, `contenu`, `date_message`) VALUES
(1, 1, 13, 'patient', 'Bonjour Dr. Lemoine, j\'ai quelques questions concernant mon bilan cardiaque récent.', '2025-06-09 10:05:00'),
(2, 1, 1, 'medecin', 'Bonjour Alice, je suis disponible. Posez vos questions, je ferai de mon mieux pour vous éclairer.', '2025-06-09 10:10:00'),
(3, 1, 13, 'patient', 'Merci. Mon rapport mentionne une légère anomalie, est-ce grave ?', '2025-06-09 10:12:30'),
(4, 1, 1, 'medecin', 'Je vois le rapport. Ne vous inquiétez pas, cette anomalie est courante et ne présente pas de danger immédiat. Nous en discuterons plus en détail lors de votre prochain RDV.', '2025-06-09 10:15:45'),
(5, 1, 13, 'patient', 'GG', '2025-06-10 18:49:13'),
(6, 1, 13, 'patient', 'GGDHDJDJDDDDDDDDDDDDD', '2025-06-10 18:49:21'),
(7, 1, 13, 'patient', 'dd', '2025-06-10 23:56:41'),
(8, 2, 13, 'patient', 'merci', '2025-06-11 01:29:19'),
(9, 1, 13, 'patient', 'ddkd', '2025-06-11 08:56:08'),
(10, 1, 13, 'patient', 'SJSJ', '2025-06-11 12:45:09'),
(11, 1, 1, 'medecin', 'C\'est bon', '2025-06-11 13:32:32'),
(12, 1, 1, 'medecin', 'Yes', '2025-06-11 22:55:49'),
(13, 1, 1, 'medecin', 'Yes', '2025-06-11 22:55:54'),
(14, 1, 1, 'medecin', 'YO', '2025-06-12 17:04:23'),
(15, 3, 3, 'medecin', 'YO', '2025-06-13 00:53:56'),
(16, 1, 13, 'patient', 'yes', '2025-06-13 11:30:23'),
(17, 3, 3, 'medecin', 'yes', '2025-06-13 11:31:57'),
(18, 3, 13, 'patient', 'yes', '2025-06-13 11:32:25'),
(19, 3, 3, 'medecin', 'yes', '2025-06-13 11:32:38'),
(20, 4, 13, 'patient', 'j\'ai tres mal au ventre', '2025-06-13 14:09:49'),
(21, 4, 13, 'patient', 'je besoin d\'une consultation', '2025-06-13 14:10:09'),
(22, 4, 6, 'medecin', 'okay je vous recontact', '2025-06-13 14:10:35'),
(23, 4, 13, 'patient', 'je veux une consultation', '2025-06-13 18:31:59');

-- --------------------------------------------------------

--
-- Table structure for table `note`
--

DROP TABLE IF EXISTS `note`;
CREATE TABLE IF NOT EXISTS `note` (
  `id_note` int NOT NULL AUTO_INCREMENT,
  `note` int NOT NULL,
  `id_medecin` int NOT NULL,
  `date_notation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_note`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `note`
--

INSERT INTO `note` (`id_note`, `note`, `id_medecin`, `date_notation`) VALUES
(1, 2, 1, '2025-06-12 18:24:50'),
(2, 5, 1, '2025-06-12 18:24:59'),
(3, 5, 1, '2025-06-12 18:25:13'),
(4, 4, 1, '2025-06-12 18:25:28'),
(5, 1, 1, '2025-06-12 18:25:34'),
(6, 3, 3, '2025-06-12 23:49:24'),
(7, 1, 2, '2025-06-13 16:28:05'),
(8, 1, 2, '2025-06-13 16:28:08'),
(9, 3, 6, '2025-06-13 18:32:50');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

DROP TABLE IF EXISTS `patient`;
CREATE TABLE IF NOT EXISTS `patient` (
  `id_patient` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text,
  `image_patient` text,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `sexe` enum('Homme','Femme') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Homme',
  PRIMARY KEY (`id_patient`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`id_patient`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `adresse`, `image_patient`, `date_inscription`, `sexe`) VALUES
(13, 'Luciano', 'Charly', 'lucianonama1234@gmail.com', '$2y$10$f1L25hyK3yKmi9rzBmmHFeT.ltJSTiyEZQF4UXEPN/N8SsatS23FO', '699222922', 'AHala', 'uploads/patients/684b1ea880318.jpg', '2025-06-10 18:08:42', 'Homme'),
(14, 'Kamdeu', 'Sandra', 'Kamdeusandra237@gmail.com', '$2y$10$sqJ060nEwSSAhmp/G8B7EuzolWcNmfSHmYGGvNt7QgqY6CZeHMcCq', '699222922', 'Ahala', 'uploads/6849f4b32fbdd_00ba0a60b6b29b73f4945f2bc6602f9f.jpg', '2025-06-11 22:27:15', 'Femme');

-- --------------------------------------------------------

--
-- Table structure for table `prescription`
--

DROP TABLE IF EXISTS `prescription`;
CREATE TABLE IF NOT EXISTS `prescription` (
  `id_prescription` int NOT NULL AUTO_INCREMENT,
  `id_rdv` int NOT NULL,
  `medicament` varchar(255) DEFAULT NULL,
  `posologie` text,
  `duree` varchar(100) DEFAULT NULL,
  `conseils` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_prescription`),
  KEY `id_rdv` (`id_rdv`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prescription`
--

INSERT INTO `prescription` (`id_prescription`, `id_rdv`, `medicament`, `posologie`, `duree`, `conseils`, `date_creation`) VALUES
(1, 44, 'Paracetamol', 'ss', '2h', 'ss', '2025-06-13 10:11:40'),
(14, 44, 'dddd', 'dd', 'dd', 'dd', '2025-06-13 11:50:01'),
(13, 44, 'dddd', 'dd', 'dd', 'dd', '2025-06-13 11:49:53');

-- --------------------------------------------------------

--
-- Table structure for table `rendezvous`
--

DROP TABLE IF EXISTS `rendezvous`;
CREATE TABLE IF NOT EXISTS `rendezvous` (
  `id_rdv` int NOT NULL AUTO_INCREMENT,
  `date_heure` datetime NOT NULL,
  `type_consultation` enum('domicile','en_ligne','hopital') NOT NULL,
  `niveau_urgence` enum('normal','urgent') DEFAULT 'normal',
  `statut` enum('en_attente','confirmé','terminé','encours','annulé') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'en_attente',
  `symptomes` text,
  `id_patient` int NOT NULL,
  `id_medecin` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `longitude` double NOT NULL,
  `latitude` double NOT NULL,
  `duree_rdv` int DEFAULT '30' COMMENT 'Durée du rendez-vous en minutes',
  PRIMARY KEY (`id_rdv`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rendezvous`
--

INSERT INTO `rendezvous` (`id_rdv`, `date_heure`, `type_consultation`, `niveau_urgence`, `statut`, `symptomes`, `id_patient`, `id_medecin`, `date_creation`, `date_debut`, `date_fin`, `longitude`, `latitude`, `duree_rdv`) VALUES
(42, '2025-06-13 08:11:00', 'hopital', 'normal', 'terminé', 'kk', 13, 3, '2025-06-13 01:11:19', '2025-06-13 08:11:00', '2025-06-13 08:56:00', 0, 0, 45),
(41, '2025-06-13 08:06:00', 'hopital', 'normal', 'annulé', 'g', 13, 3, '2025-06-13 01:06:45', '2025-06-13 08:06:00', '2025-06-13 08:36:00', 0, 0, 30),
(40, '2025-07-02 08:55:00', 'hopital', 'normal', 'en_attente', 'DD', 13, 3, '2025-06-13 01:01:54', '2025-07-02 08:55:00', '2025-07-02 09:25:00', 0, 0, 30),
(39, '2025-06-12 14:09:00', 'hopital', 'normal', 'annulé', 'k', 13, 1, '2025-06-12 14:06:47', '2025-06-12 14:09:00', '2025-06-12 14:39:00', 0, 0, 30),
(38, '2025-06-14 17:06:00', 'hopital', 'normal', 'en_attente', '', 13, 1, '2025-06-12 14:06:37', '2025-06-14 17:06:00', '2025-06-14 17:36:00', 0, 0, 30),
(36, '2025-06-12 15:01:00', 'domicile', 'normal', 'annulé', 'D', 13, 2, '2025-06-12 12:05:25', '2025-06-12 15:01:00', '2025-06-12 15:31:00', 11.550424663262, 3.8837421417172, 30),
(37, '2025-06-13 17:06:00', 'hopital', 'normal', 'en_attente', '', 13, 1, '2025-06-12 14:06:22', '2025-06-13 17:06:00', '2025-06-13 17:36:00', 0, 0, 30),
(43, '2025-06-13 13:29:00', 'hopital', 'normal', 'en_attente', 'D', 13, 1, '2025-06-13 09:29:10', '2025-06-13 13:29:00', '2025-06-13 13:59:00', 0, 0, 30),
(44, '2025-06-13 12:29:00', 'hopital', 'normal', 'annulé', 'D', 13, 3, '2025-06-13 09:29:59', '2025-06-13 12:29:00', '2025-06-13 12:58:00', 0, 0, 29),
(45, '2025-06-13 14:35:00', 'hopital', 'normal', 'confirmé', 'e', 13, 3, '2025-06-13 11:35:47', '2025-06-13 14:35:00', '2025-06-13 15:05:00', 0, 0, 30),
(46, '2025-06-13 16:36:00', 'domicile', 'normal', 'confirmé', 'h', 13, 3, '2025-06-13 11:37:23', '2025-06-13 16:36:00', '2025-06-13 17:06:00', 11.550253329633, 3.8836567871023, 30),
(47, '2025-06-13 14:01:00', 'domicile', 'normal', 'confirmé', 'w', 13, 3, '2025-06-13 11:52:17', '2025-06-13 14:01:00', '2025-06-13 14:31:00', 11.55033, 3.88366, 30),
(48, '2025-06-13 14:10:00', 'hopital', 'normal', 'terminé', 'MAUX DE VENTRE', 13, 6, '2025-06-13 14:08:30', '2025-06-13 14:10:00', '2025-06-13 14:40:00', 0, 0, 30),
(49, '2025-06-13 17:08:00', 'hopital', 'normal', 'terminé', 'MAUX DE TETE', 13, 6, '2025-06-13 14:08:47', '2025-06-13 17:08:00', '2025-06-13 17:38:00', 0, 0, 30),
(50, '2025-06-14 13:33:00', 'hopital', 'normal', 'annulé', '', 13, 6, '2025-06-13 18:33:23', '2025-06-14 13:33:00', '2025-06-14 14:03:00', 0, 0, 30),
(51, '2025-06-14 14:33:00', 'hopital', 'normal', 'annulé', 'je', 13, 6, '2025-06-13 18:34:00', '2025-06-14 14:33:00', '2025-06-14 15:03:00', 0, 0, 30);

-- --------------------------------------------------------
INSERT INTO rendezvous ( date_heure , type_consultation , niveau_urgence , statut , symptômes , id_patient , id_medecin , date_creation , date_debut , date_fin , longitude , latitude , duree_rdv ) VALEURS
(52,'2025-06-14 14:00:00', 'hopital', 'normal', 'en_attente', 'Consultation générale', 13, 6, '2025-06-14 07:14:00', '2025-06-14 14:00:00', '2025-06-14 14:30:00', 0, 0, 30);
--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id_service` int NOT NULL AUTO_INCREMENT,
  `nom_service` varchar(255) NOT NULL,
  `description` text,
  `image_service` text,
  PRIMARY KEY (`id_service`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id_service`, `nom_service`, `description`, `image_service`) VALUES
(1, 'Médecine Générale', 'Ce services est specialise dans la medecine generale ', 'uploads/services/1.jpg'),
(2, 'Radiologie', 'pour la radiohhhjhf', 'uploads/services/1749738121_506392.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `signalement`
--

DROP TABLE IF EXISTS `signalement`;
CREATE TABLE IF NOT EXISTS `signalement` (
  `id_signalement` int NOT NULL AUTO_INCREMENT,
  `id_patient` int DEFAULT NULL,
  `id_medecin` int DEFAULT NULL,
  `motif` text NOT NULL,
  `statut` enum('en_cours','traité','résolu') DEFAULT 'en_cours',
  `date_signalement` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  PRIMARY KEY (`id_signalement`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specialite`
--

DROP TABLE IF EXISTS `specialite`;
CREATE TABLE IF NOT EXISTS `specialite` (
  `id_specialite` int NOT NULL AUTO_INCREMENT,
  `id_service` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description_specialite` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `est_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_specialite`),
  KEY `id_service` (`id_service`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `specialite`
--

INSERT INTO `specialite` (`id_specialite`, `id_service`, `nom`, `description_specialite`, `date_creation`, `est_active`) VALUES
(1, 1, 'Généraliste', 'Est une specialité tres demandé', '2025-06-10 18:33:51', 1),
(2, 2, 'dddddd', 'siddha', '2025-06-12 15:25:31', 1),
(3, 1, 'rhumatique', 'ffff', '2025-06-12 18:11:14', 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
