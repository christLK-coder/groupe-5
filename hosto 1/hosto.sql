-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 11 juin 2025 à 07:11
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `hosto`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id_admin`, `nom`, `email`, `mot_de_passe`, `date_creation`) VALUES
(2, 'MANG', 'michou@gmail.com', '$2y$10$xB7NMBCza7k.RLyTFE2R.eoV.DlMo/UWl2ObCVVIM2Ph67MPikkiG', '2025-06-11 00:48:35');

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

DROP TABLE IF EXISTS `commentaire`;
CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT,
  `id_patient` int DEFAULT NULL,
  `id_medecin` int DEFAULT NULL,
  `commentaire` text,
  `note` int DEFAULT NULL,
  `cible` enum('application','medecin') DEFAULT NULL,
  `date_commentaire` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_commentaire`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `diagnostic`
--

DROP TABLE IF EXISTS `diagnostic`;
CREATE TABLE IF NOT EXISTS `diagnostic` (
  `id_diagnostic` int NOT NULL AUTO_INCREMENT,
  `id_rdv` int DEFAULT NULL,
  `description` text,
  `diagnostic_ai` text,
  `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_diagnostic`),
  KEY `id_rdv` (`id_rdv`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `medecin`
--

DROP TABLE IF EXISTS `medecin`;
CREATE TABLE IF NOT EXISTS `medecin` (
  `id_medecin` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `valide` tinyint(1) DEFAULT '0',
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `statut_disponible` tinyint(1) DEFAULT '1',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `sexe` varchar(10) NOT NULL DEFAULT 'Femme',
  PRIMARY KEY (`id_medecin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `medecin`
--

INSERT INTO `medecin` (`id_medecin`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `specialite`, `valide`, `latitude`, `longitude`, `statut_disponible`, `date_inscription`, `sexe`) VALUES
(1, 'MENGUE', 'Stephanie', 'menguestephanie@gmail.com', '$2y$10$1mOtfWyt5roYSD4iHVNmX.W8T2XrBIFF5CkMUM5TYwqSYTkGLvtc6', '696424633', 'chirugie', 1, 5, 10, 1, '2025-06-11 01:47:26', 'Femme'),
(2, 'ANGE ', 'Merveille', 'angemerveille@gmail.com', '$2y$10$.jwknOE/OZJ.zt7aGQYNWemifv0vUN0Un6rwBpQRlrZeqKNuiULpK', '656567515', 'ophtalmologie ', 0, 11, 10, 1, '2025-06-11 08:53:38', 'Femme');

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

DROP TABLE IF EXISTS `notification`;
CREATE TABLE IF NOT EXISTS `notification` (
  `id_notification` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) DEFAULT NULL,
  `contenu` text,
  `cible` enum('tous','medecins','patients') DEFAULT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notification`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `patient`
--

DROP TABLE IF EXISTS `patient`;
CREATE TABLE IF NOT EXISTS `patient` (
  `id_patient` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text,
  `langue_preferee` varchar(50) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_patient`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

DROP TABLE IF EXISTS `rendez_vous`;
CREATE TABLE IF NOT EXISTS `rendez_vous` (
  `id_rdv` int NOT NULL AUTO_INCREMENT,
  `id_patient` int DEFAULT NULL,
  `id_medecin` int DEFAULT NULL,
  `date_heure` datetime DEFAULT NULL,
  `type_consultation` enum('présentiel','en ligne') DEFAULT NULL,
  `statut` enum('en attente','confirmé','annulé','terminé') DEFAULT NULL,
  PRIMARY KEY (`id_rdv`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `signalement`
--

DROP TABLE IF EXISTS `signalement`;
CREATE TABLE IF NOT EXISTS `signalement` (
  `id_signalement` int NOT NULL AUTO_INCREMENT,
  `id_patient` int DEFAULT NULL,
  `id_medecin` int DEFAULT NULL,
  `motif` text,
  `statut` enum('en cours','traité') DEFAULT NULL,
  `date_signalement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_signalement`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `traduction`
--

DROP TABLE IF EXISTS `traduction`;
CREATE TABLE IF NOT EXISTS `traduction` (
  `id_traduction` int NOT NULL AUTO_INCREMENT,
  `langue` varchar(50) DEFAULT NULL,
  `cle` text,
  `texte_traduit` text,
  PRIMARY KEY (`id_traduction`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
