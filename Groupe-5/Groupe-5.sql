CREATE TABLE PATIENT (
    id_patient INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    mot_de_passe VARCHAR(255),
    telephone VARCHAR(20),
    adresse TEXT,
    image_patient VARCHAR(100),
    latitude DOUBLE,
    longitude DOUBLE
);

CREATE TABLE SERVICES (
    id_service INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT,
    specialite VARCHAR(100),
    image_service VARCHAR(100)
);

CREATE TABLE MEDECIN (
    id_medecin INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    id_service INT,
    specialite VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    mot_de_passe VARCHAR(255),
    telephone VARCHAR(20),
    adresse TEXT,
    disponible BOOLEAN,
    image_medecin VARCHAR(100),
    latitude DOUBLE,
    longitude DOUBLE,
    FOREIGN KEY (id_service) REFERENCES SERVICES(id_service)
);

CREATE TABLE ADMIN (
    id_admin INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    mot_de_passe VARCHAR(255),
    image_admin VARCHAR(100),
    telephone VARCHAR(20)
);

CREATE TABLE RENDEZVOUS (
    id_rdv INT PRIMARY KEY AUTO_INCREMENT,
    date_heure DATETIME,
    type_consultation ENUM('domicile', 'en_ligne', 'hopital'),
    niveau_urgence ENUM('normal', 'urgent'),
    statut ENUM('en_attente', 'confirmé', 'terminé', 'annulé'),
    symptomes TEXT,
    id_patient INT,
    id_medecin INT,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin)
);

CREATE TABLE DIAGNOSTIC (
    id_diagnostic INT PRIMARY KEY AUTO_INCREMENT,
    contenu TEXT,
    id_rdv INT,
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv)
);

CREATE TABLE CONVERSATION (
    id_conversation INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT,
    id_medecin INT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin),
    UNIQUE (id_patient, id_medecin)
);

CREATE TABLE MESSAGE (
    id_message INT PRIMARY KEY AUTO_INCREMENT,
    id_conversation INT,
    id_expediteur INT,
    type_expediteur ENUM('patient', 'medecin'),
    contenu TEXT,
    date_message DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_conversation) REFERENCES CONVERSATION(id_conversation)
);

CREATE TABLE COMMENTAIRE (
    id_commentaire INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT,
    id_medecin INT NULL, -- Made nullable as per your comment
    id_rdv INT NULL,     -- Made nullable as per your comment
    contenu TEXT,
    date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin),
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv)
);

CREATE TABLE NOTE (
    id_note INT PRIMARY KEY AUTO_INCREMENT,
    id_rdv INT,
    note INT CHECK (note BETWEEN 1 AND 5),
    id_medecin INT,
    date_notation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rdv) REFERENCES RENDEZVOUS(id_rdv),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin)
);

CREATE TABLE SIGNALEMENT (
    id_signalement INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT,
    id_medecin INT,
    motif TEXT,
    statut ENUM('en cours', 'traité'),
    date_signalement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES PATIENT(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES MEDECIN(id_medecin)
);