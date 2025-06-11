<?php
require_once 'hosto.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $sexe = $_POST['sexe'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $specialite = $_POST['specialite'];
    $service = $_POST['service']; // 🔹 récupération du service
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

    // 🔹 Mise à jour de la requête avec le champ service
    $stmt = $conn->prepare("INSERT INTO medecin (nom, prenom, sexe, email, mot_de_passe, telephone, specialite, service, latitude, longitude) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$nom, $prenom, $sexe, $email, $mot_de_passe, $telephone, $specialite, $service, $latitude, $longitude])) {
        $msg = "Médecin ajouté avec succès.";
    } else {
        $msg = "Erreur lors de l'ajout.";
}
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Médecin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #eef3f7;
            padding: 20px;
        }

        form {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #007bff;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn-submit {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            text-align: center;
            margin-top: 10px;
            color: green;
        }

        .back {
            text-align: center;
            margin-top: 15px;
        }

        .back a {
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>

    <form method="POST">
        <h2><i class="fa-solid fa-user-doctor"></i> Ajouter un Médecin</h2>

        <label>Nom :</label>
        <input type="text" name="nom" required>

        <label>Prénom :</label>
        <input type="text" name="prenom" required>

        <label>Email :</label>
        <input type="email" name="email" required>

        <label>Mot de passe :</label>
        <input type="password" name="mot_de_passe" required>

        <label>Téléphone :</label>
        <input type="text" name="telephone" required>

        <label>Spécialité :</label>
        <input type="text" name="specialite" required>

        <label>Latitude :</label>
        <input type="number" step="any" name="latitude" required>

        <label>Longitude :</label>
        <input type="number" step="any" name="longitude" required>

        <label for="sexe">Sexe :</label>
        <select name="sexe" id="sexe" required>
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
        </select>

        <label for="service">Service :</label>
        <input type="text" name="service"required>
        

        <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Ajouter</button>

        <?php if ($msg): ?>
            <div class="message"><?= $msg ?></div>
        <?php endif; ?>

        <div class="back">
            <a href="gestion_medecins.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
    </form>

</body>
</html>