<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'D:/wamp64/logs/php_error.log');

require_once 'connexion.php';
require_once 'PHPMailer/send_email.php'; // Assure-toi que ce fichier contient bien la fonction sendPatientEmail()

// Check TCPDF
if (!file_exists('tcpdf/tcpdf.php')) {
    error_log('TCPDF not found at tcpdf/tcpdf.php');
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: TCPDF manquant']);
    exit;
}
require_once 'tcpdf/tcpdf.php';

if (!isset($_SESSION['id_medecin'])) {
    error_log('Unauthorized access to generate_pdf.php');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_rdv'])) {
    error_log('Invalid request to generate_pdf.php');
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

$id_rdv = filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT);
$id_medecin = $_SESSION['id_medecin'];

try {
    if (!$pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }

    // Consultation info
    $stmt = $pdo->prepare("
        SELECT r.date_début, r.type_consultation, r.niveau_urgence, r.symptomes,
               p.nom AS patient_nom, p.prenom AS patient_prenom, p.email AS patient_email,
               d.contenu AS diagnostic, d.date_diagnostic
        FROM RENDEZVOUS r
        JOIN PATIENT p ON r.id_patient = p.id_patient
        LEFT JOIN DIAGNOSTIC d ON r.id_rdv = d.id_rdv
        WHERE r.id_rdv = ? AND r.id_medecin = ?
    ");
    $stmt->execute([$id_rdv, $id_medecin]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$consultation) {
        error_log("Consultation not found for id_rdv: $id_rdv, id_medecin: $id_medecin");
        echo json_encode(['success' => false, 'error' => 'Consultation non trouvée']);
        exit;
    }

    // Prescriptions
    $stmt = $pdo->prepare("
        SELECT medicament, posologie, duree, conseils, date_creation
        FROM PRESCRIPTION
        WHERE id_rdv = ?
    ");
    $stmt->execute([$id_rdv]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Création PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Dr. Christ Lemongo');
    $pdf->SetAuthor('Dr. Christ Lemongo');
    $pdf->SetTitle('Rapport de Consultation Médicale');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setFont('helvetica', '', 12);
    $pdf->SetHeaderData('', 0, 'Dr. Christ Lemongo', date('d/m/Y'), [147, 214, 208], [255, 255, 255]);
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->AddPage();

    // Contenu PDF
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Rapport de Consultation Médicale', 0, 1, 'C');
    $pdf->Ln(10);

    // Infos patient
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Informations du Patient', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 6, "Nom: {$consultation['patient_nom']} {$consultation['patient_prenom']}", 0, 'L');
    $pdf->MultiCell(0, 6, "Date du rendez-vous: " . date('d/m/Y H:i', strtotime($consultation['date_début'])), 0, 'L');
    $pdf->MultiCell(0, 6, "Type de consultation: {$consultation['type_consultation']}", 0, 'L');
    $pdf->MultiCell(0, 6, "Niveau d’urgence: {$consultation['niveau_urgence']}", 0, 'L');
    $pdf->Ln(5);

    // Symptômes
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Symptômes', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 6, $consultation['symptomes'] ?? 'Non spécifiés', 0, 'L');
    $pdf->Ln(5);

    // Diagnostic
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Diagnostic', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $diagnostic = $consultation['diagnostic'] ?? 'Non disponible';
    $date_diag = $consultation['date_diagnostic'] ? date('d/m/Y H:i', strtotime($consultation['date_diagnostic'])) : 'N/A';
    $pdf->MultiCell(0, 6, $diagnostic, 0, 'L');
    $pdf->MultiCell(0, 6, "Posé le: $date_diag", 0, 'L');
    $pdf->Ln(5);

    // Ordonnances
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Prescriptions', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    if (empty($prescriptions)) {
        $pdf->MultiCell(0, 6, 'Aucune prescription.', 0, 'L');
    } else {
        foreach ($prescriptions as $p) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->MultiCell(0, 6, ($p['medicament'] ?? 'N/A') . " (" . ($p['duree'] ?? '') . ")", 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->MultiCell(0, 6, "Posologie: " . ($p['posologie'] ?? 'N/A'), 0, 'L');
            $pdf->MultiCell(0, 6, "Conseils: " . ($p['conseils'] ?? 'N/A'), 0, 'L');
            $pdf->MultiCell(0, 6, "Ajouté le: " . ($p['date_creation'] ? date('d/m/Y H:i', strtotime($p['date_creation'])) : 'N/A'), 0, 'L');
            $pdf->Ln(2);
        }
    }
    $pdf->Ln(10);

    // Signature
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Dr. Christ Lemongo, Médecin', 0, 1, 'R');

    // Sauvegarde PDF
    $unique_id = uniqid('diag_', true);
    $pdf_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "rapport_consultation_{$unique_id}.pdf";
    $pdf->Output($pdf_path, 'F');

    if (!file_exists($pdf_path)) {
        error_log("PDF file not created at: $pdf_path");
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du PDF']);
        exit;
    }

    // Envoi Email avec PDF en pièce jointe
    $patientEmail = $consultation['patient_email'];
    $patientName = $consultation['patient_nom'] . ' ' . $consultation['patient_prenom'];
    $subject = "Votre rapport de consultation médicale";
    $body = "Veuillez trouver ci-joint votre rapport de consultation réalisé le " . date('d/m/Y', strtotime($consultation['date_début'])) . ".";

    $emailSent = sendPatientEmail($patientEmail, $patientName, $subject, $body, $pdf_path);

    if (!$emailSent) {
        echo json_encode(['success' => false, 'error' => 'PDF créé mais erreur lors de l’envoi par email.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'PDF généré et envoyé avec succès.']);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur: ' . $e->getMessage()]);
}
?>
