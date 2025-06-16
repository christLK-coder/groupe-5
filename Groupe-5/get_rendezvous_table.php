<?php
require_once 'connexion.php';

// Récupération des filtres depuis la requête AJAX
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
        WHERE 1 "; // Start with WHERE 1 to easily append conditions

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
    $sql .= "AND DATE(r.date_debut) = :date ";
    $params[':date'] = $filtre_date;
}

$sql .= "ORDER BY r.date_heure DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start(); // Start output buffering
?>
    <?php if (count($rendezvous) === 0): ?>
        <tr><td colspan="8" class="text-center">Aucun rendez-vous trouvé pour les critères sélectionnés.</td></tr>
    <?php else: ?>
        <?php foreach ($rendezvous as $rdv): ?>
            <tr>
                <td><?= htmlspecialchars($rdv['id_rdv']) ?></td>
                <td><?= htmlspecialchars($rdv['prenom_patient'] . " " . $rdv['nom_patient']) ?></td>
                <td><?= htmlspecialchars($rdv['prenom_medecin'] . " " . $rdv['nom_medecin']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($rdv['date_debut'])) ?></td>
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
    <?php endif; ?>
<?php
$html_output = ob_get_clean(); // Get the buffered HTML content
echo $html_output; // Output the HTML
?>