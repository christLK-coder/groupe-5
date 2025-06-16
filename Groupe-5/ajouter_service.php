<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once 'connexion.php';

$message = "";

// Insertion d’un service
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nom_service"])) {
    $nom_service = $_POST["nom_service"];
    $description = $_POST["description"];
    $image_service = "";

    // Upload image
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

    $stmt = $pdo->prepare("INSERT INTO services (nom_service, description, image_service) VALUES (?, ?, ?)");
    if ($stmt->execute([$nom_service, $description, $image_service])) {
        $message = "<div style='color:green;'>✅ Service ajouté avec succès.</div>";
    } else {
        $message = "<div style='color:red;'>❌ Une erreur est survenue lors de l'ajout.</div>";
    }
}

// Recherche
$search = "";
if (isset($_GET["search"])) {
    $search = $_GET["search"];
    $stmt = $pdo->prepare("SELECT * FROM services WHERE nom_service LIKE ?");
    $stmt->execute(["%$search%"]);
    $services = $stmt->fetchAll();
} else {
    $services = $pdo->query("SELECT * FROM services")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f1fff2;
        margin: 40px;
    }
    .container {
        max-width: 900px;
        margin: auto;
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 0 10px #b4e1c0;
    }
    h2 {
        text-align: center;
        color: rgb(59, 197, 151);
    }
    label {
        font-weight: bold;
        color: rgb(59, 197, 151);
    }
    input, textarea, select, button {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    button {
        background-color: rgb(59, 197, 151);
        color: white;
        font-weight: bold;
        cursor: pointer;
    }
    .search-bar {
        margin: 20px 0;
        display: flex;
        justify-content: flex-end;
    }
    .search-bar input {
        width: 300px;
        border: 1px solid #aaa;
        border-radius: 5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
    }
    th, td {
        padding: 12px;
        border: 1px solid #ccc;
        text-align: left;
    }
    th {
        background-color: #bbf3d4;
        color: rgb(59, 197, 151);
    }
    img {
        width: 60px;
        height: auto;
        border-radius: 6px;
    }
    .btn {
        padding: 5px 10px;
        margin-right: 5px;
        text-decoration: none;
        border-radius: 5px;
    }
    .btn-warning {
        background-color: orange;
        color: white;
    }
    .btn-danger {
        background-color: crimson;
        color: white;
    }
    .back a {
        text-decoration: none;
        color: rgb(59, 197, 151);
        font-weight: bold;
        display: inline-block;
        margin-top: 10px;
        margin-bottom: 50px;
    }
    .card-header {
        background-color: rgb(59, 197, 151);
        color: white;
        font-weight: bold;
        padding: 10px;
        border-radius: 5px 5px 0 0;
    }

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        body {
            margin: 20px;
        }
        .container {
            padding: 20px;
            max-width: 100%;
        }
        h2 {
            font-size: 1.5em;
        }
        .search-bar {
            justify-content: flex-start;
            margin: 15px 0;
        }
        .search-bar input {
            width: 100%;
            max-width: 250px;
        }
        th, td {
            padding: 10px;
            font-size: 0.95em;
        }
        img {
            width: 50px;
        }
        .btn {
            padding: 4px 8px;
            font-size: 0.9em;
        }
        .back a {
            font-size: 0.95em;
        }
    }

    @media (max-width: 768px) {
        body {
            margin: 15px;
        }
        .container {
            padding: 15px;
        }
        h2 {
            font-size: 1.3em;
        }
        input, textarea, select, button {
            padding: 8px;
            font-size: 0.9em;
        }
        .search-bar {
            margin: 10px 0;
        }
        .search-bar input {
            max-width: 100%;
        }
        table {
            margin-top: 15px;
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        th, td {
            padding: 8px;
            font-size: 0.85em;
        }
        img {
            width: 40px;
        }
        .btn {
            padding: 3px 6px;
            font-size: 0.85em;
        }
        .card-header {
            padding: 8px;
            font-size: 0.9em;
        }
        .back a {
            font-size: 0.9em;
            margin-bottom: 30px;
        }
    }

    @media (max-width: 576px) {
        body {
            margin: 10px;
        }
        .container {
            padding: 10px;
        }
        h2 {
            font-size: 1.1em;
        }
        input, textarea, select, button {
            padding: 6px;
            font-size: 0.85em;
        }
        button {
            font-size: 0.9em;
        }
        .search-bar {
            margin: 8px 0;
        }
        .search-bar input {
            padding: 6px;
        }
        th, td {
            padding: 6px;
            font-size: 0.8em;
        }
        img {
            width: 30px;
        }
        .btn {
            padding: 2px 5px;
            font-size: 0.8em;
        }
        .card-header {
            padding: 6px;
            font-size: 0.85em;
        }
        .back a {
            font-size: 0.85em;
            margin-bottom: 20px;
        }
    }
</style>
</head>
<body> 
    <div class="back">
            <a href="dashboard_admin.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
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

        <!-- Barre de recherche -->
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="🔍 Rechercher un service..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Rechercher</button>
            </form>
        </div>

        <!-- Liste des services -->
        <div class="card">
            <div class="card-header">Liste des Services</div>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['nom_service']) ?></td>
                                <td><?= htmlspecialchars($s['description']) ?></td>
                                <td>
                                    <?php if (!empty($s['image_service'])): ?>
                                        <img src="<?= htmlspecialchars($s['image_service']) ?>" alt="image">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="modifier_service.php?id=<?= $s['id_service'] ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="supprimer_service.php?id=<?= $s['id_service'] ?>" onclick="return confirm('Supprimer ce service ?')" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Aucun service trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


    </div>
</body>
</html>
