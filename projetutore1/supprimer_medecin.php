<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'identifiant du médecin est fourni
    $id_medecin = $_GET['id'] ?? null;

    if (!$id_medecin) {
        throw new Exception("Identifiant du médecin manquant.");
    }

    // Supprimer le médecin
    $stmt = $pdo->prepare("DELETE FROM medecin WHERE id_medecin = :id_medecin");
    $stmt->execute(['id_medecin' => $id_medecin]);

    // Rediriger vers la liste des médecins après la suppression
    header("Location: admin_medecins.php");
    exit();

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>