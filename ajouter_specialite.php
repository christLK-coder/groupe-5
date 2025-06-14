<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once("hosto.php");

$message = "";

// Traitement ajout spécialité
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nom_specialite"])) {
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

// Récupération des spécialités avec recherche
$search = $_GET['q'] ?? '';
$sql = "SELECT s.*, sv.nom_service FROM specialite s 
        JOIN services sv ON s.id_service = sv.id_service";

if (!empty($search)) {
    $sql .= " WHERE s.nom LIKE :search OR s.description_specialite LIKE :search OR sv.nom_service LIKE :search";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['search' => '%' . $search . '%']);
    $specialites = $stmt->fetchAll();
} else {
    $specialites = $conn->query($sql)->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Spécialité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f0fdf5;
        margin: 40px;
    }
    .container {
        max-width: 850px;
        margin: auto;
        background: #ffffff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 0 15px #c0e7cb;
    }
    h2 {
        text-align: center;
        color: rgb(59, 197, 151);
    }
    label {
        font-weight: bold;
        color: rgb(59, 197, 151);
    }
    input, select, textarea, button {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border-radius: 6px;
        border: 1px solid #ccc;
    }
    button {
        background-color: rgb(59, 197, 151);
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    button:hover {
        background-color: rgb(40, 168, 125);
    }
    .search-box {
        margin-bottom: 15px;
    }
    .search-box input {
        width: calc(100% - 100px);
        display: inline-block;
    }
    .search-box button {
        width: 90px;
        display: inline-block;
        margin-left: 10px;
        background-color: rgb(59, 197, 151);
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
    }
    .table th, .table td {
        border: 1px solid #bde5c8;
        padding: 10px;
        text-align: center;
    }
    .table thead {
        background-color: #def7e0;
    }
    .actions a {
        margin: 0 5px;
        color: white;
        padding: 6px 10px;
        border-radius: 5px;
        text-decoration: none;
    }
    .btn-warning {
        background-color: #ffc107;
    }
    .btn-danger {
        background-color: #dc3545;
    }
    .back {
        margin-bottom: 50px;
    }
    .back a {
        text-decoration: none;
        color: rgb(59, 197, 151);
        font-weight: bold;
    }
    .no-result {
        text-align: center;
        color: #999;
        font-style: italic;
        margin-top: 10px;
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
        input, select, textarea, button {
            padding: 10px;
            font-size: 0.95em;
        }
        .search-box {
            margin-bottom: 10px;
        }
        .search-box input {
            width: calc(100% - 90px);
        }
        .search-box button {
            width: 80px;
            padding: 10px;
            font-size: 0.95em;
        }
        .table th, .table td {
            padding: 8px;
            font-size: 0.95em;
        }
        .actions a {
            padding: 5px 8px;
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
        input, select, textarea, button {
            padding: 8px;
            font-size: 0.9em;
        }
        .search-box input {
            width: 100%;
            margin-bottom: 8px;
        }
        .search-box button {
            width: 100%;
            margin-left: 0;
            padding: 8px;
            font-size: 0.9em;
        }
        .table {
            margin-top: 15px;
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        .table th, .table td {
            padding: 6px;
            font-size: 0.85em;
        }
        .actions a {
            padding: 4px 6px;
            font-size: 0.85em;
        }
        .back {
            margin-bottom: 30px;
        }
        .back a {
            font-size: 0.9em;
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
        input, select, textarea, button {
            padding: 6px;
            font-size: 0.85em;
        }
        .search-box {
            margin-bottom: 8px;
        }
        .search-box input, .search-box button {
            padding: 6px;
            font-size: 0.85em;
        }
        .table th, .table td {
            padding: 5px;
            font-size: 0.8em;
        }
        .actions a {
            padding: 3px 5px;
            font-size: 0.8em;
        }
        .back {
            margin-bottom: 20px;
        }
        .back a {
            font-size: 0.85em;
        }
    }
</style>
</head>
<body> 
    <div class="back">
        <a href="dashboard_admin.php"><i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord</a>
    </div>

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

    <!-- Liste des spécialités -->
    <hr>
    <h2>Liste des Spécialités</h2>

    <!-- Barre de recherche -->
    <form method="GET" class="search-box">
        <input type="text" name="q" placeholder="Rechercher une spécialité..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th>Nom</th>
            <th>Description</th>
            <th>Service associé</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (count($specialites) === 0): ?>
            <tr>
                <td colspan="4" class="no-result">Aucune spécialité trouvée.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($specialites as $sp): ?>
                <tr>
                    <td><?= htmlspecialchars($sp['nom']) ?></td>
                    <td><?= htmlspecialchars($sp['description_specialite']) ?></td>
                    <td><?= htmlspecialchars($sp['nom_service']) ?></td>
                    <td class="actions">
                        <a href="modifier_specialite.php?id=<?= $sp['id_specialite'] ?>" class="btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="supprimer_specialite.php?id=<?= $sp['id_specialite'] ?>" onclick="return confirm('Supprimer cette spécialité ?')" class="btn-danger">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>


</div>
</body>
</html>
