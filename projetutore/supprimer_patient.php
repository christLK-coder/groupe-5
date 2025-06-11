<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'identifiant du patient est fourni
    $id_patient = $_GET['id'] ?? null;

    if (!$id_patient) {
        throw new Exception("Identifiant du patient manquant.");
    }

    // Supprimer le patient
    $stmt = $pdo->prepare("DELETE FROM patient WHERE id_patient = :id_patient");
    $stmt->execute(['id_patient' => $id_patient]);

    // Rediriger vers la liste des patients après la suppression
    header("Location: patients.php");
    exit();

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>