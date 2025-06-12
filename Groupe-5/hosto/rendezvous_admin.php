<?php
require_once("hosto.php");

// Récupération des filtres s'ils existent
$filtre_nom_patient = $_GET['nom_patient'] ?? '';
$filtre_nom_medecin = $_GET['nom_medecin'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_date = $_GET['date'] ?? '';

// Construction de la requête SQL avec filtres
$sql = "SELECT r.*, 
               p.nom AS nom_patient, p.prenom AS prenom_patient, 
               m.nom AS nom_medecin, m.prenom AS prenom_medecin 
        FROM rendezvous r
        JOIN patient p ON r.id_patient = p.id_patient
        JOIN medecin m ON r.id_medecin = m.id_medecin
        WHERE 1 ";

$params = [];

if (!empty($filtre_nom_patient)) {
    $sql .= "AND CONCAT(p.prenom, ' ', p.nom) LIKE :nom_patient ";
    $params[':nom_patient'] = "%$filtre_nom_patient%";
}
if (!empty($filtre_nom_medecin)) {
    $sql .= "AND CONCAT(m.prenom, ' ', m.nom) LIKE :nom_medecin ";
    $params[':nom_medecin'] = "%$filtre_nom_medecin%";
}
if (!empty($filtre_statut)) {
    $sql .= "AND r.statut = :statut ";
    $params[':statut'] = $filtre_statut;
}
if (!empty($filtre_date)) {
    $sql .= "AND DATE(r.date_heure) = :date ";
    $params[':date'] = $filtre_date;
}

$sql .= "ORDER BY r.date_heure DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rendez-vous programmés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="text-primary mb-4"><i class="fas fa-calendar-check me-2"></i>Rendez-vous programmés</h2>

    <!-- Formulaire de recherche -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
            <input type="text" name="nom_patient" value="<?= htmlspecialchars($filtre_nom_patient) ?>" class="form-control" placeholder="Nom du patient">
        </div>
        <div class="col-md-3">
            <input type="text" name="nom_medecin" value="<?= htmlspecialchars($filtre_nom_medecin) ?>" class="form-control" placeholder="Nom du médecin">
        </div>

        <div class="col-md-2">
            <select name="statut" class="form-select">
                <option value="">-- Statut --</option>
                <?php
                $statuts = ['en_attente', 'encours', 'confirmé', 'terminé', 'annulé'];
                foreach ($statuts as $stat) {
                    $selected = ($filtre_statut === $stat) ? 'selected' : '';
                    echo "<option value='$stat' $selected>$stat</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date" value="<?= htmlspecialchars($filtre_date) ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="fas fa-search"></i> Rechercher</button>
        </div>
    </form>

    <!-- Résultats -->
    <?php if (count($rendezvous) === 0): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Aucun rendez-vous trouvé.
        </div>

        <div class="back">
            <a href="dashboard_admin.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>

    <?php else: ?>
        <div class="table-responsive shadow rounded">
            <table class="table table-bordered table-hover bg-white">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Date/Heure</th>
                        <th>Type</th>
                        <th>Urgence</th>
                        <th>Statut</th>
                        <th>Symptômes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rendezvous as $rdv): ?>
                    <tr>
                        <td><?= $rdv['id_rdv'] ?></td>
                        <td><?= $rdv['prenom_patient'] . " " . $rdv['nom_patient'] ?></td>
                        <td><?= $rdv['prenom_medecin'] . " " . $rdv['nom_medecin'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($rdv['date_heure'])) ?></td>
                        <td><?= $rdv['type_consultation'] === 'domicile' ? '<span class="badge bg-warning text-dark">Domicile</span>' : '<span class="badge bg-info text-dark">Hôpital</span>' ?></td>
                        <td><?= $rdv['niveau_urgence'] === 'urgent' ? '<span class="badge bg-danger">Urgent</span>' : '<span class="badge bg-success">Normal</span>' ?></td>
                        <td>
                            <?php
                            $statut = $rdv['statut'];
                            $badge = match ($statut) {
                                'en_attente' => 'secondary',
                                'encours' => 'warning',
                                'confirmé' => 'info',
                                'terminé' => 'success',
                                'annulé' => 'danger',
                                default => 'dark'
                            };
                            echo "<span class='badge bg-$badge'>$statut</span>";
                            ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($rdv['symptomes'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>


        </div>


    <?php endif; ?>
</div>
</body>
</html>
