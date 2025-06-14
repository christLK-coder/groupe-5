<?php
require_once("hosto.php");

// Initial PHP variables are now placeholders as AJAX will handle the fetching.
$filtre_nom_patient = $_GET['nom_patient'] ?? '';
$filtre_nom_medecin = $_GET['nom_medecin'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_date = $_GET['date'] ?? '';

// We no longer need to perform the initial SQL query here, as JS will fetch the table.
// $sql = "...";
// $stmt = $conn->prepare($sql);
// $stmt->execute($params);
// $rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <form id="filterForm" class="row g-3 mb-4">
        <div class="col-md-3">
            <input type="text" name="nom_patient" id="nom_patient_filter" value="<?= htmlspecialchars($filtre_nom_patient) ?>" class="form-control" placeholder="Nom du patient">
        </div>
        <div class="col-md-3">
            <input type="text" name="nom_medecin" id="nom_medecin_filter" value="<?= htmlspecialchars($filtre_nom_medecin) ?>" class="form-control" placeholder="Nom du médecin">
        </div>

        <div class="col-md-2">
            <select name="statut" id="statut_filter" class="form-select">
                <option value="">-- Statut --</option>
                <?php
                $statuts = ['en_attente', 'encours', 'confirmé', 'terminé', 'annulé'];
                foreach ($statuts as $stat) {
                    $selected = ($filtre_statut === $stat) ? 'selected' : '';
                    echo "<option value='$stat' $selected>" . ucfirst(str_replace('_', ' ', $stat)) . "</option>"; // Make text nicer
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date" id="date_filter" value="<?= htmlspecialchars($filtre_date) ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Rechercher</button>
        </div>
    </form>

    <div class="table-responsive shadow rounded">
        <table class="table table-bordered table-hover bg-white">
            <thead class="table-primary" >
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
            <tbody id="rendezvousTableBody">
                <tr><td colspan="8" class="text-center">Chargement des rendez-vous...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="dashboard_admin.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-2"></i> Retour au Dashboard</a>
    </div>

</div>

<script>
    // Function to fetch and display rendezvous
    function fetchAndDisplayRendezvous() {
        const nomPatient = document.getElementById('nom_patient_filter').value;
        const nomMedecin = document.getElementById('nom_medecin_filter').value;
        const statut = document.getElementById('statut_filter').value;
        const date = document.getElementById('date_filter').value;

        // Construct URL with query parameters
        const params = new URLSearchParams();
        if (nomPatient) params.append('nom_patient', nomPatient);
        if (nomMedecin) params.append('nom_medecin', nomMedecin);
        if (statut) params.append('statut', statut);
        if (date) params.append('date', date);

        const url = `get_rendezvous_table.php?${params.toString()}`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.text(); // Get the response as plain HTML
            })
            .then(html => {
                document.getElementById('rendezvousTableBody').innerHTML = html;
            })
            .catch(error => {
                console.error('There was a problem fetching rendezvous data:', error);
                document.getElementById('rendezvousTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur lors du chargement des rendez-vous.</td></tr>';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initial load of rendezvous when the page loads
        fetchAndDisplayRendezvous();

        const filterForm = document.getElementById('filterForm');
        // Add event listener for form submission
        filterForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission (page reload)
            fetchAndDisplayRendezvous(); // Call the function to fetch and display filtered data
        });

        // Optional: Add event listeners for input/select changes for "live" filtering
        // Uncomment if you want the table to update as the user types/selects
        document.getElementById('nom_patient_filter').addEventListener('keyup', fetchAndDisplayRendezvous);
        document.getElementById('nom_medecin_filter').addEventListener('keyup', fetchAndDisplayRendezvous);
        document.getElementById('statut_filter').addEventListener('change', fetchAndDisplayRendezvous);
        document.getElementById('date_filter').addEventListener('change', fetchAndDisplayRendezvous);
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>