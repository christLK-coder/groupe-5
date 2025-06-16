-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 12 juin 2025 à 03:15
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
  `image_admin` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id_admin`, `nom`, `email`, `mot_de_passe`, `date_creation`, `image_admin`) VALUES
(2, 'MANG', 'michou@gmail.com', '$2y$10$xB7NMBCza7k.RLyTFE2R.eoV.DlMo/UWl2ObCVVIM2Ph67MPikkiG', '2025-06-11 00:48:35', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

DROP TABLE IF EXISTS `commentaire`;
CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `id_medecin` int DEFAULT NULL,
  `id_rdv` int DEFAULT NULL,
  `contenu` text NOT NULL,
  `date_commentaire` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_commentaire`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`),
  KEY `id_rdv` (`id_rdv`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversation`
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `diagnostic`
--

DROP TABLE IF EXISTS `diagnostic`;
CREATE TABLE IF NOT EXISTS `diagnostic` (
  `id_diagnostic` int NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `id_rdv` int NOT NULL,
  `date_diagnostic` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_diagnostic`),
  UNIQUE KEY `id_rdv` (`id_rdv`)
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
  `valide` tinyint(1) DEFAULT '0',
  `statut_disponible` tinyint(1) DEFAULT '1',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `sexe` varchar(10) NOT NULL DEFAULT 'Femme',
  `image_medecin` varchar(100) DEFAULT NULL,
  `id_service` int DEFAULT NULL,
  `id_specialite` int DEFAULT NULL,
  `adresse` text,
  `biographie` text,
  PRIMARY KEY (`id_medecin`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_service` (`id_service`),
  KEY `fk_specialite` (`id_specialite`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `medecin`
--

INSERT INTO `medecin` (`id_medecin`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `valide`, `statut_disponible`, `date_inscription`, `sexe`, `image_medecin`, `id_service`, `id_specialite`, `adresse`, `biographie`) VALUES
(5, 'BITEE', 'Georgica', 'biteegeorgica@gmail.com', '$2y$10$hmnwQ/bwTW8FLB8G.6jJSedfj.p6GO7H8Bwc6tZG3yy3leBORTA9i', '690124567', 0, 0, '2025-06-12 03:07:44', 'Femme', 'Bateau Eau.jpg', 4, 7, 'Mbalmayo,Cameroun', 'Meilleur medecin du cameroun et tres hospitaliere');

-- --------------------------------------------------------

--
-- Structure de la table `message`
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `note`
--

DROP TABLE IF EXISTS `note`;
CREATE TABLE IF NOT EXISTS `note` (
  `id_note` int NOT NULL AUTO_INCREMENT,
  `id_rdv` int DEFAULT NULL,
  `note` int NOT NULL,
  `id_medecin` int NOT NULL,
  `date_notation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_note`),
  KEY `id_rdv` (`id_rdv`),
  KEY `id_medecin` (`id_medecin`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `patient`
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
  `image_patient` varchar(100) DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `sexe` enum('Homme','Femme') NOT NULL DEFAULT 'Homme',
  PRIMARY KEY (`id_patient`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `patient`
--

INSERT INTO `patient` (`id_patient`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `adresse`, `image_patient`, `date_inscription`, `sexe`) VALUES
(1, 'Tchamda', 'Brice', 'brice.tchamda@gmail.com', '$2y$10$o43DM3vlJX20xJ2UQ.wjoOnHesiwoxDJcZIsZi5ur3/0WiPIM2nB.', '‪+237650000000‬', 'Yaounde,Cameroun', 'brice.jpg', '2025-06-12 01:07:34', 'Homme');

-- --------------------------------------------------------

--
-- Structure de la table `prescription`
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendezvous`
--

DROP TABLE IF EXISTS `rendezvous`;
CREATE TABLE IF NOT EXISTS `rendezvous` (
  `id_rdv` int NOT NULL AUTO_INCREMENT,
  `date_heure` datetime NOT NULL,
  `type_consultation` enum('domicile','hopital') NOT NULL,
  `niveau_urgence` enum('normal','urgent') DEFAULT 'normal',
  `statut` enum('en_attente','encours','confirmé','terminé','annulé') DEFAULT 'en_attente',
  `symptomes` text,
  `id_patient` int NOT NULL,
  `id_medecin` int NOT NULL,
  `date_début` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rdv`),
  KEY `id_patient` (`id_patient`),
  KEY `id_medecin` (`id_medecin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id_service` int NOT NULL AUTO_INCREMENT,
  `nom_service` varchar(255) NOT NULL,
  `description` text,
  `image_service` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_service`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id_service`, `nom_service`, `description`, `image_service`) VALUES
(1, 'Cardiologie', 'Service spécialisé dans le diagnostic et le traitement des maladies cardiovasculaires.', NULL),
(2, 'Pédiatrie', 'Service médical dédié aux soins des nourrissons, enfants et adolescents.', NULL),
(3, 'Gynécologie-Obstétrique', 'Service de soins pour la santé des femmes, grossesse et accouchement.', NULL),
(4, 'Chirurgie Générale', 'Prise en charge des interventions chirurgicales sur différentes parties du corps.', 'Akbar Cheval.jpg'),
(5, 'Neurologie', 'Spécialité des troubles du système nerveux (cerveau, moelle épinière, nerfs).', NULL),
(6, 'Oncologie', 'Service dédié au diagnostic et au traitement des cancers.', NULL),
(7, 'Ophtalmologie', 'Prise en charge des maladies des yeux et troubles de la vision.', NULL),
(8, 'ORL', 'Service traitant les pathologies de l’oreille, du nez et de la gorge.', NULL),
(9, 'Dermatologie', 'Spécialité des maladies de la peau, des cheveux et des ongles.', NULL),
(10, 'Psychiatrie', 'Service de santé mentale pour les troubles psychiques et comportementaux.', NULL),
(11, 'Radiologie', 'Service d’imagerie médicale : radiographies, scanners, IRM, etc.', NULL),
(12, 'Médecine Interne', 'Approche globale des maladies adultes souvent complexes ou chroniques.', NULL),
(13, 'Urgences', 'Service pour la prise en charge rapide des cas médicaux graves et urgents.', NULL),
(14, 'Réanimation', 'Soins intensifs pour patients en état critique nécessitant une surveillance constante.', NULL),
(15, 'Anesthésie', 'Service responsable de l’anesthésie pendant les interventions chirurgicales.', NULL),
(16, 'Néphrologie', 'Spécialité traitant les maladies des reins.', NULL),
(17, 'Endocrinologie', 'Traitement des troubles hormonaux comme le diabète ou les maladies thyroïdiennes.', NULL),
(18, 'Rhumatologie', 'Traitement des affections des os, articulations et tissus conjonctifs.', NULL),
(19, 'Gastro-entérologie', 'Spécialité des maladies du système digestif.', NULL),
(20, 'Hématologie', 'Étude et traitement des maladies du sang et des organes hématopoïétiques.', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `signalement`
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
-- Structure de la table `specialite`
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
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `specialite`
--

INSERT INTO `specialite` (`id_specialite`, `id_service`, `nom`, `description_specialite`, `date_creation`, `est_active`) VALUES
(1, 1, 'Cardiologie interventionnelle', 'Traitement des maladies cardiaques via des procédures mini-invasives (angioplastie, stents, etc.)', '2025-06-12 02:26:57', 1),
(2, 1, 'Rythmologie', 'Spécialité de la cardiologie qui traite les troubles du rythme cardiaque', '2025-06-12 02:26:57', 1),
(3, 2, 'Pédiatrie néonatale', 'Soins médicaux aux nouveau-nés, en particulier les prématurés ou malades', '2025-06-12 02:26:57', 1),
(4, 2, 'Pédiatrie générale', 'Suivi médical de l’enfant de la naissance à l’adolescence', '2025-06-12 02:26:57', 1),
(5, 3, 'Obstétrique', 'Suivi de la grossesse, de l’accouchement et du post-partum', '2025-06-12 02:26:57', 1),
(6, 3, 'Gynécologie médicale', 'Suivi général de la santé reproductive et hormonale de la femme', '2025-06-12 02:26:57', 1),
(7, 4, 'Chirurgie digestive', 'Interventions sur les organes digestifs (foie, intestins, estomac, etc.)', '2025-06-12 02:26:57', 1),
(8, 4, 'Chirurgie vasculaire', 'Traitement chirurgical des artères et veines', '2025-06-12 02:26:57', 1),
(9, 5, 'Neurologie générale', 'Diagnostic et traitement des pathologies neurologiques courantes', '2025-06-12 02:26:57', 1),
(10, 5, 'Épileptologie', 'Spécialité axée sur le traitement des épilepsies', '2025-06-12 02:26:57', 1),
(11, 6, 'Oncologie médicale', 'Traitement du cancer par chimiothérapie et autres méthodes non chirurgicales', '2025-06-12 02:26:57', 1),
(12, 6, 'Oncologie radiothérapeutique', 'Traitement du cancer par radiothérapie', '2025-06-12 02:26:57', 1),
(13, 7, 'Chirurgie réfractive', 'Correction de la vue au laser (myopie, astigmatisme, etc.)', '2025-06-12 02:26:57', 1),
(14, 8, 'Otologie', 'Traitement des maladies de l’oreille', '2025-06-12 02:26:57', 1),
(15, 8, 'Rhinologie', 'Traitement des maladies du nez et des sinus', '2025-06-12 02:26:57', 1),
(16, 9, 'Dermatologie esthétique', 'Soins de la peau à but esthétique (laser, peelings, etc.)', '2025-06-12 02:26:57', 1),
(17, 9, 'Dermatologie allergologique', 'Traitement des allergies cutanées', '2025-06-12 02:26:57', 1),
(18, 10, 'Psychiatrie de l’enfant et de l’adolescent', 'Traitement des troubles mentaux chez les jeunes', '2025-06-12 02:26:57', 1),
(19, 10, 'Addictologie', 'Prise en charge des addictions (drogue, alcool, etc.)', '2025-06-12 02:26:57', 1),
(20, 11, 'Radiologie interventionnelle', 'Actes médicaux réalisés sous imagerie (biopsies, drainages...)', '2025-06-12 02:26:57', 1),
(21, 12, 'Médecine des maladies infectieuses', 'Traitement des infections aiguës et chroniques', '2025-06-12 02:26:57', 1),
(22, 13, 'Médecine d’urgence traumatologique', 'Prise en charge des blessures graves et traumatismes', '2025-06-12 02:26:57', 1),
(23, 14, 'Réanimation polyvalente', 'Soins intensifs à des patients présentant plusieurs défaillances vitales', '2025-06-12 02:26:57', 1),
(24, 15, 'Anesthésie générale', 'Gestion de la douleur et de l’inconscience lors des interventions majeures', '2025-06-12 02:26:57', 1),
(25, 16, 'Dialyse', 'Traitement de suppléance pour les patients souffrant d’insuffisance rénale', '2025-06-12 02:26:57', 1),
(26, 17, 'Diabétologie', 'Spécialité de la prise en charge du diabète', '2025-06-12 02:26:57', 1),
(27, 18, 'Rhumatologie inflammatoire', 'Traitement des maladies inflammatoires des articulations (polyarthrite...)', '2025-06-12 02:26:57', 1),
(28, 19, 'Hépatologie', 'Spécialité traitant des maladies du foie', '2025-06-12 02:26:57', 1),
(29, 20, 'Hématologie clinique', 'Prise en charge des maladies du sang (leucémies, anémies...)', '2025-06-12 02:26:57', 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
