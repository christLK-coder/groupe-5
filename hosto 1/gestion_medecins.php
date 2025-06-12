<?php
require_once 'hosto.php'; // Connexion à la BD

// Récupération des statistiques
$nb_total = $conn->query("SELECT COUNT(*) FROM medecin")->fetchColumn();
$nb_valides = $conn->query("SELECT COUNT(*) FROM medecin WHERE valide = 1")->fetchColumn();
$nb_attente = $conn->query("SELECT COUNT(*) FROM medecin WHERE valide = 0")->fetchColumn();
$nb_indispo = $conn->query("SELECT COUNT(*) FROM medecin WHERE statut_disponible = 0")->fetchColumn();

// Recherche
$filtre = $_GET['filtre'] ?? '';
$query = "SELECT medecin.*, services.nom_service 
          FROM medecin 
          LEFT JOIN services ON medecin.id_service = services.id_service 
          WHERE 
            medecin.nom LIKE :filtre OR 
            medecin.prenom LIKE :filtre OR 
            services.nom_service LIKE :filtre 
          ORDER BY medecin.date_inscription DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['filtre' => "%$filtre%"]);
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Médecins</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef6f9;
            padding: 20px;
        }
        h1 {
            color: rgb(0, 182, 9);
        }
        .stat-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .card {
            background: #ffffff;
            border-left: 5px solid rgb(0, 216, 0);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 200px;
        }
        .btn-ajout {
            background: rgb(0, 182, 9);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-ajout:hover {
            background: rgb(12, 180, 20);
        }
        .search-box {
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box input {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .back a {
            color: rgb(44, 170, 6);
            text-decoration: none;
            font-weight: bold;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #caf0f8;
            color: rgb(58, 177, 3);
        }
        td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .actions a {
            margin-right: 8px;
            text-decoration: none;
            color: rgb(2, 149, 168);
            font-size: 1.1em;
        }
        .actions a:hover {
            color: rgb(40, 185, 3);
        }
    </style>
</head>
<body>
    <h1><i class="fas fa-user-md"></i> Gestion des Médecins</h1>

    <div class="stat-cards">
        <div class="card"><strong>Total</strong><br><?= $nb_total ?> médecins</div>
        <div class="card"><strong>Validés</strong><br><?= $nb_valides ?></div>
        <div class="card"><strong>En attente</strong><br><?= $nb_attente ?></div>
        <div class="card"><strong>Indisponibles</strong><br><?= $nb_indispo ?></div>
    </div>

    <a href="ajouter_medecin.php" class="btn-ajout"><i class="fas fa-plus-circle"></i> Ajouter un Médecin</a>

    <div class="search-box">
        <form method="get">
            <input type="text" name="filtre" placeholder="🔍 Rechercher un médecin..." value="<?= htmlspecialchars($filtre) ?>">
        </form>
        <div class="back">
            <a href="dashboard_admin.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Nom</th>
                <th>Sexe</th>
                <th>Spécialité</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($medecins) === 0): ?>
                <tr><td colspan="8">Aucun médecin trouvé.</td></tr>
            <?php else: ?>
                <?php foreach ($medecins as $m): ?>
                    <tr>
                        <td>
                            <?php if (!empty($m['image_medecin']) && file_exists("New Folder/" . $m['image_medecin'])): ?>
                                <img src="New Folder/<?= htmlspecialchars($m['image_medecin']) ?>" alt="photo">
                            <?php else: ?>
                                <img src="images/default_avatar.png" alt="avatar">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></td>
                        <td><?= htmlspecialchars($m['sexe']) ?></td>
                        <td><?= htmlspecialchars($m['nom_service']) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['telephone']) ?></td>
                        <td>
                            <?= $m['valide'] ? "✅ Validé" : "⏳ En attente" ?><br>
                            <?= $m['statut_disponible'] ? "🟢 Disponible" : "🔴 Indisponible" ?>
                        </td>
                        <td class="actions">
                            <a href="detail_medecin.php?id=<?= $m['id_medecin'] ?>" title="Détails"><i class="fas fa-eye"></i></a>
                            <?php if (!$m['valide']): ?>
                                <a href="valider_medecin.php?id=<?= $m['id_medecin'] ?>" title="Valider"><i class="fas fa-check-circle"></i></a>
                            <?php endif; ?>
                            <a href="modifier_medecin.php?id=<?= $m['id_medecin'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                            <a href="supprimer_medecin.php?id=<?= $m['id_medecin'] ?>" title="Supprimer" onclick="return confirm('Confirmer la suppression ?')"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
        </tbody>
    </table>
</body>
</html>
