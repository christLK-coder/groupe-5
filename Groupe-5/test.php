<?php
session_start();
require_once 'connexion.php'; // Assurez-vous que ce fichier gère correctement la connexion PDO

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg';

// Gestion des requêtes AJAX
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    try {
        $response = [];

        // Récupérer la note moyenne
        $stmt = $pdo->prepare("SELECT AVG(note) as moyenne FROM NOTE WHERE id_medecin = ?");
        $stmt->execute([$id_medecin]);
        $response['note_moyenne'] = round($stmt->fetchColumn() ?: 0, 1);

        // Récupérer le nombre de diagnostics (corrigé pour ne compter que ceux du médecin)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM DIAGNOSTIC d
            JOIN RENDEZVOUS r ON d.id_rdv = r.id_rdv
            WHERE r.id_medecin = ?
        ");
        $stmt->execute([$id_medecin]);
        $response['nb_diagnostics'] = $stmt->fetchColumn();

        // Récupérer les rendez-vous pour aujourd'hui
        $current_date = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT 
                r.id_rdv,
                r.date_debut, 
                r.type_consultation, 
                r.niveau_urgence, 
                r.statut,
                p.nom AS nom_patient,
                p.prenom AS prenom_patient
            FROM RENDEZVOUS r
            JOIN PATIENT p ON r.id_patient = p.id_patient
            WHERE r.id_medecin = :id_medecin 
            AND DATE(r.date_debut) = :current_date
            AND r.statut IN ('confirmé', 'encours')
            ORDER BY r.date_debut ASC
        ");
        $stmt->execute([
            ':id_medecin' => $id_medecin,
            ':current_date' => $current_date
        ]);
        $response['rdv_aujourdhui'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retourner les données en JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } catch (PDOException $e) {
        error_log("Database Error in test.php AJAX: " . $e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Erreur lors du chargement des données']);
        exit();
    }
}
// Données initiales par défaut (ces valeurs seront remplacées par AJAX au chargement)
$note_moyenne = 0;
$nb_diagnostics = 0;
$rdv_aujourdhui = [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
    * {
        font-family: 'Roboto', sans-serif;
    }
    body {
        background-color: #f3fbfa;
        margin: 0;
        font-family: 'Roboto', sans-serif;
        display: flex;
        min-height: 100vh;
    }
    .sidebar {
        width: 250px;
        background-color: #FFFFFF;
        position: fixed;
        top: 0;
        bottom: 0;
        padding: 20px 0;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }
    .sidebar .profile {
        text-align: center;
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    .sidebar .profile img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        border: 2px solid #93d6d0;
    }
    .sidebar .profile h4 {
        margin: 5px 0;
        color: #333;
        font-size: 16px;
    }
    .sidebar .nav {
        padding-top: 20px;
    }
    .sidebar .nav-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: #333;
        text-decoration: none;
        font-size: 15px;
        transition: background-color 0.3s, color 0.3s;
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
        background-color: #93d6d0;
        color: #FFFFFF;
    }
    .sidebar .nav-link .material-icons {
        margin-right: 10px;
        font-size: 20px;
    }
    .main-content {
        margin-left: 250px;
        padding: 30px;
        flex-grow: 1;
        overflow-y: auto;
        background-color: #f3fbfa;
    }
    .card {
        background-color: #FFFFFF;
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        padding: 20px;
    }
    .card h3 {
        color: #333;
        font-size: 18px;
        margin-bottom: 10px;
    }
    .card p {
        color: #93d6d0;
        font-size: 24px;
        font-weight: bold;
    }
    .rdv-section table {
        background-color: #FFFFFF;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        width: 100%;
    }
    .rdv-section th {
        background-color: #93d6d0;
        color: #FFFFFF;
        padding: 12px;
    }
    .rdv-section td {
        padding: 12px;
        color: #333;
    }
    .rdv-section tr:hover {
        background-color: #f3fbfa;
    }
    .star-rating {
        color: #93d6d0;
        font-size: 20px;
    }
    .star-rating .star {
        cursor: default;
    }
    .star-filled::before {
        content: '★';
    }
    .star-half::before {
        content: '★';
        clip-path: polygon(0 0, 50% 0, 50% 100%, 0 100%);
    }
    .star-empty::before {
        content: '☆';
    }
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        .sidebar {
            width: 200px;
        }
        .sidebar .profile img {
            width: 60px;
            height: 60px;
        }
        .sidebar .profile h4 {
            font-size: 14px;
        }
        .sidebar .nav-link {
            font-size: 14px;
            padding: 10px 15px;
        }
        .main-content {
            margin-left: 200px;
            padding: 20px;
        }
        .card {
            padding: 15px;
        }
        .card h3 {
            font-size: 16px;
        }
        .card p {
            font-size: 20px;
        }
        .rdv-section th, .rdv-section td {
            padding: 10px;
            font-size: 14px;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            padding: 10px 0;
        }
        .sidebar .profile h4, .sidebar .nav-link span {
            display: none;
        }
        .sidebar .profile img {
            width: 40px;
            height: 40px;
        }
        .sidebar .nav-link {
            justify-content: center;
            padding: 10px;
        }
        .main-content {
            margin-left: 70px;
            padding: 15px;
        }
        .card {
            margin-bottom: 15px;
            padding: 12px;
        }
        .card h3 {
            font-size: 14px;
        }
        .card p {
            font-size: 18px;
        }
        .rdv-section h2 {
            font-size: 18px;
        }
        .rdv-section table {
            font-size: 12px;
        }
        .rdv-section th, .rdv-section td {
            padding: 8px;
        }
        /* Rendre le tableau scrollable horizontalement sur petits écrans */
        .rdv-section {
            overflow-x: auto;
        }
        .rdv-section table {
            min-width: 600px; /* Assure que le tableau reste lisible */
        }
    }

    @media (max-width: 576px) {
        .sidebar {
            width: 60px;
        }
        .main-content {
            margin-left: 60px;
            padding: 10px;
        }
        .card {
            padding: 10px;
        }
        .card h3 {
            font-size: 12px;
        }
        .card p {
            font-size: 16px;
        }
        .rdv-section h2 {
            font-size: 16px;
        }
        .rdv-section th, .rdv-section td {
            padding: 6px;
            font-size: 11px;
        }
        .alert-container {
            top: 10px;
            right: 10px;
            width: 90%;
            margin: 0 auto;
        }
    }
