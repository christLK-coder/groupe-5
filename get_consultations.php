<?php
session_start();
require_once 'connexion.php'; // <-- assurez-vous que ce fichier contient votre connexion PDO

if (!isset($_SESSION['id_medecin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$id_medecin = $_SESSION['id_medecin'];

$sql = "SELECT 
            r.id_rdv, r.date_heure, r.statut, r.symptomes,
            r.date_début, r.date_fin,
            p.nom AS nom_patient, p.prenom AS prenom_patient,
            d.id_diagnostic, d.contenu AS contenu_diagnostic,
            pr.id_prescription, pr.medicament, pr.posologie, pr.duree, pr.conseils
        FROM RENDEZVOUS r
        JOIN PATIENT p ON r.id_patient = p.id_patient
        LEFT JOIN DIAGNOSTIC d ON r.id_rdv = d.id_rdv
        LEFT JOIN PRESCRIPTION pr ON r.id_rdv = pr.id_rdv
        WHERE r.id_medecin = :id_medecin
        ORDER BY r.date_heure DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_medecin' => $id_medecin]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];
foreach ($rows as $row) {
    $id = $row['id_rdv'];
    if (!isset($grouped[$id])) {
        $grouped[$id] = [
            'id_rdv' => $id,
            'date_heure' => $row['date_heure'],
            'statut' => $row['statut'],
            'symptomes' => $row['symptomes'],
            'date_début' => $row['date_début'],
            'date_fin' => $row['date_fin'],
            'nom_patient' => $row['nom_patient'],
            'prenom_patient' => $row['prenom_patient'],
            'diagnostic' => [
                'id' => $row['id_diagnostic'],
                'contenu' => $row['contenu_diagnostic']
            ],
            'prescriptions' => []
        ];
    }

    if ($row['id_prescription']) {
        $grouped[$id]['prescriptions'][] = [
            'id' => $row['id_prescription'],
            'medicament' => $row['medicament'],
            'posologie' => $row['posologie'],
            'duree' => $row['duree'],
            'conseils' => $row['conseils']
        ];
    }
}

echo json_encode(array_values($grouped));
