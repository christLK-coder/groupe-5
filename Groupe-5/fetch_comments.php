<?php
// Active l'affichage de toutes les erreurs PHP pour le débogage (à retirer en production !)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ce fichier ne renverra QUE des données JSON pour les commentaires

header('Content-Type: application/json'); // Indique que la réponse est JSON

require_once 'connexion.php';

// --- 2. Récupération des paramètres LIMIT et OFFSET ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3; // Par défaut 3 commentaires si non spécifié
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; // Par défaut 0 offset si non spécifié

// Prépare la structure de la réponse
$response = [
    'success' => true,
    'comments' => [],
    'hasMore' => false
];

try {

    $stmt = $pdo->prepare("
        SELECT
            c.nom,
            c.contenu,
            c.date_commentaire
        FROM commentaire c
        ORDER BY c.date_commentaire DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formate la date pour un affichage cohérent
    foreach ($comments as &$comment) {
        $comment['date_commentaire'] = (new DateTime($comment['date_commentaire']))->format('d/m/Y à H:i');
    }
    unset($comment); // Annule la référence pour éviter des modifications inattendues

    $response['comments'] = $comments;

    // Vérifie s'il y a plus de commentaires après la récupération actuelle
    // Fait un COUNT(*) total pour une vérification précise
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM commentaire");
    $totalComments = $stmtCount->fetchColumn();

    // Si le nombre total de commentaires est supérieur à (offset + commentaires récupérés), il y en a plus
    if (($offset + count($comments)) < $totalComments) {
        $response['hasMore'] = true;
    }

} catch (PDOException $e) {
    // En cas d'erreur lors de la requête SQL, renvoie un JSON d'erreur
    $response['success'] = false;
    $response['message'] = 'Erreur lors du chargement des commentaires : ' . $e->getMessage();
}

// Renvoie la réponse finale en JSON
echo json_encode($response);
exit;
