<?php
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPatientEmail($patientEmail, $patientName, $subject, $body, $attachmentPath = null) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'christarmandlemongo@gmail.com';
        $mail->Password = 'guzx iexj pidf tidd'; // App-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // SSL options (use with caution)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Sender and recipient
        $mail->setFrom('christarmandlemongo@gmail.com', 'Dr. Christ Lemongo');
        $mail->addAddress($patientEmail, $patientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '
            <h2>Bonjour ' . htmlspecialchars($patientName) . ',</h2>
            <p>' . $body . '</p>
            <p>Pour toute question, contactez-nous à cette adresse.</p>
            <p>Cordialement,<br>Dr. Christ Lemongo</p>
        ';
        $mail->AltBody = strip_tags($body) . "\n\nPour toute question, contactez-nous à cette adresse.\nCordialement,\nDr. Christ Lemongo";

        // Add attachment if provided
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath, 'Rapport_Consultation.pdf');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi email à $patientEmail: {$mail->ErrorInfo}");
        return false;
    }
}
?>