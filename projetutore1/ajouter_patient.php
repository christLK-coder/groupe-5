<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $genre = $_POST['genre'] ?? '';

    // Validation des données
    if ($nom && $prenom && filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^\d{10}$/', $telephone) && $genre) {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO patient (nom, prenom, email, telephone, genre) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $telephone, $genre]);

            $success = "Patient ajouté avec succès.";
            // Redirection vers la liste des patients après l'ajout
            header("Location: patients.php");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs correctement.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un patient</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F8F9FA;
            color: #343A40;
            padding: 20px;
            margin: 0;
        }
        h2 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 20px;
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
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 5px 0 20px;
            border: 1px solid #DEE2E6;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            border-color: #007BFF;
            outline: none;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<h2>Ajouter un patient</h2>
<div class="message">
    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php elseif ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>
</div>

<form method="post">
    <label>Nom :</label>
    <input type="text" name="nom" required>

    <label>Prénom :</label>
    <input type="text" name="prenom" required>

    <label>Email :</label>
    <input type="email" name="email" required>

    <label>Téléphone :</label>
    <input type="text" name="telephone" required placeholder="10 chiffres sans espaces">

    <label>Genre :</label>
    <select name="genre" required>
        <option value="">--Sélectionnez--</option>
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
    </select>

    <button type="submit">Ajouter</button>
</form>

<br>
<a href="patients.php" style="display: block; text-align: center; color: #007BFF;">⬅ Retour à la liste des patients</a>

</body>
</html>