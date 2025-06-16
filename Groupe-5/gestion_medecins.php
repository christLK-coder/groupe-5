<?php
require_once 'connexion.php';; // Connexion à la BD

// Initial PHP variables are now placeholders or can be removed if strictly using AJAX for these stats.
// We'll keep them as 0 to ensure the page renders initially, then JS will update.
$nb_total = 0;
$nb_valides = 0;
$nb_attente = 0;
$nb_indispo = 0;

// The initial display of doctors will be handled by AJAX now, so we remove the direct query.
// $filtre = $_GET['filtre'] ?? ''; // This will be handled by JS for live search
// $query = "..."; // This query will now be in get_medecins_table.php
// $stmt = $conn->prepare($query);
// $stmt->execute(['filtre' => "%$filtre%"]);
// $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Médecins</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #eef6f9;
        padding: 20px;
    }

    h1 {
        color: rgb(72, 207, 162);
    }

    .stat-cards {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .card {
        background: #ffffff;
        border-left: 5px solid rgb(72, 207, 162);
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        width: 200px;
    }

    .btn-ajout {
        background: rgb(72, 207, 162);
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
        margin-top: 10px;
    }

    .btn-ajout:hover {
        background: rgb(72, 207, 162);
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
        border-radius: 4px;
    }

    .back a {
        color: rgb(72, 207, 162);
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
        color: rgb(72, 207, 162);
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

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        body {
            padding: 15px;
        }
        h1 {
            font-size: 1.8em;
        }
        .stat-cards {
            gap: 15px;
            margin-bottom: 15px;
            margin-top: 30px;
        }
        .card {
            width: 180px;
            padding: 12px 20px;
        }
        .btn-ajout {
            padding: 8px 16px;
            font-size: 0.95em;
        }
        .search-box {
            margin: 10px 0;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        .search-box input {
            width: 100%;
            max-width: 300px;
        }
        table {
            margin-top: 15px;
        }
        th, td {
            padding: 8px;
            font-size: 0.95em;
        }
        td img {
            width: 40px;
            height: 40px;
        }
        .actions a {
            font-size: 1em;
            margin-right: 6px;
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        h1 {
            font-size: 1.5em;
        }
        .stat-cards {
            flex-direction: column;
            gap: 10px;
        }
        .card {
            width: 100%;
            max-width: 300px;
            padding: 10px 15px;
        }
        .btn-ajout {
            padding: 7px 14px;
            font-size: 0.9em;
        }
        .search-box input {
            padding: 7px;
            font-size: 0.9em;
        }
        .back a {
            font-size: 0.9em;
        }
        table {
            font-size: 0.85em;
        }
        th, td {
            padding: 6px;
        }
        td img {
            width: 35px;
            height: 35px;
        }
        .actions a {
            font-size: 0.9em;
            margin-right: 5px;
        }
    }

    @media (max-width: 576px) {
        body {
            padding: 8px;
        }
        h1 {
            font-size: 1.3em;
        }
        .card {
            padding: 8px 12px;
            font-size: 0.85em;
        }
        .btn-ajout {
            padding: 6px 12px;
            font-size: 0.85em;
            width: 100%;
            text-align: center;
        }
        .search-box {
            margin: 8px 0;
            gap: 8px;
        }
        .search-box input {
            padding: 6px;
            font-size: 0.85em;
        }
        .back a {
            font-size: 0.85em;
        }
        table {
            margin-top: 10px;
            font-size: 0.8em;
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        th, td {
            padding: 5px;
        }
        td img {
            width: 30px;
            height: 30px;
        }
        .actions a {
            font-size: 0.85em;
            margin-right: 4px;
        }
    }
</style>
</head>
<body>
    <h1><i class="fas fa-user-md"></i> Gestion des Médecins</h1>

    <div class="stat-cards">
        <div class="card"><strong>Total</strong><br><span id="nb_total"><?= $nb_total ?></span> médecins</div>
        <div class="card"><strong>Validés</strong><br><span id="nb_valides"><?= $nb_valides ?></span></div>
        <div class="card"><strong>En attente</strong><br><span id="nb_attente"><?= $nb_attente ?></span></div>
        <div class="card"><strong>Indisponibles</strong><br><span id="nb_indispo"><?= $nb_indispo ?></span></div>
    </div>

    <a href="ajouter_medecin.php" class="btn-ajout"><i class="fas fa-plus-circle"></i> Ajouter un Médecin</a>

    <div class="search-box">
        <form id="searchForm">
            <input type="text" name="filtre" id="searchInput" placeholder="🔍 Rechercher un médecin..." value="">
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
        <tbody id="medecinsTableBody">
            </tbody>
    </table>

    <script>
        // Function to fetch and update statistics
        function fetchAndDisplayStats() {
            fetch('get_medecin_stats.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('nb_total').textContent = data.nb_total;
                    document.getElementById('nb_valides').textContent = data.nb_valides;
                    document.getElementById('nb_attente').textContent = data.nb_attente;
                    document.getElementById('nb_indispo').textContent = data.nb_indispo;
                })
                .catch(error => console.error('Error fetching stats:', error));
        }

        // Function to fetch and update doctors table
        function fetchAndDisplayMedecins(filter = '') {
            fetch(`get_medecins_table.php?filtre=${encodeURIComponent(filter)}`)
                .then(response => response.text()) // Get response as text (HTML)
                .then(html => {
                    document.getElementById('medecinsTableBody').innerHTML = html;
                })
                .catch(error => console.error('Error fetching doctors table:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initial load of statistics
            fetchAndDisplayStats();

            // Initial load of doctors table (empty filter)
            fetchAndDisplayMedecins();

            // Live search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('keyup', function() {
                fetchAndDisplayMedecins(this.value);
            });

            // Prevent form submission on enter key
            const searchForm = document.getElementById('searchForm');
            searchForm.addEventListener('submit', function(event) {
                event.preventDefault();
            });
        });
    </script>
</body>
</html>