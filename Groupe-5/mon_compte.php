<?php
session_start(); // Start the session to store user data after login

// Database connection parameters
$host = 'localhost'; // Your database host
$db   = 'hopital'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("<script>alert('Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.');</script>");
}

// Check if the user is logged in
if (!isset($_SESSION['id_patient'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

$user_id = $_SESSION['id_patient']; // Get the patient ID from the session

// --- Handle Actions (e.g., Cancel Appointment) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json'); // Indicate JSON response

    // Cancel Rendezvous
    if ($_POST['action'] === 'annuler_rdv' && isset($_POST['rdv_id'])) {
        $rdv_id = (int)$_POST['rdv_id'];

        try {
            // Only allow cancellation if status is 'en_attente' or 'confirmé'
            $stmtUpdate = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ? AND id_patient = ? AND (statut = 'en_attente' OR statut = 'confirmé')");
            $stmtUpdate->execute([$rdv_id, $user_id]);

            if ($stmtUpdate->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Rendez-vous annulé avec succès!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Rendez-vous introuvable ou ne peut pas être annulé.']);
            }
        } catch (\PDOException $e) {
            error_log("Error canceling RDV: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'annulation du rendez-vous.']);
        }
        exit();
    }

    // Add other actions here if needed (e.g., delete comment, mark message as read)
}


// --- Retrieve User Information ---
// Fetch full user data from DB (more reliable than session for mutable data)
$stmt_user = $pdo->prepare("SELECT nom, prenom, email, telephone, adresse, image_patient, latitude, longitude, date_inscription FROM PATIENT WHERE id_patient = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    // User not found in DB, something is wrong with session or DB
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Assign variables for easier access
$nom_display = htmlspecialchars($user_data['nom']);
$prenom_display = htmlspecialchars($user_data['prenom']);
$email_display = htmlspecialchars($user_data['email']);
$telephone_display = htmlspecialchars($user_data['telephone'] ?? 'N/A');
$adresse_display = htmlspecialchars($user_data['adresse'] ?? 'N/A');
$image_user_display = htmlspecialchars($user_data['image_patient'] ?? 'https://via.placeholder.com/100?text=User');
$latitude_display = htmlspecialchars($user_data['latitude'] ?? 'N/A');
$longitude_display = htmlspecialchars($user_data['longitude'] ?? 'N/A');
$date_inscription_display = htmlspecialchars($user_data['date_inscription']);


// --- Retrieve Rendez-vous ---
$stmt_rdv = $pdo->prepare("SELECT r.id_rdv, r.date_heure, r.type_consultation, r.niveau_urgence, r.statut, r.symptomes, m.nom AS medecin_nom, m.prenom AS medecin_prenom, s.nom AS specialite_nom
                           FROM RENDEZVOUS r
                           JOIN MEDECIN m ON r.id_medecin = m.id_medecin
                           JOIN specialite s ON m.id_specialite = s.id_specialite
                           WHERE r.id_patient = ?
                           ORDER BY r.date_heure DESC");
$stmt_rdv->execute([$user_id]);
$rendezvous = $stmt_rdv->fetchAll(PDO::FETCH_ASSOC);

// --- Retrieve Latest Diagnostics (for simplicity, only those linked to completed RDVs) ---
$stmt_diag = $pdo->prepare("SELECT d.contenu, d.date_diagnostic, r.date_heure, m.nom AS medecin_nom, m.prenom AS medecin_prenom
                            FROM DIAGNOSTIC d
                            JOIN RENDEZVOUS r ON d.id_rdv = r.id_rdv
                            JOIN MEDECIN m ON r.id_medecin = m.id_medecin
                            WHERE r.id_patient = ? AND r.statut = 'terminé'
                            ORDER BY d.date_diagnostic DESC
                            LIMIT 5"); // Limit to last 5 diagnostics
$stmt_diag->execute([$user_id]);
$diagnostics = $stmt_diag->fetchAll(PDO::FETCH_ASSOC);


// --- Retrieve Recent Conversations (e.g., last 5 messages) ---
$stmt_conv = $pdo->prepare("
    SELECT
        C.id_conversation,
        M.contenu,
        M.date_message,
        M.type_expediteur,
        CASE
            WHEN M.type_expediteur = 'medecin' THEN CONCAT(MD.prenom, ' ', MD.nom)
            WHEN M.type_expediteur = 'patient' THEN CONCAT(P.prenom, ' ', P.nom)
            ELSE 'Inconnu'
        END AS expediteur_nom_complet,
        CONCAT(MD.prenom, ' ', MD.nom) AS medecin_interlocuteur_nom
    FROM MESSAGE M
    JOIN CONVERSATION C ON M.id_conversation = C.id_conversation
    LEFT JOIN MEDECIN MD ON C.id_medecin = MD.id_medecin
    LEFT JOIN PATIENT P ON C.id_patient = P.id_patient
    WHERE C.id_patient = ?
    ORDER BY M.date_message DESC
    LIMIT 5
");
$stmt_conv->execute([$user_id]);
$messages = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Mon Compte - Tableau de bord</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Reset & Base */
        * {
            box-sizing: border-box; /* cite: 14 */
            font-family: 'cambria'; /* cite: 14 */
        }
        body {
            margin: 0; /* cite: 15 */
            background-color: #f0f2f5; /* cite: 15 */
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        a {
            color: inherit; /* cite: 16 */
            text-decoration: none; /* cite: 16 */
        }
        a:hover {
            color: #008080; /* cite: 17 */
        }

        /* Sidebar */
        .sidebar {
            position: fixed; /* cite: 18 */
            left: 0; /* cite: 18 */
            top: 0; /* cite: 18 */
            width: 320px; /* cite: 18 */
            height: 100vh; /* cite: 18 */
            color: #eee;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            overflow: hidden; /* cite: 19 */
            z-index: 100; /* cite: 19 */
            background-color: rgb(18, 18, 18); /* cite: 19 */
            box-shadow: 0 0 6px rgba(54, 48, 48, 0.5); /* cite: 20 */
        }
        .sidebar.collapsed {
            width: 85px; /* cite: 21 */
        }
        .sidebar-header {
            padding: 30px 20px 20px; /* cite: 22 */
            text-align: center; /* cite: 22 */
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header img {
            width: 110px; /* cite: 23 */
            height: 110px; /* cite: 23 */
            border-radius: 50%; /* cite: 23 */
            object-fit: cover; /* cite: 23 */
            transition: all 0.3s ease; /* cite: 24 */
        }
        .sidebar.collapsed .sidebar-header img {
            width: 50px; /* cite: 25 */
            height: 50px; /* cite: 25 */
            box-shadow: none; /* cite: 25 */
        }
        .sidebar-header h3 {
            margin-top: 12px; /* cite: 26 */
            font-weight: 700; /* cite: 26 */
            font-size: 20px; /* cite: 26 */
            white-space: nowrap; /* cite: 26 */
            letter-spacing: 0.05em; /* cite: 26 */
        }
        .sidebar.collapsed .sidebar-header h3 {
            display: none; /* cite: 27 */
        }

        .sidebar nav {
            flex-grow: 1; /* cite: 28 */
            padding-top: 20px; /* cite: 28 */
        }

        .sidebar nav a {
            display: flex; /* cite: 29 */
            align-items: center; /* cite: 29 */
            color: #cfd8dc;
            padding: 16px 25px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease, color 0.3s ease; /* cite: 30 */
            white-space: nowrap; /* cite: 30 */
            border-left: 4px solid transparent;
        }
        .sidebar nav a:hover {
            border-left: 1px solid #fff; /* cite: 31 */
            box-shadow: 0 0 4px rgba(255,255,255,0.5); /* cite: 31 */
        }
        .sidebar nav a.active {
            background-color: #004d4d; /* cite: 32 */
            color: #fff; /* cite: 32 */
            border-left-color: rgb(18, 18, 18); /* cite: 32 */
        }
        .sidebar nav a .icon {
            margin-right: 18px; /* cite: 33 */
            font-size: 22px; /* cite: 33 */
            width: 30px; /* cite: 33 */
            text-align: center; /* cite: 33 */
            transition: color 0.3s ease; /* cite: 34 */
        }
        .sidebar.collapsed nav a .text {
            display: none; /* cite: 35 */
        }

        /* Main content */
        .main-content {
            margin-left: 320px; /* cite: 36 */
            padding: 40px 50px; /* cite: 36 */
            flex-grow: 1;
            transition: margin-left 0.3s ease;
            background-color: #fff;
            box-shadow: inset 0 0 20px #eee;
            min-height: 100vh; /* cite: 37 */
        }
        .sidebar.collapsed + .main-content {
            margin-left: 70px; /* cite: 38 */
            padding-left: 55px; /* cite: 38 */
            padding-right: 25px; /* cite: 38 */
        }

        /* Headings */
        h1, h2 {
            color: #1c2833; /* cite: 39 */
            margin-bottom: 20px; /* cite: 39 */
            letter-spacing: 0.03em; /* cite: 39 */
        }
        h1 {
            font-weight: 700; /* cite: 40 */
            font-size: 2.4rem; /* cite: 40 */
            text-align: center;
            margin-bottom: 50px;
        }
        h2 {
            font-weight: 600; /* cite: 41 */
            font-size: 1.7rem; /* cite: 41 */
            border-bottom: 2px solid rgb(18, 18, 18);; /* cite: 41 */
            padding-bottom: 8px; /* cite: 41 */
            margin-top: 40px; /* cite: 41 */
            margin-bottom: 25px; /* cite: 42 */
        }

        /* Sections with cards */
        section {
            margin-bottom: 60px; /* cite: 43 */
        }
        section#infos-user {
            background-color: rgb(163, 226, 249); /* cite: 44 */
            padding: 25px 35px; /* cite: 44 */
            border-radius: 12px; /* cite: 44 */
            box-shadow: 0 3px 8px rgba(0,0,0,0.05); /* cite: 44 */
            max-width: 700px; /* cite: 45 */
            margin: 0 auto 60px auto; /* Center the user info card */
        }
        section#infos-user p {
            font-size: 1.1rem; /* cite: 46 */
            margin: 10px 0; /* cite: 46 */
            line-height: 1.5; /* cite: 46 */
            color: #004d4d;
        }
        section#infos-user p strong {
            width: 150px; /* Adjust as needed for labels */
            display: inline-block;
        }

        /* Table styling */
        table {
            width: 100%; /* cite: 47 */
            border-collapse: separate; /* cite: 47 */
            border-spacing: 0 8px; /* cite: 47 */
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); /* cite: 47 */
            border-radius: 12px; /* cite: 47 */
            overflow: hidden; /* cite: 48 */
        }
        thead tr {
            background-color: rgb(0, 172, 252); /* cite: 49 */
            color: #fff; /* cite: 49 */
            font-weight: 700; /* cite: 49 */
        }
        th, td {
            padding: 14px 18px; /* cite: 50 */
            text-align: left; /* cite: 50 */
            vertical-align: middle; /* cite: 50 */
        }
        tbody tr {
            transition: background-color 0.25s ease; /* cite: 51 */
            cursor: default; /* cite: 51 */
            background-color: #f9f9f9; /* Lighter background for rows */
        }
        tbody tr:hover {
            background-color: rgb(163, 226, 249); /* cite: 52 */
        }
        tbody tr td:first-child {
            font-weight: 600; /* cite: 53 */
            color: #004d4d; /* cite: 53 */
        }
        /* Style for status column */
        .status-cell {
            font-weight: bold;
            text-transform: capitalize;
        }
        .status-en_attente { color: #f39c12; } /* Orange */
        .status-confirmé { color: #27ae60; } /* Green */
        .status-terminé { color: #3498db; } /* Blue */
        .status-annulé { color: #e74c3c; } /* Red */


        /* No data message */
        p.no-data {
            font-style: italic; /* cite: 54 */
            color: #888; /* cite: 54 */
            font-size: 1rem; /* cite: 54 */
            text-align: center; /* cite: 54 */
            padding: 30px 0; /* cite: 54 */
        }

        /* Toggle button */
        #sidebarToggle {
            position: absolute; /* cite: 55 */
            left: 330px; /* Adjusted to be next to the sidebar */
            top: 18px; /* cite: 55 */
            background: transparent; /* cite: 55 */
            border: none; /* cite: 55 */
            color: black; /* cite: 55 */
            width: 30px; /* cite: 55 */
            height: 30px; /* cite: 55 */
            cursor: pointer; /* cite: 55 */
            border-radius: 6px; /* cite: 55 */
            font-weight: 700; /* cite: 55 */
            font-size: 35px; /* cite: 56 */
            line-height: 30px; /* cite: 56 */
            user-select: none; /* cite: 56 */
            transition: background-color 0.3s ease, left 0.3s ease; /* cite: 56 */
            z-index: 101; /* cite: 57 */
        }
        .sidebar.collapsed + .main-content #sidebarToggle {
            left: 95px; /* Adjust button position when sidebar collapses */
        }

        /* Responsive */
        @media (max-width: 900px) {
            .sidebar {
                position: fixed; /* cite: 58 */
                width: 70px; /* cite: 58 */
            }
            .sidebar.collapsed {
                width: 0; /* cite: 59 */
            }
            .sidebar.collapsed + .main-content {
                margin-left: 0; /* cite: 60 */
            }
            .main-content {
                margin-left: 70px; /* cite: 61 */
                padding-left: 20px; /* cite: 61 */
                padding-right: 20px; /* cite: 61 */
            }
            section#infos-user {
                max-width: 100%; /* cite: 62 */
                margin: 0 auto 60px auto; /* Ensure it stays centered and responsive */
            }
            #sidebarToggle {
                left: 80px; /* Adjust button position for smaller screens */
            }
            .sidebar.collapsed + .main-content #sidebarToggle {
                left: 10px; /* Adjust button position when sidebar collapses */
            }
        }
    </style>
</head>

<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo $image_user_display; ?>" alt="Photo de profil" />
        <h3><?php echo $prenom_display . ' ' . $nom_display; ?></h3>
    </div>
    <nav>
        <a href="mon_compte.php" class="active"><span class="icon"><i class='bx bx-home' style="color: white;"></i></span> <span class="text">Tableau de bord</span></a>
        <a href="#rendezvous"><span class="icon"><i class='bx bx-calendar' style="color: white;"></i></span> <span class="text">Mes rendez-vous</span></a>
        <a href="#diagnostics"><span class="icon"><i class='bx bx-file-medical' style="color: white;"></i></span> <span class="text">Mes diagnostics</span></a>
        <a href="#messages"><span class="icon"><i class='bx bx-message-dots' style="color: white;"></i></span> <span class="text">Mes messages</span></a>
                <a href="chat.php"><span class="icon"><i class='bx bx-message-dots' style="color: white;"></i></span> <span class="text">Mes messages</span></a>
        <a href="parametres.php"><span class="icon"><i class='bx bx-cog' style="color: white;"></i></span> <span class="text">Parametres</span></a>
        <a href="logout.php"><span class="icon"><i class='bx bx-log-out' style="color: white;"></i></span> <span class="text">Déconnexion</span></a>
    </nav>
</div>

<div class="main-content">
    <button id="sidebarToggle" title="Toggle Sidebar">&laquo;</button>
    <h1>Bienvenue, <?php echo $prenom_display . ' ' . $nom_display; ?></h1>

    <section id="infos-user">
        <h2>Mes informations</h2>
        <p><strong>Email :</strong> <?php echo $email_display; ?></p>
        <p><strong>Téléphone :</strong> <?php echo $telephone_display; ?></p>
        <p><strong>Adresse :</strong> <?php echo nl2br($adresse_display); ?></p>
        <?php if ($latitude_display != 'N/A' && $longitude_display != 'N/A'): ?>
            <p><strong>Latitude :</strong> <?php echo $latitude_display; ?></p>
            <p><strong>Longitude :</strong> <?php echo $longitude_display; ?></p>
        <?php endif; ?>
        <p><strong>Date d'inscription :</strong> <?php echo $date_inscription_display; ?></p>
    </section>

    <section id="rendezvous">
        <h2>Mes rendez-vous</h2>
        <?php if (count($rendezvous) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Médecin</th>
                    <th>Spécialité</th>
                    <th>Type</th>
                    <th>Urgence</th>
                    <th>Statut</th>
                    <th>Symptômes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rendezvous as $rdv): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rdv['date_heure']))); ?></td>
                    <td><?php echo htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']); ?></td>
                    <td><?php echo htmlspecialchars($rdv['specialite_nom']); ?></td>
                    <td><?php echo htmlspecialchars($rdv['type_consultation']); ?></td>
                    <td><?php echo htmlspecialchars($rdv['niveau_urgence']); ?></td>
                    <td class="status-cell status-<?php echo htmlspecialchars($rdv['statut']); ?>" id="statut-rdv-<?php echo $rdv['id_rdv']; ?>">
                        <?php echo htmlspecialchars($rdv['statut']); ?>
                    </td>
                    <td><?php echo nl2br(htmlspecialchars(substr($rdv['symptomes'], 0, 50) . (strlen($rdv['symptomes']) > 50 ? '...' : ''))); ?></td>
                    <td>
                        <?php if ($rdv['statut'] === 'en_attente' || $rdv['statut'] === 'confirmé'): ?>
                            <button class="cancel-rdv-btn" data-id="<?php echo (int)$rdv['id_rdv']; ?>" style="background-color:#e74c3c; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Annuler</button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-data">Vous n'avez pas encore de rendez-vous planifiés.</p>
        <?php endif; ?>
    </section>

    <section id="diagnostics">
        <h2>Mes derniers diagnostics</h2>
        <?php if (count($diagnostics) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date du diagnostic</th>
                    <th>Date du RDV</th>
                    <th>Médecin</th>
                    <th>Contenu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics as $diag): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($diag['date_diagnostic']))); ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($diag['date_heure']))); ?></td>
                    <td><?php echo htmlspecialchars($diag['medecin_prenom'] . ' ' . $diag['medecin_nom']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($diag['contenu'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-data">Aucun diagnostic disponible pour le moment.</p>
        <?php endif; ?>
    </section>

</div>

<script>
$(document).ready(function() {
    // Sidebar Toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('collapsed');
        // Adjust main-content margin when sidebar toggles
        if ($('#sidebar').hasClass('collapsed')) {
            $('.main-content').css('margin-left', '70px');
            $('#sidebarToggle').css('left', '95px');
        } else {
            $('.main-content').css('margin-left', '320px');
            $('#sidebarToggle').css('left', '330px');
        }
    });

    // Handle Cancel Rendez-vous button click
    $('.cancel-rdv-btn').on('click', function(e) {
        e.preventDefault();
        var rdv_id = $(this).data('id');
        var $button = $(this); // Store reference to the button clicked

        if (confirm('Voulez-vous vraiment annuler ce rendez-vous ?')) {
            $.ajax({
                url: 'mon_compte.php', // Send to current page for processing
                type: 'POST',
                data: {
                    action: 'annuler_rdv',
                    rdv_id: rdv_id
                },
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        // Update the status in the table dynamically without full reload
                        $('#statut-rdv-' + rdv_id).text('annulé').removeClass().addClass('status-cell status-annulé');
                        $button.remove(); // Remove the cancel button
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Erreur lors de l\'annulation du rendez-vous: ' + error);
                    console.error("AJAX Error: ", status, error, xhr.responseText);
                }
            });
        }
    });

    // Function to update Rendez-vous statuses dynamically (if needed for background updates)
    // This is similar to your get_status.php logic but adapted for RDV statuses
    function mettreAJourStatutsRdv() {
        $.ajax({
            url: 'get_rdv_statuses.php', // Create a new PHP file for this
            type: 'GET',
            dataType: 'json',
            success: function(commandes) { // Renamed 'commandes' to 'rendezvous' for clarity
                rendezvous.forEach(function(rdv) {
                    var element = $('#statut-rdv-' + rdv.id_rdv);
                    if (element.length && element.text() !== rdv.statut) { // Only update if status changed
                        element.text(rdv.statut).removeClass().addClass('status-cell status-' + rdv.statut);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error("Erreur de requête AJAX pour les statuts: ", status, error);
            }
        });
    }

    // Call initial + update every 10 seconds
    // mettreAJourStatutsRdv(); // Uncomment if you want live updates for RDV statuses
    // setInterval(mettreAJourStatutsRdv, 10000); // Uncomment for live updates
});
</script>

</body>
</html>