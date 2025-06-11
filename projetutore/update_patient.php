<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si les données du formulaire sont envoyées
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_patient = $_POST['id_patient'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $telephone = $_POST['telephone'];

        // Mettre à jour les informations du patient
        $stmt = $pdo->prepare("
            UPDATE patient 
            SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone 
            WHERE id_patient = :id_patient
        ");
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'id_patient' => $id_patient,
        ]);

        // Rediriger vers la liste des patients après la mise à jour
        header("Location: patients.php");
        exit();
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mise à Jour du Patient</title>
</head>
<body>
    <h1>Mise à Jour du Patient</h1>
    <p>Les informations ont été mises à jour avec succès.</p>
    <a href="patients.php">Retour à la liste des patients</a>
</body>
</html>