<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer l'identifiant du patient
    $id_patient = $_GET['id'] ?? null;

    if (!$id_patient) {
        throw new Exception("Identifiant du patient manquant.");
    }

    // Récupérer les informations du patient
    $stmt = $pdo->prepare("SELECT * FROM patient WHERE id_patient = :id_patient");
    $stmt->execute(['id_patient' => $id_patient]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("Patient non trouvé.");
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Patient</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F8F9FA;
            color: #343A40;
            padding: 20px;
            margin: 0;
        }
        h1 {
            color: #007BFF;
        }
        form {
            background-color: #FFFFFF;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: auto;
        }
        label {
            display: block;
            margin-bottom: 10px;
        }
        input[type="text"],
        input[type="email"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 5px 0 20px;
            border: 1px solid #DEE2E6;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<h1>Modifier Patient</h1>
<form action="update_patient.php" method="POST">
    <input type="hidden" name="id_patient" value="<?= htmlspecialchars($patient['id_patient']) ?>">
    <label for="nom">Nom :</label>
    <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($patient['nom']) ?>" required>

    <label for="prenom">Prénom :</label>
    <input type="text" name="prenom" id="prenom" value="<?= htmlspecialchars($patient['prenom']) ?>" required>

    <label for="email">Email :</label>
    <input type="email" name="email" id="email" value="<?= htmlspecialchars($patient['email']) ?>" required>

    <label for="telephone">Téléphone :</label>
    <input type="text" name="telephone" id="telephone" value="<?= htmlspecialchars($patient['telephone']) ?>">

    <input type="submit" value="Modifier">
</form>

<a href="patients.php" class="back-link">⬅ Retour à la liste des patients</a>

</body>
</html>