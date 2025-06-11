
<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer tous les rendez-vous
    $stmt = $pdo->query("
        SELECT rv.id_rdv, rv.date_heure, rv.type_consultation AS type, rv.statut,
               p.nom AS patient_nom, p.prenom AS patient_prenom,
               m.nom AS medecin_nom, m.prenom AS medecin_prenom
        FROM rendez_vous rv
        JOIN patient p ON rv.id_patient = p.id_patient
        JOIN medecin m ON rv.id_medecin = m.id_medecin
        ORDER BY rv.date_heure DESC
    ");
    $rendezvous = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F8F9FA;
            color: #343A40;
            padding: 20px;
        }
        h2 {
            color: #007BFF;
        }
        h3 {
            margin-top: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #DEE2E6;
            padding: 12px;
            text-align: center;
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
        .logout {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <h2>Bienvenue, admin !</h2>

    <h3>Liste des rendez-vous</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Médecin</th>
            <th>Date et heure</th>
            <th>Type</th>
            <th>Statut</th>
        </tr>
        <?php if (count($rendezvous) > 0): ?>
            <?php foreach ($rendezvous as $rdv): ?>
                <tr>
                    <td><?= htmlspecialchars($rdv['id_rdv']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($rdv['patient_prenom']) . " " . ucfirst($rdv['patient_nom'])) ?></td>
                    <td><?= htmlspecialchars(ucfirst($rdv['medecin_prenom']) . " " . ucfirst($rdv['medecin_nom'])) ?></td>
                    <td><?= htmlspecialchars($rdv['date_heure']) ?></td>
                    <td><?= htmlspecialchars($rdv['type']) ?></td>
                    <td><?= htmlspecialchars($rdv['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Aucun rendez-vous trouvé.</td></tr>
        <?php endif; ?>
    </table>

    <p><a href="admin_medecins.php">➡ Gérer les médecins</a></p>
    <p><a href="patients.php">➡ Gérer les patients</a></p>

    <p class="logout"><a href="logout.php">Déconnexion</a></p>

</body>
</html>