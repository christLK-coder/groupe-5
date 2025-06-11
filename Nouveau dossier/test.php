<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg';

try {
    // Récupérer la note moyenne
    $stmt = $pdo->prepare("SELECT AVG(note) as moyenne FROM NOTE WHERE id_medecin = ?");
    $stmt->execute([$id_medecin]);
    $note_moyenne = round($stmt->fetchColumn() ?: 0, 1);

    // Récupérer le nombre de messages non lus
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM MESSAGE m
        JOIN CONVERSATION c ON m.id_conversation = c.id_conversation
        WHERE c.id_medecin = ? 
        AND m.type_expediteur = 'patient'
        AND m.date_message > COALESCE((
            SELECT MAX(date_message)
            FROM MESSAGE m2
            WHERE m2.id_conversation = c.id_conversation
            AND m2.type_expediteur = 'medecin'
        ), '2000-01-01')
    ");
    $stmt->execute([$id_medecin]);
    $nb_messages = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database Error in test.php: " . $e->getMessage());
    $note_moyenne = 0;
    $nb_messages = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            font-family: 'cambria';
        }
        body {
            background-color: #f3fbfa;
            margin: 0;
            font-family: 'Arial', sans-serif;
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
            }
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="<?= htmlspecialchars($image_medecin) ?>" alt="Profil">
            <h4>Dr. <?= htmlspecialchars($nom . ' ' . $prenom) ?></h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="test.php">
                <span class="material-icons">dashboard</span>
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
            <a class="nav-link" href="logout.php">
                <span class="material-icons">logout</span>
                <span>Déconnexion</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Bienvenue, Dr. <?= htmlspecialchars($prenom) ?></h1>
            <p>Aperçu de vos consultations et activités</p>

            <div class="row mb-4">
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <h3>Consultations Aujourd'hui</h3>
                        <p id="nb-consultations">0 Rendez-vous</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <h3>Messages Non Lus</h3>
                        <p id="nb-messages"><?= $nb_messages ?> Messages</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <h3>Note Moyenne</h3>
                        <p id="note-moyenne" class="star-rating" data-rating="<?= $note_moyenne ?>"></p>
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
                    <tbody id="rdv-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to display star rating
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
        }

        // Initial star display
        displayStars(parseFloat(document.getElementById('note-moyenne').dataset.rating));

        // Poll for rating updates every 10 seconds
        function updateRating() {
            fetch('get_rating.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('note-moyenne').dataset.rating = data.rating;
                        displayStars(parseFloat(data.rating));
                    }
                })
                .catch(error => console.error('Error updating rating:', error));
        }
        setInterval(updateRating, 10000);

        // Fetch consultations
        fetch('get_consultations.php')
            .then(response => response.json())
            .then(data => {
                const today = new Date().toISOString().slice(0, 10);
                const rdvToday = data.filter(rdv =>
                    rdv.date_heure.startsWith(today) && rdv.statut === 'confirmé'
                );

                // Update consultation count
                document.getElementById('nb-consultations').textContent = `${rdvToday.length} Rendez-vous`;

                // Fill table
                const rdvBody = document.getElementById('rdv-body');
                rdvBody.innerHTML = ''; // Clear existing rows
                rdvToday.forEach(rdv => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${rdv.nom_patient || 'N/A'} ${rdv.prenom_patient || ''}</td>
                        <td>${rdv.date_heure.replace('T', ' ')}</td>
                        <td>${rdv.type_consultation || 'N/A'}</td>
                        <td>${rdv.niveau_urgence || 'Normal'}</td>
                        <td>${rdv.statut}</td>
                    `;
                    rdvBody.appendChild(row);
                });
            })
            .catch(error => console.error('Erreur:', error));
    </script>
</body>
</html>