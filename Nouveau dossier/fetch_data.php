<?php
session_start();
require_once 'connexion.php';
require_once 'send_email.php'; // Added for email functionality

if (!isset($_SESSION['id_medecin'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$response = ['success' => false, 'data' => [], 'error' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['type'])) {
        $type = $_GET['type'];

        if ($type === 'diagnostics') {
            // Fetch today's consultations (confirmé or encours)
            $today = date('Y-m-d');
            $sql = "
                SELECT 
                    r.id_rdv, r.date_début, r.symptomes, r.type_consultation, r.niveau_urgence, r.statut,
                    p.nom AS patient_nom, p.prenom AS patient_prenom,
                    d.contenu AS diagnostic_contenu, d.date_diagnostic,
                    pr.medicament, pr.posologie, pr.duree AS prescription_duree, pr.conseils, pr.date_creation AS date_prescription
                FROM RENDEZVOUS r
                JOIN PATIENT p ON r.id_patient = p.id_patient
                LEFT JOIN DIAGNOSTIC d ON d.id_rdv = r.id_rdv
                LEFT JOIN PRESCRIPTION pr ON pr.id_rdv = r.id_rdv
                WHERE r.id_medecin = ? AND DATE(r.date_début) = ? AND r.statut IN ('confirmé', 'encours')
                ORDER BY r.date_début ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_medecin, $today]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize data
            $consultations = [];
            foreach ($rows as $row) {
                $key = $row['id_rdv'];
                if (!isset($consultations[$key])) {
                    $consultations[$key] = [
                        'id_rdv' => $row['id_rdv'],
                        'patient_nom' => $row['patient_nom'],
                        'patient_prenom' => $row['patient_prenom'],
                        'date_début' => $row['date_début'] ? date('d/m/Y H:i', strtotime($row['date_début'])) : '-',
                        'symptomes' => $row['symptomes'] ?? 'Non spécifié',
                        'type_consultation' => $row['type_consultation'],
                        'niveau_urgence' => $row['niveau_urgence'],
                        'statut' => $row['statut'],
                        'diagnostic' => $row['diagnostic_contenu'],
                        'date_diagnostic' => $row['date_diagnostic'] ? date('d/m/Y H:i', strtotime($row['date_diagnostic'])) : null,
                        'prescriptions' => []
                    ];
                }
                if ($row['medicament']) {
                    $consultations[$key]['prescriptions'][] = [
                        'medicament' => $row['medicament'],
                        'posologie' => $row['posologie'],
                        'duree' => $row['prescription_duree'],
                        'conseils' => $row['conseils'],
                        'date_prescription' => $row['date_prescription'] ? date('d/m/Y H:i', strtotime($row['date_prescription'])) : null
                    ];
                }
            }
            $response['success'] = true;
            $response['data'] = array_values($consultations);

        } elseif ($type === 'historique') {
            // Fetch completed consultations
            $sql = "
                SELECT 
                    p.nom AS patient_nom, p.prenom AS patient_prenom,
                    r.id_rdv, r.date_début, r.symptomes, r.date_fin,
                    d.contenu AS diagnostic_contenu, d.date_diagnostic,
                    pr.medicament, pr.posologie, pr.duree AS prescription_duree, pr.conseils, pr.date_creation AS date_prescription
                FROM RENDEZVOUS r
                JOIN PATIENT p ON r.id_patient = p.id_patient
                LEFT JOIN DIAGNOSTIC d ON d.id_rdv = r.id_rdv
                LEFT JOIN PRESCRIPTION pr ON pr.id_rdv = r.id_rdv
                WHERE r.id_medecin = ? AND r.statut = 'terminé'
                ORDER BY r.date_début DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_medecin]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize data
            $historique = [];
            foreach ($rows as $row) {
                $key = $row['id_rdv'];
                if (!isset($historique[$key])) {
                    $historique[$key] = [
                        'patient_nom' => $row['patient_nom'],
                        'patient_prenom' => $row['patient_prenom'],
                        'date_début' => $row['date_début'] ? date('d/m/Y H:i', strtotime($row['date_début'])) : '-',
                        'date_fin' => $row['date_fin'] ? date('d/m/Y H:i', strtotime($row['date_fin'])) : '-',
                        'symptomes' => $row['symptomes'] ?? 'Non spécifié',
                        'diagnostic' => $row['diagnostic_contenu'],
                        'date_diagnostic' => $row['date_diagnostic'] ? date('d/m/Y H:i', strtotime($row['date_diagnostic'])) : null,
                        'prescriptions' => []
                    ];
                }
                if ($row['medicament']) {
                    $historique[$key]['prescriptions'][] = [
                        'medicament' => $row['medicament'],
                        'posologie' => $row['posologie'],
                        'duree' => $row['prescription_duree'],
                        'conseils' => $row['conseils'],
                        'date_prescription' => $row['date_prescription'] ? date('d/m/Y H:i', strtotime($row['date_prescription'])) : null
                    ];
                }
            }
            $response['success'] = true;
            $response['data'] = array_values($historique);
        } else {
            $response['error'] = 'Type de requête invalide';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_diagnostic') {
            $id_rdv = filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT);
            $contenu = trim($_POST['contenu']);
            if ($id_rdv && $contenu) {
                $stmt = $pdo->prepare("INSERT INTO DIAGNOSTIC (id_rdv, contenu) VALUES (?, ?)");
                $stmt->execute([$id_rdv, $contenu]);
                $response['success'] = true;
            } else {
                $response['error'] = 'Données invalides pour le diagnostic';
            }
        } elseif ($action === 'add_prescription') {
            $id_rdv = filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT);
            $medicament = trim($_POST['medicament']);
            $posologie = trim($_POST['posologie']);
            $duree = trim($_POST['duree']);
            $conseils = trim($_POST['conseils']);
            if ($id_rdv && $medicament) {
                $stmt = $pdo->prepare("INSERT INTO PRESCRIPTION (id_rdv, medicament, posologie, duree, conseils) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_rdv, $medicament, $posologie, $duree, $conseils]);
                $response['success'] = true;
            } else {
                $response['error'] = 'Données invalides pour la prescription';
            }
        } elseif ($action === 'send_pdf_email') {
            $pdf_path = filter_var($_POST['pdf_path'], FILTER_SANITIZE_STRING);
            $patient_email = filter_var($_POST['patient_email'], FILTER_SANITIZE_EMAIL);
            $patient_name = filter_var($_POST['patient_name'], FILTER_SANITIZE_STRING);
            $id_rdv = filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT);

            if (!$id_rdv || !$patient_email || !$patient_name || !file_exists($pdf_path)) {
                $response['error'] = 'Paramètres manquants ou fichier PDF introuvable';
            } else {
                $subject = 'Votre Rapport de Consultation Médicale';
                $body = 'Veuillez trouver ci-joint le rapport de votre consultation, incluant le diagnostic et les prescriptions associées.';
                
                if (sendPatientEmail($patient_email, $patient_name, $subject, $body, $pdf_path)) {
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Erreur lors de l\'envoi de l\'email';
                }
            }
        } elseif ($action === 'cleanup_pdf') {
            $pdf_path = filter_var($_POST['pdf_path'], FILTER_SANITIZE_STRING);
            $tex_path = str_replace('.pdf', '.tex', $pdf_path);
            
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
            }
            if (file_exists($tex_path)) {
                unlink($tex_path);
            }
            
            $response['success'] = true;
        } else {
            $response['error'] = 'Action inconnue';
        }
    } else {
        $response['error'] = 'Méthode ou paramètres invalides';
    }
} catch (PDOException $e) {
    $response['error'] = 'Erreur de base de données : ' . $e->getMessage();
} catch (Exception $e) {
    $response['error'] = 'Erreur : ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>