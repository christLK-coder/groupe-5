<?php
session_start();
require_once 'connexion.php';
require_once 'libs/tcpdf/tcpdf.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'D:/wamp64/logs/php_error.log');

if (!isset($_SESSION['id_medecin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if (!isset($_POST['id_rdv']) || !filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'error' => 'ID de consultation invalide']);
    exit;
}

$id_rdv = $_POST['id_rdv'];
$id_medecin = $_SESSION['id_medecin'];

try {
    // Fetch consultation details
    $stmt = $pdo->prepare("
        SELECT r.date_debut, r.type_consultation, r.niveau_urgence, r.symptomes,
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
        echo json_encode(['success' => false, 'error' => 'Consultation non trouvée']);
        exit;
    }

    // Fetch prescriptions
    $stmt = $pdo->prepare("
        SELECT medicament, posologie, duree, conseils, date_creation
        FROM PRESCRIPTION
        WHERE id_rdv = ?
    ");
    $stmt->execute([$id_rdv]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Dr. Christ Lemongo');
    $pdf->SetAuthor('Dr. Christ Lemongo');
    $pdf->SetTitle('Rapport de Consultation Médicale');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setFont('helvetica', '', 12);

    // Header
    $pdf->SetHeaderData('', 0, 'Dr. Christ Lemongo', date('d/m/Y'), [147, 214, 208], [255, 255, 255]);
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);

    // Add page
    $pdf->AddPage();

    // Content
    $prescriptions_html = empty($prescriptions) ? '<p><em>Aucune prescription.</em></p>' : '<ul>' . implode('', array_map(function($p) {
        return "<li><strong>{$p['medicament']}</strong> ({$p['duree']})<br>Posologie: {$p['posologie']}<br>Conseils: {$p['conseils']}<br>Ajouté le: " . ($p['date_creation'] ? date('d/m/Y H:i', strtotime($p['date_creation'])) : 'N/A') . "</li>";
    }, $prescriptions)) . '</ul>';

    $content = "
        <h2>Rapport de Consultation Médicale</h2>
        <h4>Patient: {$consultation['patient_nom']} {$consultation['patient_prenom']}</h4>
        <p><strong>Date:</strong> " . date('d/m/Y H:i', strtotime($consultation['date_debut'])) . "</p>
        <p><strong>Type:</strong> {$consultation['type_consultation']}</p>
        <p><strong>Urgence:</strong> {$consultation['niveau_urgence']}</p>
        <p><strong>Symptômes:</strong> " . (empty($consultation['symptomes']) ? 'Non spécifiés' : $consultation['symptomes']) . "</p>
        <p><strong>Diagnostic:</strong> " . (empty($consultation['diagnostic']) ? 'Non disponible' : $consultation['diagnostic']) . "<br><small>Posé le: " . ($consultation['date_diagnostic'] ? date('d/m/Y H:i', strtotime($consultation['date_diagnostic'])) : 'N/A') . "</small></p>
        <p><strong>Prescriptions:</strong> {$prescriptions_html}</p>
        <p style='text-align: right;'>Dr. Christ Lemongo, Médecin</p>
    ";
    $pdf->writeHTML($content, true, false, true, false, '');

    // Save PDF
    $unique_id = uniqid('consult_', true);
    $pdf_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "consultation_{$unique_id}.pdf";
    $pdf->Output($pdf_path, 'F');

    if (!file_exists($pdf_path)) {
        error_log("PDF not created at: $pdf_path");
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du PDF']);
        exit;
    }

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'christarmandlemongo@gmail.com';
        $mail->Password = 'guzx iexj pidf tidd'; // Replace with Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

        $mail->setFrom('christarmandlemongo@gmail.com', 'Dr. Christ Lemongo');
        $mail->addAddress($consultation['patient_email'], $consultation['patient_nom'] . ' ' . $consultation['patient_prenom']);
        $mail->addAttachment($pdf_path, 'Rapport_Consultation.pdf');
        $mail->isHTML(true);
        $mail->Subject = 'Votre Rapport de Consultation Médicale';
        $mail->Body = "Bonjour {$consultation['patient_nom']},<br><br>Veuillez trouver en pièce jointe votre rapport de consultation.<br><br>Bien cordialement,<br>Dr. Christ Lemongo";

        $mail->send();

        // Cleanup
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }

        echo json_encode(['success' => true, 'message' => "PDF généré et envoyé à {$consultation['patient_email']}"]);
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$e->getMessage()}");
        echo json_encode(['success' => false, 'error' => "Erreur lors de l'envoi du mail: {$e->getMessage()}"]);
    }
} catch (PDOException $e) {
    error_log("Database Error: {$e->getMessage()}");
    echo json_encode(['success' => false, 'error' => "Erreur base de données: {$e->getMessage()}"]);
} catch (Exception $e) {
    error_log("General Error: {$e->getMessage()}");
    echo json_encode(['success' => false, 'error' => "Erreur générale: {$e->getMessage()}"]);
}
?>