
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>

<?php
require_once("hosto.php"); // contient la connexion PDO à la BDD
?>

<?php
// Nombre de patients
$nbPatients = $conn->query("SELECT COUNT(*) FROM patient")->fetchColumn();

// Nombre de médecins
$nbMedecins = $conn->query("SELECT COUNT(*) FROM medecin")->fetchColumn();

// Nombre de rendez-vous du jour
$nbRdvToday = $conn->query("SELECT COUNT(*) FROM rendezvous WHERE DATE(date_heure) = CURDATE()")->fetchColumn();

// Nombre de commentaires
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

        /* Sidebar */
        .sidebar {
            width: 250px;
            background:rgb(104, 206, 10);
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
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }

        .sidebar a:hover {
            background-color:rgb(37, 192, 6);
        }

        .sidebar i {
            margin-right: 10px;
        }

        /* Main content */
        .main {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color:rgb(5, 231, 24);
        }

        .logout {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card i {
            font-size: 30px;
            color:rgb(8, 199, 206);
        }

        .card h3 {
            margin-bottom: 5px;
            color:rgb(13, 218, 13);
        }

        .footer-note {
            margin-top: 40px;
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

    <div class="sidebar">
        <h2><i class="fas fa-hospital"></i> Hosto Admin</h2>
        <a href="#"><i class="fas fa-home"></i> Accueil</a>
        <a href="gestion_medecins.php"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="gestion_patients.php"><i class="fas fa-procedures"></i> Patients</a>
        <a href="rendezvous_admin.php"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="commentaires_admin.php"><i class="fas fa-comments me-2"></i> Commentaires</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Bienvenue, <?= htmlspecialchars($_SESSION["admin_nom"]) ?> 👨‍⚕</h1>
           
        </div>



<div class="row mt-4">
    <!-- Patients -->
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title">Patients</h5>
                <i class="fas fa-procedures"></i><?= $nbPatients ?></p>
            </div>
        </div>
    </div>
    
    <!-- Médecins -->
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title">Médecins</h5>
                <i class="fas fa-user-md"></i><?= $nbMedecins ?></p>
            </div>
        </div>
    </div>

    <!-- RDV aujourd'hui -->
    <div class="col-md-3">
        <div class="card text-white bg-warning shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title">RDV Aujourd'hui</h5>
                <i class="fas fa-calendar-check"></i><?= $nbRdvToday ?></p>
            </div>
        </div>
    </div>

    <!-- Commentaires -->
    <div class="col-md-3">
        <div class="card text-white bg-danger shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title">Commentaires</h5>
                <i class="fas fa-comments me-2"></i><?= $nbCommentaires ?></p>
            </div>
        </div>
    </div>
</div>


        <div class="footer-note">
            <i class="fas fa-heartbeat"></i> Système hospitalier Hosto &copy; 2025
        </div>
    </div>

</body>
</html>