</style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="New folder/<?= htmlspecialchars($image_medecin) ?>" alt="Profil">
            <h4>Dr. <?= htmlspecialchars($nom . ' ' . $prenom) ?></h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"> <span class="material-icons">house</span>
                <span>Home</span>
            </a>
            <a class="nav-link" href="test.php"> <span class="material-icons">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="rendezvous.php">
                <span class="material-icons">event</span>
                <span>Rendez-vous</span>
            </a>
            <a class="nav-link" href="messages.php">
                <span class="material-icons">chat</span>
                <span>Messages</span>
            </a>
            <a class="nav-link" href="diagnostics.php">
                <span class="material-icons">medical_services</span>
                <span>Diagnostic</span>
            </a>
            <a class="nav-link" href="historique.php">
                <span class="material-icons">history</span>
                <span>Historique</span>
            </a>
            <a class="nav-link active" href="api.php"> <span class="material-icons">map</span>
                <span>Carte RDV</span>
            </a>
            <a class="nav-link" href="profil.php">
                <span class="material-icons">settings</span>
                <span>Paramètres</span>
            </a>
            <a class="nav-link" href="logout.php">
                <span class="material-icons">logout</span>
                <span>Déconnexion</span>
            </a>
        </nav>
    </div>

    <div id="main-content-wrapper">

    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Bienvenue, Dr. <?= htmlspecialchars($prenom) ?></h1>
            <p>Aperçu de vos consultations et activités</p>
            <div class="alert-container" id="alert-container"></div>

            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <h3>Consultations Aujourd'hui</h3>
                        <p id="nb-consultations">Chargement...</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <h3>Nombre de Diagnostics</h3>
                        <p id="nb-diagnostics">Chargement...</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <h3>Note Moyenne</h3>
                        <p id="note-moyenne" class="star-rating" data-rating="0"></p>
                    </div>
                </div>
            </div>

            <div class="rdv-section">
                <h2>Rendez-vous à venir</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date & Heure</th>
                            <th>Type</th>
                            <th>Urgence</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody id="rdv-body">
                        <tr><td colspan="5">Chargement des rendez-vous...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher les étoiles de notation
        function displayStars(rating) {
            const container = document.getElementById('note-moyenne');
            container.innerHTML = '';
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            for (let i = 0; i < 5; i++) {
                const star = document.createElement('span');
                star.className = 'star';
                if (i < fullStars) {
                    star.className += ' star-filled';
                } else if (i === fullStars && hasHalfStar) {
                    star.className += ' star-half';
                } else {
                    star.className += ' star-empty';
                }
                container.appendChild(star);
            }
            container.title = `${rating} / 5`;
            container.dataset.rating = rating;
        }

        // Fonction pour afficher une alerte
        function showAlert(message, type = 'danger') {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        // Fonction pour rafraîchir les données via AJAX
        function refreshDashboard() {
            fetch('test.php?action=refresh', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau ou serveur');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    showAlert(data.error, 'danger');
                    return;
                }

                // Mettre à jour le nombre de consultations
                document.getElementById('nb-consultations').textContent = `${data.rdv_aujourdhui.length} Rendez-vous`;

                // Mettre à jour le nombre de diagnostics
                document.getElementById('nb-diagnostics').textContent = `${data.nb_diagnostics} Diagnostics`;

                // Mettre à jour la note moyenne
                displayStars(parseFloat(data.note_moyenne));

                // Mettre à jour le tableau des rendez-vous
                const rdvBody = document.getElementById('rdv-body');
                rdvBody.innerHTML = '';
                if (data.rdv_aujourdhui.length === 0) {
                    rdvBody.innerHTML = '<tr><td colspan="5">Aucun rendez-vous pour aujourd\'hui.</td></tr>';
                } else {
                    data.rdv_aujourdhui.forEach(rdv => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${rdv.nom_patient} ${rdv.prenom_patient}</td>
                            <td>${new Date(rdv.date_debut).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                            <td>${rdv.type_consultation}</td>
                            <td>${rdv.niveau_urgence || 'Normal'}</td>
                            <td>${rdv.statut}</td>
                        `;
                        rdvBody.appendChild(row);
                    });
                }
            })
            .catch(error => {
                showAlert('Erreur lors du rafraîchissement des données', 'danger');
                console.error('Erreur AJAX:', error);
            });
        }

        // Rafraîchissement automatique toutes les 30 secondes
        setInterval(refreshDashboard, 30000);

        // Appeler refreshDashboard au chargement de la page pour charger les données initiales
        window.addEventListener('load', refreshDashboard);
    </script>
</body>
</html>