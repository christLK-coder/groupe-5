<?php
// get_medecins_table.php

require_once 'connexion.php'; // Ensure your PDO connection ($pdo) is available

// Sanitize the input filter term
// Using filter_input for better security and to get GET parameter
$filtre = filter_input(INPUT_GET, 'filtre', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// Construct the base query
$query = "SELECT medecin.*, services.nom_service 
          FROM medecin 
          LEFT JOIN services ON medecin.id_service = services.id_service";

$params = []; // Initialize an array to hold parameters for the prepared statement
$where_clauses = []; // Array to build up dynamic WHERE conditions

// Add WHERE clauses if a filter is provided
if (!empty($filtre)) {
    // Add conditions for each column you want to filter
    $where_clauses[] = "medecin.nom LIKE :filtre_nom";
    $params[':filtre_nom'] = "%$filtre%";

    $where_clauses[] = "medecin.prenom LIKE :filtre_prenom";
    $params[':filtre_prenom'] = "%$filtre%";

    $where_clauses[] = "services.nom_service LIKE :filtre_service";
    $params[':filtre_service'] = "%$filtre%";
    
    // Combine the WHERE clauses with OR
    $query .= " WHERE (" . implode(" OR ", $where_clauses) . ")";
}

// Add the ORDER BY clause
$query .= " ORDER BY medecin.date_inscription DESC";

try {
    // Prepare the SQL statement
    $stmt = $pdo->prepare($query);
    
    // Execute the statement with the prepared parameters
    // The keys in $params now explicitly match the named placeholders in the query
    $stmt->execute($params); 
    
    // Fetch all results as an associative array
    $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error for debugging purposes (check your PHP error logs)
    error_log("Database error in get_medecins_table.php: " . $e->getMessage());
    // Provide a generic error message to the user
    $medecins = []; // Ensure $medecins is an empty array to prevent further errors
    echo '<tr><td colspan="8" style="color:red; text-align:center;">Une erreur est survenue lors du chargement des médecins.</td></tr>';
    exit(); // Stop execution if there's a critical database error
}

// Start output buffering to capture the HTML table rows
ob_start(); 
?>
<?php if (empty($medecins)): // Use empty() for a more robust check for an empty array ?>
    <tr><td colspan="8">Aucun médecin trouvé.</td></tr>
<?php else: ?>
    <?php foreach ($medecins as $m): ?>
        <tr>
            <td>
                <?php if (!empty($m['image_medecin'])): ?>
                    <img src="New folder/<?= htmlspecialchars($m['image_medecin']) ?>" alt="Photo de <?= htmlspecialchars($m['nom']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <img src="images/default_avatar.png" alt="Avatar par défaut" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></td>
            <td><?= htmlspecialchars($m['sexe']) ?></td>
            <td><?= htmlspecialchars($m['nom_service']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['telephone']) ?></td>
            <td>
                <?= $m['valide'] ? "<span style='color:green;'>✅ Validé</span>" : "<span style='color:orange;'>⏳ En attente</span>" ?><br>
                <?= $m['statut_disponible'] ? "<span style='color:green;'>🟢 Disponible</span>" : "<span style='color:red;'>🔴 Indisponible</span>" ?>
            </td>
            <td class="actions">
                <a href="detail_medecin.php?id=<?= htmlspecialchars($m['id_medecin']) ?>" title="Détails" class="btn btn-info"><i class="fas fa-eye"></i></a>
                <?php if (!$m['valide']): ?>
                    <a href="valider_medecin.php?id=<?= htmlspecialchars($m['id_medecin']) ?>" title="Valider" class="btn btn-success"><i class="fas fa-check-circle"></i></a>
                <?php endif; ?>
                <a href="modifier_medecin.php?id=<?= htmlspecialchars($m['id_medecin']) ?>" title="Modifier" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                <a href="supprimer_medecin.php?id=<?= htmlspecialchars($m['id_medecin']) ?>" title="Supprimer" onclick="return confirm('Confirmer la suppression ?')" class="btn btn-danger"><i class="fas fa-trash-alt"></i></a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
<?php
$html_output = ob_get_clean(); // Get the buffered output
echo $html_output; // Output the HTML
?>