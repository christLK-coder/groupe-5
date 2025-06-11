<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
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
            background: #007bff;
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
            background-color: #0056b3;
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
            color: #007bff;
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
            color: #28a745;
        }

        .card h3 {
            margin-bottom: 5px;
            color: #007bff;
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
        <a href="#"><i class="fas fa-procedures"></i> Patients</a>
        <a href="#"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="#"><i class="fas fa-file-medical"></i> Rapports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Bienvenue, <?= htmlspecialchars($_SESSION["admin_nom"]) ?> 👨‍⚕</h1>
            <form method="post" action="logout.php">
                <button class="logout" type="submit"><i class="fas fa-power-off"></i> Déconnexion</button>
            </form>
        </div>

        <div class="cards">
            <div class="card">
                <i class="fas fa-user-md"></i>
                <div>
                    <h3>12</h3>
                    <p>Médecins enregistrés</p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-procedures"></i>
                <div>
                    <h3>54</h3>
                    <p>Patients actifs</p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-calendar-check"></i>
                <div>
                    <h3>8</h3>
                    <p>Rendez-vous aujourd'hui</p>
                </div>
            </div>
            <div class="card">
                <i class="fas fa-file-medical-alt"></i>
                <div>
                    <h3>5</h3>
                    <p>Rapports à générer</p>
                </div>
            </div>
        </div>

        <div class="footer-note">
            <i class="fas fa-heartbeat"></i> Système hospitalier Hosto &copy; 2025
        </div>
    </div>

</body>
</html>
