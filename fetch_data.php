<?php
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'data' => []];

if (!isset($_SESSION['id_medecin'])) {
    $response['error'] = 'Non authentifié.';
    echo json_encode($response);
    exit;
}

// Ensure the request is for current day's consultations, remove the restrictive 'historique' check
// The diagnostics page expects current day's ongoing/confirmed appointments
// If you need this script to also serve historical data for another page,
// you might introduce a parameter like '?mode=current' or '?mode=historique'
// For this request, we assume it's for the current day's active consultations.

try {
    $id_medecin = $_SESSION['id_medecin'];
    $current_date = date('Y-m-d'); // Get current date in YYYY-MM-DD format

    // Query current day's 'en cours' or 'confirmé' appointments
    $stmt = $pdo->prepare("
        SELECT 
            r.id_rdv,
            r.date_debut,
            r.type_consultation,
            r.niveau_urgence,
            r.symptomes,
            r.statut,
            p.nom AS patient_nom,
            p.prenom AS patient_prenom,
            d.contenu AS diagnostic,
            d.date_diagnostic
        FROM RENDEZVOUS r
        INNER JOIN PATIENT p ON r.id_patient = p.id_patient
        LEFT JOIN DIAGNOSTIC d ON r.id_rdv = d.id_rdv
        WHERE r.id_medecin = :id_medecin 
          AND DATE(r.date_debut) = :current_date
          AND r.statut IN ('en cours', 'confirmé')
        ORDER BY r.date_debut ASC
    ");
    $stmt->execute([
        'id_medecin' => $id_medecin,
        'current_date' => $current_date
    ]);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch prescriptions for each consultation
    foreach ($consultations as &$consultation) {
        $stmt = $pdo->prepare("
            SELECT 
                medicament,
                posologie,
                duree,
                conseils,
                date_creation AS date_creation
            FROM PRESCRIPTION
            WHERE id_rdv = :id_rdv
        ");
        $stmt->execute(['id_rdv' => $consultation['id_rdv']]);
        $consultation['prescriptions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format dates
        $consultation['date_debut'] = $consultation['date_debut'] ? date('d/m/Y H:i', strtotime($consultation['date_debut'])) : 'N/A';
        $consultation['date_diagnostic'] = $consultation['date_diagnostic'] ? date('d/m/Y H:i', strtotime($consultation['date_diagnostic'])) : null;
        foreach ($consultation['prescriptions'] as &$prescription) {
            $prescription['date_creation'] = $prescription['date_creation'] ? date('d/m/Y H:i', strtotime($prescription['date_creation'])) : 'N/A';
        }
    }

    $response['success'] = true;
    $response['consultations'] = $consultations; // Renamed 'data' to 'consultations' for consistency with JS
} catch (PDOException $e) {
    $response['error'] = 'Erreur base de données : ' . $e->getMessage();
    error_log('Erreur PDO : ' . $e->getMessage());
} catch (Exception $e) {
    $response['error'] = 'Erreur serveur : ' . $e->getMessage();
    error_log('Erreur serveur : ' . $e->getMessage());
}

echo json_encode($response);

// Handle POST requests for adding diagnostic/prescription (from diagnostics.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_rdv = $_POST['id_rdv'] ?? null;

    if (!$id_rdv) {
        echo json_encode(['success' => false, 'error' => 'ID de rendez-vous manquant.']);
        exit;
    }

    try {
        if ($action === 'add_diagnostic') {
            $contenu = $_POST['contenu'] ?? '';
            if (empty($contenu)) {
                echo json_encode(['success' => false, 'error' => 'Le contenu du diagnostic est vide.']);
                exit;
            }

            // Check if a diagnostic already exists for this rdv
            $stmt_check = $pdo->prepare("SELECT id_diagnostic FROM DIAGNOSTIC WHERE id_rdv = :id_rdv");
            $stmt_check->execute(['id_rdv' => $id_rdv]);
            $existing_diagnostic = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing_diagnostic) {
                // Update existing diagnostic
                $stmt = $pdo->prepare("UPDATE DIAGNOSTIC SET contenu = :contenu, date_diagnostic = NOW() WHERE id_rdv = :id_rdv");
                $stmt->execute([
                    'contenu' => $contenu,
                    'id_rdv' => $id_rdv
                ]);
            } else {
                // Insert new diagnostic
                $stmt = $pdo->prepare("INSERT INTO DIAGNOSTIC (id_rdv, contenu, date_diagnostic) VALUES (:id_rdv, :contenu, NOW())");
                $stmt->execute([
                    'id_rdv' => $id_rdv,
                    'contenu' => $contenu
                ]);
            }
            echo json_encode(['success' => true, 'message' => 'Diagnostic enregistré avec succès.']);

        } elseif ($action === 'add_prescription') {
            $medicament = $_POST['medicament'] ?? '';
            $posologie = $_POST['posologie'] ?? '';
            $duree = $_POST['duree'] ?? '';
            $conseils = $_POST['conseils'] ?? '';

            if (empty($medicament) || empty($posologie)) {
                echo json_encode(['success' => false, 'error' => 'Médicament et posologie sont requis.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO PRESCRIPTION (id_rdv, medicament, posologie, duree, conseils, date_creation) VALUES (:id_rdv, :medicament, :posologie, :duree, :conseils, NOW())");
            $stmt->execute([
                'id_rdv' => $id_rdv,
                'medicament' => $medicament,
                'posologie' => $posologie,
                'duree' => $duree,
                'conseils' => $conseils
            ]);
            echo json_encode(['success' => true, 'message' => 'Prescription ajoutée avec succès.']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Action invalide.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur de base de données : ' . $e->getMessage()]);
        error_log('PDO Error in POST: ' . $e->getMessage());
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
        error_log('Server Error in POST: ' . $e->getMessage());
    }
    exit;
}
?>

