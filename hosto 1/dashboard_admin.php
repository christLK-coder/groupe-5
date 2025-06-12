<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once("hosto.php");

// Récupération du nombre de services
$reqService = $conn->query("SELECT COUNT(*) FROM services");
$nbServices = $reqService->fetchColumn();

// Récupération du nombre de spécialités
$reqSpecialite = $conn->query("SELECT COUNT(*) FROM specialite");
$nbSpecialite = $reqSpecialite->fetchColumn();

// Récupération des données statistiques
$nbPatients = $conn->query("SELECT COUNT(*) FROM patient")->fetchColumn();
$nbMedecins = $conn->query("SELECT COUNT(*) FROM medecin")->fetchColumn();
$nbRdvToday = $conn->query("SELECT COUNT(*) FROM rendezvous WHERE DATE(date_heure) = CURDATE()")->fetchColumn();
$nbCommentaires = $conn->query("SELECT COUNT(*) FROM commentaire")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Hosto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0; padding: 0;
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
            background: rgb(104, 206, 10);
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
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
            background-color: rgb(37, 192, 6);
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
            color: rgb(5, 231, 24);
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
            color: #28a745;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2><i class="fas fa-hospital"></i> Hosto Admin</h2>
        <a href="#"><i class="fas fa-home"></i> Accueil</a>
        <a href="gestion_medecins.php"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="gestion_patients.php"><i class="fas fa-procedures"></i> Patients</a>
        <a href="rendezvous_admin.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="commentaires_admin.php"><i class="fas fa-comments"></i> Commentaires</a>
        <a href="ajouter_service.php"><i class="fas fa-building"></i> Services</a>
        <a href="ajouter_specialite.php"><i class="fas fa-stethoscope"></i> Spécialités</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header">
            <h1>Bienvenue, <?= htmlspecialchars($_SESSION["admin_nom"]) ?> 👨‍⚕</h1>
        </div>

        <!-- Statistiques -->
        <div class="cards">
            <div class="card">
                <i class="fas fa-procedures"></i>
                <h5>Patients</h5>
                <p><?= $nbPatients ?></p>
            </div>
            <div class="card">
                <i class="fas fa-user-md"></i>
                <h5>Médecins</h5>
                <p><?= $nbMedecins ?></p>
            </div>
            <div class="card">
                <i class="fas fa-calendar-check"></i>
                <h5>RDV Aujourd'hui</h5>
                <p><?= $nbRdvToday ?></p>
            </div>
            <div class="card">
                <i class="fas fa-comments"></i>
                <h5>Commentaires</h5>
                <p><?= $nbCommentaires ?></p>
            </div>

            <div class="card">
                <i class="fas fa-building"></i>
                <h5>Services</h5>
                <p><?= $nbServices ?></p>
            </div>

            <div class="card">
                <i class="fas fa-stethoscope"></i>
                <h5>Specialite</h5>
                <p><?= $nbSpecialite ?></p>
            </div>
        </div>

       

        <!-- Footer -->
        <div class="footer-note">
            <i class="fas fa-heartbeat"></i> Système hospitalier Hosto &copy; 2025
        </div>
    </div>
</body>
</html>
