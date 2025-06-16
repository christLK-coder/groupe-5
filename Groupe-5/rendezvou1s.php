<?php 
// fichier: rendezvous.php
session_start();
require_once 'connexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'includes/PHPMailer/src/Exception.php';
require 'includes/PHPMailer/src/PHPMailer.php';
require 'includes/PHPMailer/src/SMTP.php';

function envoyerEmail($destinataire, $sujet, $messageTexte) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'christarmandlemongo@gmail.com';
        $mail->Password = 'guzx iexj pidf tidd'; // Clé d'application
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('christarmandlemongo@gmail.com', 'Dr. Mongo');
        $mail->addAddress($destinataire);

        $mail->isHTML(false);
        $mail->Subject = $sujet;
        $mail->Body    = $messageTexte;

        $mail->send();
    } catch (Exception $e) {
        error_log("Erreur d'envoi de mail: {$mail->ErrorInfo}");
    }
}

function genererMessagePourAction($action, $nom, $prenom, $date = null) {
    switch ($action) {
        case 'annuler':
        case 'refuser':
            return "Cher(e) $prenom $nom,\n\nC'est avec beaucoup de regret que le Dr. Mongo doit annuler votre rendez-vous. Il aurait vraiment souhaité vous recevoir, mais un imprévu l'empêche.\n\nMerci de votre compréhension.";
        case 'terminer':
            return "Cher(e) $prenom $nom,\n\nVotre consultation avec le Dr. Mongo est maintenant terminée. Merci pour votre confiance, et prenez bien soin de vous !";
        case 'reporter':
            return "Cher(e) $prenom $nom,\n\nVotre rendez-vous a été reprogrammé. La nouvelle date est : $date.\n\nMerci de votre compréhension et à bientôt !";
        case 'confirmer':
            return "Cher(e) $prenom $nom,\n\nBonne nouvelle ! Votre rendez-vous avec le très estimé Dr. Mongo est confirmé pour le $date.\n\nNous sommes impatients de vous accueillir.";
        default:
            return "Notification de rendez-vous pour $prenom $nom.";
    }
}

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_rdv = intval($_POST['id_rdv'] ?? 0);

    if ($id_rdv > 0) {
        // Récupérer info patient
        $stmt = $pdo->prepare("SELECT r.*, p.nom, p.prenom, p.email FROM RENDEZVOUS r JOIN PATIENT p ON r.id_patient = p.id_patient WHERE r.id_rdv = ?");
        $stmt->execute([$id_rdv]);
        $rdvInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rdvInfo) {
            $nom = $rdvInfo['nom'];
            $prenom = $rdvInfo['prenom'];
            $email = $rdvInfo['email'];
            $date = $rdvInfo['date_heure'];

            switch ($action) {
                case 'annuler':
                    $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ? AND statut = 'confirmé' AND id_medecin = ?");
                    $stmt->execute([$id_rdv, $id_medecin]);
                    $message = genererMessagePourAction('annuler', $nom, $prenom);
                    envoyerEmail($email, "Annulation de votre rendez-vous", $message);
                    break;
                case 'refuser':
                    $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ? AND statut = 'en_attente' AND id_medecin = ?");
                    $stmt->execute([$id_rdv, $id_medecin]);
                    $message = genererMessagePourAction('refuser', $nom, $prenom);
                    envoyerEmail($email, "Refus de votre rendez-vous", $message);
                    break;
                case 'terminer':
                    $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'terminé', date_fin = NOW() WHERE id_rdv = ? AND statut = 'encours' AND id_medecin = ?");
                    $stmt->execute([$id_rdv, $id_medecin]);
                    $message = genererMessagePourAction('terminer', $nom, $prenom);
                    envoyerEmail($email, "Consultation terminée", $message);
                    break;
                case 'reporter':
                    $nouvelle_date = $_POST['nouvelle_date'] ?? '';
                    $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET date_heure = ?, statut = 'confirmé' WHERE id_rdv = ? AND id_medecin = ?");
                    $stmt->execute([$nouvelle_date, $id_rdv, $id_medecin]);
                    $message = genererMessagePourAction('reporter', $nom, $prenom, $nouvelle_date);
                    envoyerEmail($email, "Votre rendez-vous a été reporté", $message);
                    break;
            }
        }
    }
}

$pdo->prepare("UPDATE RENDEZVOUS SET statut = 'encours', date_début = NOW() WHERE statut = 'confirmé' AND id_medecin = ? AND date_heure <= NOW()")->execute([$id_medecin]);

$stmt = $pdo->prepare("SELECT r.*, p.nom AS nom_patient, p.prenom AS prenom_patient 
                       FROM RENDEZVOUS r 
                       JOIN PATIENT p ON r.id_patient = p.id_patient 
                       WHERE r.id_medecin = ? 
                       ORDER BY r.date_heure DESC");
$stmt->execute([$id_medecin]);
$rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
