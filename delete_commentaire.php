<?php
require_once("hosto.php");

header('Content-Type: application/json');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("DELETE FROM commentaire WHERE id_commentaire = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Commentaire supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Commentaire non trouvé ou déjà supprimé.']);
        }
    } catch (PDOException $e) {
        error_log("Error deleting comment: " . $e->getMessage()); // Log the error
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données lors de la suppression.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de commentaire invalide.']);
}
?>
