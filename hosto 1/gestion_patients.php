<?php
include("hosto.php");

$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : "";

$sql = "SELECT * FROM patient";
if (!empty($recherche)) {
    $sql .= " WHERE nom LIKE :recherche OR prenom LIKE :recherche";
}

$stmt = $conn->prepare($sql);
if (!empty($recherche)) {
    $stmt->execute(['recherche' => "%$recherche%"]);
} else {
    $stmt->execute();
}

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Patients</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #e6f5e6;
        }
        h2 {
            color: #2e7d32;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #d4edda;
        }
        a {
            text-decoration: none;
            color: #2e7d32;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"] {
            padding: 8px;
            width: 300px;
        }
        button {
            padding: 8px 15px;
            background-color: #2e7d32;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #256d27;
        }
    </style>
</head>
<body>

<h2><i class="fas fa-user-injured"></i> Liste des Patients</h2>

<form method="GET">
    <input type="text" name="recherche" placeholder="Rechercher par nom ou prénom" value="<?= htmlspecialchars($recherche) ?>">
    <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
</form>

<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Sexe</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($patients as $patient): ?>
        <tr>
            <td><?= htmlspecialchars($patient['nom']) ?></td>
            <td><?= htmlspecialchars($patient['prenom']) ?></td>
            <td><?= htmlspecialchars($patient['sexe']) ?></td>
            <td><?= htmlspecialchars($patient['email']) ?></td>
            <td><?= htmlspecialchars($patient['telephone']) ?></td>
            <td>
                <a href="detail_patient.php?id=<?= $patient['id_patient'] ?>"><i class="fas fa-eye"></i> Voir</a> |
                <a href="supprimer_patient.php?id=<?= $patient['id_patient'] ?>" onclick="return confirm('Supprimer ce patient ?')"><i class="fas fa-trash-alt"></i> Supprimer</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<a href="dashboard_admin.php"><i class="fas fa-arrow-left"></i> Retour</a>
</body>
</html>
