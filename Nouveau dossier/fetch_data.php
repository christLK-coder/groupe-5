<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$id_medecin = $_SESSION['id_medecin'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch today's consultations
        $stmt = $pdo->prepare("
            SELECT r.id_rdv, r.date_début, r.type_consultation, r.niveau_urgence, r.symptomes,
                   p.nom AS patient_nom, p.prenom AS patient_prenom, p.email AS patient_email,
                   d.contenu AS diagnostic, d.date_diagnostic
            FROM RENDEZVOUS r
            JOIN PATIENT p ON r.id_patient = p.id_patient
            LEFT JOIN DIAGNOSTIC d ON r.id_rdv = d.id_rdv
            WHERE r.id_medecin = ? 
            AND DATE(r.date_début) = CURDATE()
            AND r.statut IN ('confirmé', 'encours')
        ");
        $stmt->execute([$id_medecin]);
        $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch prescriptions for each consultation
        foreach ($consultations as &$consultation) {
            $stmt = $pdo->prepare("
                SELECT medicament, posologie, duree, conseils, date_creation
                FROM PRESCRIPTION
                WHERE id_rdv = ?
            ");
            $stmt->execute([$consultation['id_rdv']]);
            $consultation['prescriptions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $consultation['date_début'] = date('d/m/Y H:i', strtotime($consultation['date_début']));
        }

        echo json_encode(['success' => true, 'consultations' => $consultations]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $id_rdv = filter_var($_POST['id_rdv'] ?? 0, FILTER_VALIDATE_INT);

        if (!$id_rdv) {
            echo json_encode(['success' => false, 'error' => 'ID de rendez-vous invalide']);
            exit;
        }

        if ($action === 'add_diagnostic') {
            $contenu = trim($_POST['contenu'] ?? '');
            if (empty($contenu)) {
                echo json_encode(['success' => false, 'error' => 'Le diagnostic est requis']);
                exit;
            }
            $stmt = $pdo->prepare("
                INSERT INTO DIAGNOSTIC (id_rdv, contenu, date_diagnostic)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE contenu = ?, date_diagnostic = NOW()
            ");
            $stmt->execute([$id_rdv, $contenu, $contenu]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'add_prescription') {
            $medicament = trim($_POST['medicament'] ?? '');
            $posologie = trim($_POST['posologie'] ?? '');
            $duree = trim($_POST['duree'] ?? '');
            $conseils = trim($_POST['conseils'] ?? '');
            if (empty($medicament) || empty($posologie)) {
                echo json_encode(['success' => false, 'error' => 'Médicament et posologie sont requis']);
                exit;
            }
            $stmt = $pdo->prepare("
                INSERT INTO PRESCRIPTION (id_rdv, medicament, posologie, duree, conseils, date_creation)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$id_rdv, $medicament, $posologie, $duree, $conseils]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
        }
    }
} catch (PDOException $e) {
    error_log("Database Error in fetch_data.php: {$e->getMessage()}");
    echo json_encode(['success' => false, 'error' => "Erreur base de données: {$e->getMessage()}"]);
}
?>