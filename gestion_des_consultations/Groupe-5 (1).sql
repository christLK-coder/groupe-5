CREATE TABLE PATIENT (
    id_patient INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    image_patient VARCHAR(100),
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE SERVICES (
    id_service INT PRIMARY KEY AUTO_INCREMENT,
    nom_service VARCHAR(255) NOT NULL, -- Ajouté pour un nom de service clair
    description TEXT,
    image_service VARCHAR(100)
);

CREATE TABLE specialite (
    id_specialite INT PRIMARY KEY AUTO_INCREMENT,
    id_service INT NOT NULL, -- Assure qu'une spécialité est toujours liée à un service
    nom VARCHAR(255) NOT NULL,
    description_specialite TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, -- Pour suivre quand la spécialité a été ajoutée
    est_active BOOLEAN DEFAULT TRUE, -- Pour activer/désactiver une spécialité
    FOREIGN KEY (id_service) REFERENCES SERVICES(id_service)
);

CREATE TABLE MEDECIN (
    id_medecin INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    id_service INT NOT NULL,
    id_specialite INT NOT NULL, -- Liaison directe avec la table specialite
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    sexe VARCHAR(100),
    disponible BOOLEAN DEFAULT TRUE,
    image_medecin VARCHAR(100),
    biographie text ,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_service) REFERENCES SERVICES(id_service),
    FOREIGN KEY (id_specialite) REFERENCES specialite(id_specialite)
);

CREATE TABLE ADMIN (
    id_admin INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    image_admin VARCHAR(100),
    telephone VARCHAR(20),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE RENDEZVOUS (
    id_rdv INT PRIMARY KEY AUTO_INCREMENT,
    date_heure DATETIME NOT NULL,
    type_consultation ENUM('domicile','hopital') NOT NULL,
    niveau_urgence ENUM('normal', 'urgent') DEFAULT 'normal',
    statut ENUM('en_attente','encours', 'confirmé', 'terminé', 'annulé') DEFAULT 'en_attente',
    symptomes TEXT,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    date_début DATETIME,
    
    date_fin DATETIME,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    longitude DOUBLE,
    latitude DOUBLE,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin)
);

CREATE TABLE DIAGNOSTIC (
    id_diagnostic INT PRIMARY KEY AUTO_INCREMENT,
    contenu TEXT NOT NULL,
    id_rdv INT UNIQUE NOT NULL, -- Un diagnostic par RDV
    date_diagnostic DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv)
);

CREATE TABLE CONVERSATION (
    id_conversation INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin),
    UNIQUE (id_patient, id_medecin)
);

CREATE TABLE MESSAGE (
    id_message INT PRIMARY KEY AUTO_INCREMENT,
    id_conversation INT NOT NULL,
    id_expediteur INT NOT NULL,
    type_expediteur ENUM('patient', 'medecin') NOT NULL,
    contenu TEXT NOT NULL,
    date_message DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_conversation) REFERENCES CONVERSATION(id_conversation)
);

CREATE TABLE COMMENTAIRE (
    id_commentaire INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT NOT NULL,
    id_medecin INT NULL,
    contenu TEXT NOT NULL,
    date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin),
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv)
);

CREATE TABLE NOTE (
    id_note INT PRIMARY KEY AUTO_INCREMENT,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    id_medecin INT NOT NULL, -- Le médecin qui a reçu la note
    date_notation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin)
);

CREATE TABLE SIGNALEMENT (
    id_signalement INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT, -- Peut être NULL si le signalement vient d'un admin par exemple
    id_medecin INT, -- Peut être NULL si le signalement concerne un patient
    motif TEXT NOT NULL,
    statut ENUM('en_cours', 'traité', 'résolu') DEFAULT 'en_cours',
    date_signalement DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME, -- Pour suivre quand le signalement a été traité
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin)
);


CREATE TABLE PRESCRIPTION (
    id_prescription INT AUTO_INCREMENT PRIMARY KEY,
    id_rdv INT NOT NULL,
    medicament VARCHAR(255),
    posologie TEXT,
    duree VARCHAR(100),
    conseils TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv) ON DELETE CASCADE
);
