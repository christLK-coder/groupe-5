-- Disable foreign key checks to clear tables safely
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE PRESCRIPTION;
TRUNCATE TABLE DIAGNOSTIC;
TRUNCATE TABLE RENDEZVOUS;
TRUNCATE TABLE PATIENT;
TRUNCATE TABLE MEDECIN;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert Doctor (MEDECIN)
-- Single doctor with specified email
INSERT INTO MEDECIN (id_medecin, nom, prenom, email, mot_de_passe, image_medecin, telephone, adresse) VALUES
(1, 'Lemongo', 'Christ', 'christarmandlemongo@gmail.com', 'password123', 'doctor_christ.jpg', '+33 6 12 34 56 78', '12 Rue de Rivoli, 75001 Paris');

-- Insert Patients (PATIENT)
-- Three patients with specified emails
INSERT INTO PATIENT (id_patient, nom, prenom, email, image_patient, telephone, adresse) VALUES
(1, 'Luna', 'Aurore', 'auroreluna8@gmail.com', 'aurore_luna.jpg', '+33 6 23 45 67 89', '25 Avenue des Champs-Élysées, 75008 Paris'),
(2, 'Nama', 'Luciano', 'lucianonama1234@gmail.com', 'luciano_nama.jpg', '+33 6 34 56 78 90', '8 Boulevard Saint-Germain, 75005 Paris'),
(3, 'Dupont', 'Julie', 'j98655004@gmail.com', 'julie_dupont.jpg', '+33 6 45 67 89 01', '15 Rue du Faubourg Saint-Honoré, 75008 Paris');

-- Insert Appointments (RENDEZVOUS)
-- Covers all statuses, types, urgencies, and dates
INSERT INTO RENDEZVOUS (id_rdv, type_consultation, niveau_urgence, statut, symptomes, id_patient, id_medecin, date_début, date_fin, durée, date_creation, longitude, latitude) VALUES
-- Today (2025-06-11): For rendezvous.php and diagnostics.php
(1, 'hopital', 'normal', 'en_attente', 'Fièvre, toux sèche depuis 3 jours.', 1, 1, '2025-06-11 09:00:00', NULL, '30', '2025-06-10 14:00:00', 2.3522, 48.8566),
(2, 'domicile', 'urgent', 'confirmé', 'Maux de tête intenses, nausées.', 2, 1, '2025-06-11 14:00:00', NULL, '45', '2025-06-10 15:30:00', 2.3510, 48.8500),
(3, 'hopital', 'normal', 'encours', 'Douleurs thoraciques légères.', 3, 1, '2025-06-11 21:00:00', NULL, '30', '2025-06-10 16:00:00', 2.3600, 48.8600),
-- Past (2025-06-09, 2025-06-10): For historique.php
(4, 'hopital', 'normal', 'terminé', 'Douleurs abdominales, brûlures d’estomac.', 1, 1, '2025-06-10 10:00:00', '2025-06-10 10:30:00', '30', '2025-06-09 09:00:00', 2.3500, 48.8550),
(5, 'domicile', 'urgent', 'terminé', 'Essoufflement, sifflements respiratoires.', 2, 1, '2025-06-09 13:00:00', '2025-06-09 13:45:00', '45', '2025-06-08 11:00:00', 2.3400, 48.8450),
(6, 'hopital', 'normal', 'annulé', 'Fatigue persistante, douleurs musculaires.', 3, 1, '2025-06-09 08:00:00', NULL, '30', '2025-06-08 10:00:00', 2.3550, 48.8570),
-- Future (2025-06-12): For rendezvous.php calendar
(7, 'domicile', 'normal', 'confirmé', 'Douleurs articulaires aux genoux.', 3, 1, '2025-06-12 11:00:00', NULL, '30', '2025-06-11 08:00:00', 2.3530, 48.8580);

-- Insert Diagnostics (DIAGNOSTIC)
-- For terminated appointments (id_rdv 4, 5)
INSERT INTO DIAGNOSTIC (id_rdv, contenu, date_diagnostic) VALUES
(4, 'Gastrite aiguë confirmée.', '2025-06-10 10:25:00'),
(5, 'Crise d’asthme modérée.', '2025-06-09 13:30:00');

-- Insert Prescriptions (PRESCRIPTION)
-- For terminated appointments (id_rdv 4, 5)
INSERT INTO PRESCRIPTION (id_rdv, medicament, posologie, duree, conseils, date_creation) VALUES
(4, 'Oméprazole', '20 mg/jour le matin', '7 jours', 'Prendre 30 min avant le petit-déjeuner.', '2025-06-10 10:25:00'),
(4, 'Paracétamol', '1 g toutes les 6h si douleur', '3 jours', 'Ne pas dépasser 4 g/jour.', '2025-06-10 10:25:00'),
(5, 'Salbutamol', '2 bouffées toutes les 4h si besoin', '5 jours', 'Utiliser avec une chambre d’inhalation.', '2025-06-09 13:30:00');