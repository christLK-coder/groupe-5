<?php
session_start();
require_once 'connexion.php'; // Inclut votre fichier de connexion PDO 

header('Content-Type: application/json');

if (!isset($_SESSION['id_patient']) || !isset($_GET['conversation_id']) || !isset($_GET['last_message_id'])) {
    echo json_encode(['error' => 'Paramètres manquants ou non autorisé']);
    exit;
}

$id_patient = $_SESSION['id_patient'];
$conversation_id = intval($_GET['conversation_id']);
$last_message_id = intval($_GET['last_message_id']);

try {
    $stmt_check = $pdo->prepare("SELECT id_conversation FROM CONVERSATION WHERE id_conversation = ? AND id_patient = ?");
    $stmt_check->execute([$conversation_id, $id_patient]);
    if (!$stmt_check->fetch()) {
        echo json_encode(['error' => 'Conversation non autorisée']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT m.id_message, m.id_expediteur, m.type_expediteur, m.contenu, m.date_message,
               CASE
                   WHEN m.type_expediteur = 'patient' THEN 'Vous'
                   ELSE CONCAT('Dr. ', med.prenom, ' ', med.nom)
               END AS sender_name,
               DATE_FORMAT(m.date_message, '%H:%i') AS formatted_time
        FROM MESSAGE m
        LEFT JOIN MEDECIN med ON m.id_expediteur = med.id_medecin AND m.type_expediteur = 'medecin'
        WHERE m.id_conversation = ? AND m.id_message > ?
        ORDER BY m.date_message ASC
    ");
    $stmt->execute([$conversation_id, $last_message_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>