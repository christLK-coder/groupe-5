<?php
session_start();
require_once 'connexion.php'; // Assurez-vous que ce fichier contient votre connexion PDO

// Gérer les requêtes AJAX de récupération de données
// Si la requête est une requête AJAX pour les données, alors on ne génère pas le HTML complet
if (isset($_GET['ajax_request']) && $_GET['ajax_request'] === 'true') {
    header('Content-Type: application/json'); // Indiquer que la réponse est du JSON

    $response = ['success' => false, 'error' => '', 'data' => []];

    // Vérifier l'authentification pour la requête AJAX
    if (!isset($_SESSION['id_medecin'])) {
        $response['error'] = 'Non authentifié. Veuillez vous reconnecter.';
        echo json_encode($response);
        exit;
    }

    try {
        $id_medecin = $_SESSION['id_medecin'];
        $request_type = $_GET['type'] ?? ''; // Cela devrait toujours être 'historique' ici

        $sql = "";
        $params = ['id_medecin' => $id_medecin];

        if ($request_type === 'historique') {
            // Requête pour les rendez-vous terminés avec leurs diagnostics
            $sql = "
                SELECT 
                    r.id_rdv,
                    r.date_debut,
                    r.symptomes,
                    p.nom AS patient_nom,
                    p.prenom AS patient_prenom,
                    d.contenu AS diagnostic,
                    d.date_diagnostic
                FROM RENDEZVOUS r
                INNER JOIN PATIENT p ON r.id_patient = p.id_patient
                LEFT JOIN DIAGNOSTIC d ON r.id_rdv = d.id_rdv
                WHERE r.id_medecin = :id_medecin AND r.statut = 'terminé'
                ORDER BY r.date_debut DESC
            ";
        } else {
            // Ce bloc ne devrait normalement pas être atteint si l'appel AJAX est correct.
            // Il pourrait être utilisé pour d'autres types de données si nécessaire.
            $response['error'] = 'Type de requête de données invalide.';
            echo json_encode($response);
            exit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les prescriptions pour chaque consultation
        foreach ($consultations as &$consultation) {
            $stmt_presc = $pdo->prepare("
                SELECT 
                    medicament,
                    posologie,
                    duree,
                    conseils,
                    date_creation AS date_prescription
                FROM PRESCRIPTION
                WHERE id_rdv = :id_rdv
            ");
            $stmt_presc->execute(['id_rdv' => $consultation['id_rdv']]);
            $consultation['prescriptions'] = $stmt_presc->fetchAll(PDO::FETCH_ASSOC);

            // Formater les dates pour l'affichage
            $consultation['date_debut'] = $consultation['date_debut'] ? date('d/m/Y H:i', strtotime($consultation['date_debut'])) : 'N/A';
            $consultation['date_diagnostic'] = $consultation['date_diagnostic'] ? date('d/m/Y H:i', strtotime($consultation['date_diagnostic'])) : null;
            foreach ($consultation['prescriptions'] as &$prescription) {
                $prescription['date_prescription'] = $prescription['date_prescription'] ? date('d/m/Y H:i', strtotime($prescription['date_prescription'])) : 'N/A';
            }
        }

        $response['success'] = true;
        $response['data'] = $consultations;

    } catch (PDOException $e) {
        $response['error'] = 'Erreur base de données : ' . $e->getMessage();
        // Log l'erreur pour le débogage côté serveur
        error_log('Erreur PDO dans historique.php (AJAX) : ' . $e->getMessage());
    } catch (Exception $e) {
        $response['error'] = 'Erreur serveur : ' . $e->getMessage();
        // Log l'erreur pour le débogage côté serveur
        error_log('Erreur serveur dans historique.php (AJAX) : ' . $e->getMessage());
    }

    echo json_encode($response);
    exit; // Très important : arrêter l'exécution après la réponse JSON
}

// --- Fin du bloc PHP gérant les requêtes AJAX ---

// --- Début du code HTML/PHP pour l'affichage initial de la page ---

// Vérification de l'authentification pour l'affichage de la page HTML
if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Consultations Terminées</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
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
        .card-header {
            background-color: #93d6d0;
            color: #FFFFFF;
            padding: 10px 15px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .card-body {
            padding: 15px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section strong {
            color: #333;
            display: inline-block;
            width: 120px;
        }
        .prescription {
            background-color: #f3f6fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .prescription strong {
            color: #93d6d0;
        }
        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
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

    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Historique des Consultations Terminées</h1>
            <div id="error-message" class="error-message"></div>
            <div id="historique-container"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Helper function to escape HTML characters
        function escapeHtml(text) {
            if (text === null || typeof text === 'undefined') return '';
            return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // Helper function to convert newlines to <br> for display
        function nl2br(str) {
            if (str === null || typeof str === 'undefined') return '';
            return String(str).replace(/\n/g, '<br>');
        }

        function renderHistorique(historique) {
            const container = $('#historique-container');
            container.empty();

            if (!historique || historique.length === 0) {
                container.append(`
                    <div class="no-data">
                        <span class="material-icons" style="font-size: 40px; color: #93d6d0;">info</span>
                        <p>Aucune consultation terminée trouvée pour ce médecin.</p>
                    </div>
                `);
                return;
            }

            historique.forEach(consultation => {
                let prescriptionsHtml = consultation.prescriptions && consultation.prescriptions.length > 0 
                    ? consultation.prescriptions.map(p => `
                        <div class="prescription">
                            <strong><span class="material-icons" style="vertical-align: middle;">medication</span> ${escapeHtml(p.medicament)}</strong> ${p.duree ? `(${escapeHtml(p.duree)})` : ''}<br>
                            <em>Posologie :</em> ${nl2br(escapeHtml(p.posologie || 'Non spécifiée'))}<br>
                            <em>Conseils :</em> ${nl2br(escapeHtml(p.conseils || 'Aucun'))}<br>
                            <small><span class="material-icons" style="vertical-align: middle;">calendar_today</span> Ajouté le : ${escapeHtml(p.date_prescription)}</small>
                        </div>
                    `).join('')
                    : '<div class="prescription"><em>Aucune prescription enregistrée.</em></div>';

                container.append(`
                    <div class="card">
                        <div class="card-header">
                            <h3>${escapeHtml(consultation.patient_nom + ' ' + consultation.patient_prenom)}</h3>
                            <small><span class="material-icons" style="vertical-align: middle;">event</span> ${escapeHtml(consultation.date_debut)}</small>
                        </div>
                        <div class="card-body">
                            <div class="section">
                                <strong>Symptômes :</strong> ${nl2br(escapeHtml(consultation.symptomes)) || '<em>Non spécifiés</em>'}
                            </div>
                            <div class="section">
                                <strong>Diagnostic :</strong> ${consultation.diagnostic ? nl2br(escapeHtml(consultation.diagnostic)) : '<em>Non disponible</em>'}
                                <br><small><span class="material-icons" style="vertical-align: middle;">calendar_today</span> Posé le : ${escapeHtml(consultation.date_diagnostic || 'N/A')}</small>
                            </div>
                            <div class="section">
                                <strong>Prescriptions :</strong> ${prescriptionsHtml}
                            </div>
                        </div>
                    </div>
                `);
            });
        }

        // Function to load historical data (now fetches from the same file)
        function loadHistorique() {
            $('#error-message').text('');
            $.ajax({
                url: 'historique.php?ajax_request=true&type=historique', // Nouvelle URL pointant vers le même fichier
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderHistorique(response.data);
                    } else {
                        $('#error-message').text(response.error || 'Erreur lors du chargement des données.');
                        $('#historique-container').empty();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#error-message').text('Erreur de communication avec le serveur : ' + textStatus);
                    console.error('Erreur AJAX:', textStatus, errorThrown, jqXHR.responseText);
                    $('#historique-container').empty();
                }
            });
        }

        $(document).ready(function() {
            loadHistorique();
        });
    </script>
</body>
</html>