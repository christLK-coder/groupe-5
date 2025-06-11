<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer tous les patients
    $stmt = $pdo->query("
        SELECT id_patient AS id, 
               CONCAT(nom, ' ', prenom) AS nom_complet, 
               email, 
               telephone, 
               genre 
        FROM patient
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Patients</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F8F9FA;
            color: #343A40;
            margin: 0;
            padding: 20px;
        }

        h2, h3 {
            color: #007BFF;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #DEE2E6;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #E9ECEF;
        }

        tr:hover {
            background-color: #D1E7DD;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        button {
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .logout {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h2>Gestion des Patients</h2>
<p><a href="ajouter_patient.php">➕ Ajouter un patient</a></p>

<table>
    <tr>
        <th>ID</th>
        <th>Nom complet</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Genre</th>
        <th>Actions</th>
    </tr>
    <?php if (count($patients) > 0): ?>
        <?php foreach ($patients as $patient): ?>
            <tr>
                <td><?= htmlspecialchars($patient['id'] ?? '') ?></td>
                <td><?= htmlspecialchars($patient['nom_complet'] ?? '') ?></td>
                <td><?= htmlspecialchars($patient['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($patient['telephone'] ?? '') ?></td>
                <td><?= htmlspecialchars($patient['genre'] ?? '') ?></td>
                <td>
                    <a href="modifier_patient.php?id=<?= htmlspecialchars($patient['id']) ?>">Modifier</a>
                    <a href="supprimer_patient.php?id=<?= htmlspecialchars($patient['id']) ?>" onclick="return confirm('Supprimer ce patient ?')">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6">Aucun patient trouvé.</td></tr>
    <?php endif; ?>
</table>

<p><a href="admin_dashboard.php">⬅ Retour au tableau de bord</a></p>

</body>
</html>