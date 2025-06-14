<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// We no longer need the direct database calls here, as AJAX will handle it.
// require_once("hosto.php"); // This line can be removed or kept if other parts of the page use it.

// The PHP variables below are now placeholders or can be removed if strictly using AJAX for these stats.
// For now, we'll keep them as they are and let JS update the content after page load.
$nbServices = 0; // Will be updated by AJAX
$nbSpecialite = 0; // Will be updated by AJAX
$nbPatients = 0; // Will be updated by AJAX
$nbMedecins = 0; // Will be updated by AJAX
$nbRdvToday = 0; // Will be updated by AJAX
$nbCommentaires = 0; // Will be updated by AJAX

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Hosto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', sans-serif;
    }

    body {
        display: flex;
        height: 100vh;
        background-color: #f4f9ff;
    }

    .sidebar {
        width: 250px;
        background: rgb(72, 207, 162);
        color: white;
        padding: 20px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 0 6px rgba(54, 48, 48, 0.5);
        transition: width 0.3s;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
        color: white;
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        margin: 12px 0;
        padding: 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        transition: 0.2s;
    }

    .sidebar a:hover {
        background-color: rgb(47, 189, 142);
    }

    .sidebar i {
        margin-right: 10px;
    }

    .main {
        flex: 1;
        padding: 30px;
        overflow-y: auto;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .header h1 {
        color: rgb(72, 207, 162);
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        text-align: center;
    }

    .card i {
        font-size: 30px;
        margin-bottom: 10px;
        color: rgb(8, 199, 206);
    }

    .card h5 {
        margin: 10px 0;
        font-size: 18px;
        color: rgb(72, 207, 162);
    }

    .card p {
        font-size: 20px;
        font-weight: bold;
        color: #333;
    }

    .form-section {
        margin-top: 40px;
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .form-section h3 {
        background-color: #17a2b8;
        color: white;
        padding: 10px;
        border-radius: 6px 6px 0 0;
    }

    .form-section form {
        margin-top: 15px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .btn {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
    }

    .footer-note {
        margin-top: 50px;
        text-align: center;
        font-size: 14px;
        color: #555;
    }

    .footer-note i {
        color: #28a745;
    }

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        .sidebar {
            width: 200px;
        }
        .main {
            padding: 20px;
        }
        .header h1 {
            font-size: 1.8em;
        }
        .cards {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        .card {
            padding: 15px;
        }
        .card i {
            font-size: 25px;
        }
        .card h5 {
            font-size: 16px;
        }
        .card p {
            font-size: 18px;
        }
        .footer-note {
            margin-top: 40px;
            font-size: 13px;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            padding: 10px;
        }
        .sidebar h2 {
            font-size: 1em;
            margin-bottom: 20px;
        }
        .sidebar a {
            justify-content: center;
            padding: 8px;
            margin: 8px 0;
            font-size: 0;
        }
        .sidebar i {
            margin-right: 0;
            font-size: 20px;
        }
        .main {
            padding: 15px;
        }
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .header h1 {
            font-size: 1.5em;
        }
        .cards {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }
        .card {
            padding: 12px;
        }
        .card i {
            font-size: 22px;
        }
        .card h5 {
            font-size: 14px;
        }
        .card p {
            font-size: 16px;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 12px;
        }
    }

    @media (max-width: 576px) {
        .sidebar {
            width: 60px;
            padding: 8px;
        }
        .sidebar h2 {
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        .sidebar a {
            padding: 6px;
            margin: 6px 0;
        }
        .sidebar i {
            font-size: 18px;
        }
        .main {
            padding: 10px;
        }
        .header h1 {
            font-size: 1.3em;
        }
        .cards {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .card {
            padding: 10px;
        }
        .card i {
            font-size: 20px;
        }
        .card h5 {
            font-size: 13px;
        }
        .card p {
            font-size: 15px;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 11px;
        }
    }
</style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-hospital"></i> Hosto Admin</h2>
        <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
        <a href="gestion_medecins.php"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="gestion_patients.php"><i class="fas fa-procedures"></i> Patients</a>
        <a href="rendezvous_admin.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="commentaires_admin.php"><i class="fas fa-comments"></i> Commentaires</a>
        <a href="ajouter_service.php"><i class="fas fa-building"></i> Services</a>
        <a href="ajouter_specialite.php"><i class="fas fa-stethoscope"></i> Spécialités</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Bienvenue, <?= htmlspecialchars($_SESSION["admin_nom"]) ?> 👨‍⚕</h1>
        </div>

        <div class="cards">
            <div class="card">
                <i class="fas fa-procedures"></i>
                <h5>Patients</h5>
                <p id="nbPatients"><?= $nbPatients ?></p>
            </div>
            <div class="card">
                <i class="fas fa-user-md"></i>
                <h5>Médecins</h5>
                <p id="nbMedecins"><?= $nbMedecins ?></p>
            </div>
            <div class="card">
                <i class="fas fa-calendar-check"></i>
                <h5>RDV Aujourd'hui</h5>
                <p id="nbRdvToday"><?= $nbRdvToday ?></p>
            </div>
            <div class="card">
                <i class="fas fa-comments"></i>
                <h5>Commentaires</h5>
                <p id="nbCommentaires"><?= $nbCommentaires ?></p>
            </div>

            <div class="card">
                <i class="fas fa-building"></i>
                <h5>Services</h5>
                <p id="nbServices"><?= $nbServices ?></p>
            </div>

            <div class="card">
                <i class="fas fa-stethoscope"></i>
                <h5>Specialite</h5>
                <p id="nbSpecialite"><?= $nbSpecialite ?></p>
            </div>
        </div>

        <div class="footer-note">
            <i class="fas fa-heartbeat"></i> Système hospitalier Hosto &copy; 2025
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('get_stats.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error fetching stats:', data.error);
                        // Optionally display an error message on the page
                        return;
                    }
                    document.getElementById('nbPatients').textContent = data.nbPatients;
                    document.getElementById('nbMedecins').textContent = data.nbMedecins;
                    document.getElementById('nbRdvToday').textContent = data.nbRdvToday;
                    document.getElementById('nbCommentaires').textContent = data.nbCommentaires;
                    document.getElementById('nbServices').textContent = data.nbServices;
                    document.getElementById('nbSpecialite').textContent = data.nbSpecialite;
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                    // Handle network errors or JSON parsing errors here
                });
        });
    </script>
</body>
</html>