<?php
include("hosto.php");

// Get the search term from the AJAX request
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : "";

$sql = "SELECT * FROM patient";
if (!empty($recherche)) {
    $sql .= " WHERE nom LIKE :recherche OR prenom LIKE :recherche";
}
$sql .= " ORDER BY nom ASC, prenom ASC"; // Add ordering for consistency  

$stmt = $conn->prepare($sql);
if (!empty($recherche)) {
    $stmt->execute(['recherche' => "%$recherche%"]);
} else {
    $stmt->execute();
}

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start(); // Start output buffering to capture HTML
?>
    <?php if (empty($patients)): ?>
        <tr><td colspan="6">Aucun patient trouvé.</td></tr>
    <?php else: ?>
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
    <?php endif; ?>
<?php
$html_output = ob_get_clean(); // Get the buffered HTML content
echo $html_output; // Output the HTML
?>