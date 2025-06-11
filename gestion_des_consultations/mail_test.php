<?php
// Inclure les fichiers PHPMailer requis
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Créer une instance de PHPMailer
$mail = new PHPMailer(true);

try {
    // Paramètres du serveur
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'christarmandlemongo@gmail.com';
    $mail->Password   = 'guzx iexj pidf tidd'; // Assurez-vous que c'est un mot de passe d'application si la validation en deux étapes est activée
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Utilisez STARTTLS pour le port 587
    $mail->Port       = 587;

    // Option pour désactiver la vérification du certificat SSL (à utiliser avec prudence !)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );

    // Destinataire et expéditeur
    $mail->setFrom('christarmandlemongo@gmail.com', 'Votre Nom');
    $mail->addAddress('christarmandlemongo@gmail.com');

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test Email Sans Composer (Corrigé - SSL Désactivé)';
    $mail->Body    = '<h1>Bonjour!</h1>Cet e-mail est envoyé en utilisant PHPMailer sans Composer, avec la configuration corrigée et la vérification SSL désactivée.';
    $mail->AltBody = 'Bonjour! Cet e-mail est envoyé en utilisant PHPMailer sans Composer, avec la configuration corrigée et la vérification SSL désactivée.';

    $mail->send();
    echo 'E-mail envoyé avec succès.';
} catch (Exception $e) {
    echo "Erreur lors de l'envoi de l'e-mail: {$mail->ErrorInfo}";
}
?>
