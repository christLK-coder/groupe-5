<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once("hosto.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom_service = $_POST["nom_service"];
    $description = $_POST["description"];
    $image_service = "";

    // Gestion de l’image
    if (isset($_FILES["image_service"]) && $_FILES["image_service"]["error"] === 0) {
        $target_dir = "uploads/services/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = basename($_FILES["image_service"]["name"]);
        $target_file = $target_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES["image_service"]["tmp_name"], $target_file)) {
            $image_service = $target_file;
        }
    }

    // Insertion SQL
    $stmt = $conn->prepare("INSERT INTO services (nom_service, description, image_service) VALUES (?, ?, ?)");
    if ($stmt->execute([$nom_service, $description, $image_service])) {
        $message = "<div style='color:green;'>✅ Service ajouté avec succès.</div>";
    } else {
        $message = "<div style='color:red;'>❌ Une erreur est survenue lors de l'ajout.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Service</title>
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
        input, textarea, button {
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
        <h2>Ajouter un Service</h2>
        <?= $message ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <label>Nom du Service :</label>
            <input type="text" name="nom_service" required>

            <label>Description :</label>
            <textarea name="description" rows="4"></textarea>

            <label>Image du Service :</label>
            <input type="file" name="image_service">

            <button type="submit">Ajouter Service</button>
        </form>

    <a href="dashboard_admin.php" class="retour"><i class="fas fa-arrow-left"></i> Retour</a>

    </div>
</body>
</html>
