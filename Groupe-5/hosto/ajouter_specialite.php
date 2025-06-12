<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once("hosto.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_service = $_POST["id_service"];
    $nom_specialite = $_POST["nom_specialite"];
    $description = $_POST["description_specialite"];

    $stmt = $conn->prepare("INSERT INTO specialite (id_service, nom, description_specialite) VALUES (?, ?, ?)");
    if ($stmt->execute([$id_service, $nom_specialite, $description])) {
        $message = "<div style='color:green;'>✅ Spécialité ajoutée avec succès.</div>";
    } else {
        $message = "<div style='color:red;'>❌ Une erreur est survenue lors de l'ajout.</div>";
    }
}

// Récupération des services
$services = $conn->query("SELECT id_service, nom_service FROM services")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Spécialité</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1fff2;
            margin: 40px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #e9fff0;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #b4e1c0;
        }
        h2 {
            text-align: center;
            color: green;
        }
        label {
            font-weight: bold;
            color: #226622;
        }
        input, select, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        button {
            background-color: green;
            color: white;
            border: none;
            font-weight: bold;
        }
    </style>
</head>
<body>


    <div class="container">
        <h2>Ajouter une Spécialité</h2>
        <?= $message ?>
        <form action="" method="POST">
            <label>Service associé :</label>
            <select name="id_service" required>
                <option value="">-- Choisir un service --</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= $s['id_service'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Nom de la Spécialité :</label>
            <input type="text" name="nom_specialite" required>

            <label>Description :</label>
            <textarea name="description_specialite" rows="4"></textarea>

            <button type="submit">Ajouter Spécialité</button>
        </form>
    <a href="dashboard_admin.php" class="retour"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
</body>
</html>
