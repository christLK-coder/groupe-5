<?php
session_start();
require_once 'connexion.php';
require_once 'send_email.php';

date_default_timezone_set('Africa/Lagos');

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg';

// Modified: Updated function to use duree_rdv (in minutes) directly
function parseDureeToSeconds($duree) {
    return (int)$duree * 60; // Convert minutes to seconds
}

// AJAX refresh for appointments
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    try {
        $whereClauses = ["r.id_medecin = ?"];
        $params = [$id_medecin];
        $dateFilter = $_GET['date_filter'] ?? 'today';
        $statusFilter = $_GET['status_filter'] ?? '';
        $patientSearch = $_GET['patient_search'] ?? '';

        if ($dateFilter === 'today') {
            $whereClauses[] = "DATE(r.date_debut) = CURDATE()";
        } elseif ($dateFilter === 'past') {
            $whereClauses[] = "DATE(r.date_debut) < CURDATE()";
        } elseif ($dateFilter === 'future') {
            $whereClauses[] = "DATE(r.date_debut) > CURDATE()";
        }

        if ($statusFilter && in_array($statusFilter, ['en_attente', 'confirmé', 'encours', 'terminé', 'annulé'])) {
            $whereClauses[] = "r.statut = ?";
            $params[] = $statusFilter;
        }

        if ($patientSearch) {
            $whereClauses[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
            $params[] = "%$patientSearch%";
            $params[] = "%$patientSearch%";
        }

        $whereSql = implode(' AND ', $whereClauses);
        // Modified: Use lowercase table names and duree_rdv
        $sql = "
            SELECT DISTINCT r.id_rdv, r.date_debut, r.type_consultation, r.niveau_urgence, r.statut, r.symptomes, r.duree_rdv, r.date_fin,
                            p.nom AS patient_nom, p.prenom AS prenom_patient, p.email AS patient_email
            FROM rendezvous r
            JOIN patient p ON r.id_patient = p.id_patient
            WHERE $whereSql
            ORDER BY r.date_debut ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['appointments' => $appointments]);
        exit();
    } catch (PDOException $e) {
        error_log("Erreur PDO AJAX: " . $e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Erreur lors du chargement des rendez-vous']);
        exit();
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_rdv = filter_var($_POST['id_rdv'] ?? 0, FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    if ($id_rdv && $action) {
        try {
            // Modified: Use lowercase table names
            $stmt = $pdo->prepare("
                SELECT r.date_debut, r.type_consultation, r.statut, r.duree_rdv, r.date_fin,
                       p.nom AS patient_nom, p.prenom AS prenom_patient, p.email AS patient_email
                FROM rendezvous r
                JOIN patient p ON r.id_patient = p.id_patient
                WHERE r.id_rdv = ? AND r.id_medecin = ?
            ");
            $stmt->execute([$id_rdv, $id_medecin]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($appointment) {
                $patientName = $appointment['patient_nom'] . ' ' . $appointment['prenom_patient'];
                $patientEmail = $appointment['patient_email'];
                $dateDebut = date('d/m/Y H:i', strtotime($appointment['date_debut']));
                $typeConsultation = $appointment['type_consultation'] === 'hopital' ? 'à l’hôpital' : 'à domicile';
                $subject = '';
                $body = '';

                switch ($action) {
                    case 'confirmer':
                        if ($appointment['statut'] === 'en_attente') {
                            $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'confirmé' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Confirmation de votre rendez-vous';
                            $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation du $dateDebut a été confirmé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                            $response['success'] = true;
                            $response['message'] = 'Rendez-vous confirmé';
                        } else {
                            $response['message'] = 'Action non autorisée pour ce statut';
                        }
                        break;
                    case 'refuser':
                        if ($appointment['statut'] === 'en_attente') {
                            $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'annulé' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Annulation de votre rendez-vous';
                            $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation du $dateDebut a été refusé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                            $response['success'] = true;
                            $response['message'] = 'Rendez-vous refusé';
                        } else {
                            $response['message'] = 'Action non autorisée pour ce statut';
                        }
                        break;
                    case 'reporter':
                        if ($appointment['statut'] === 'confirmé' && isset($_POST['new_date_debut'])) {
                            $newDateDebut = filter_var($_POST['new_date_debut'], FILTER_SANITIZE_STRING);
                            if (strtotime($newDateDebut)) {
                                $stmt = $pdo->prepare("UPDATE rendezvous SET date_debut = ? WHERE id_rdv = ?");
                                $stmt->execute([$newDateDebut, $id_rdv]);
                                $newDateFormatted = date('d/m/Y H:i', strtotime($newDateDebut));
                                $subject = 'Report de votre rendez-vous';
                                $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation initialement prévu le $dateDebut a été reporté au $newDateFormatted.";
                                sendPatientEmail($patientEmail, $patientName, $subject, $body);
                                $response['success'] = true;
                                $response['message'] = 'Rendez-vous reporté';
                            } else {
                                $response['message'] = 'Date invalide';
                            }
                        } else {
                            $response['message'] = 'Action non autorisée ou date manquante';
                        }
                        break;
                    case 'annuler':
                        if (in_array($appointment['statut'], ['confirmé', 'encours'])) {
                            $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'annulé' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Annulation de votre rendez-vous';
                            $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation du $dateDebut a été annulé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                            $response['success'] = true;
                            $response['message'] = 'Rendez-vous annulé';
                        } else {
                            $response['message'] = 'Action non autorisée pour ce statut';
                        }
                        break;
                    case 'forcer_arret':
                        if ($appointment['statut'] === 'encours') {
                            $dateFinToSave = date('Y-m-d H:i:s');
                            $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'terminé', date_fin = ? WHERE id_rdv = ?");
                            $stmt->execute([$dateFinToSave, $id_rdv]);
                            $subject = 'Fin de votre rendez-vous';
                            $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation du $dateDebut est terminé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                            $response['success'] = true;
                            $response['message'] = 'Rendez-vous terminé';
                        } else {
                            $response['message'] = 'Action non autorisée pour ce statut';
                        }
                        break;
                }
            } else {
                $response['message'] = 'Rendez-vous non trouvé ou non autorisé';
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO POST: " . $e->getMessage());
            $response['message'] = 'Erreur serveur lors de l’action';
        }
    } else {
        $response['message'] = 'Requête invalide';
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        header('Location: rendezvous.php');
        exit();
    }
}

// Automatic status updates
try {
    $currentTime = date('Y-m-d H:i:s');
    // Modified: Use lowercase table names and duree_rdv
    $stmt = $pdo->prepare("
        SELECT r.id_rdv, r.date_debut, r.date_fin, r.duree_rdv, r.statut, r.type_consultation,
               p.nom AS patient_nom, p.prenom AS prenom_patient, p.email AS patient_email
        FROM rendezvous r
        JOIN patient p ON r.id_patient = p.id_patient
        WHERE r.id_medecin = ? AND r.statut IN ('confirmé', 'encours')
    ");
    $stmt->execute([$id_medecin]);
    $appointmentsToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appointmentsToUpdate as $appt) {
        $id_rdv = $appt['id_rdv'];
        $date_debut = strtotime($appt['date_debut']);
        $date_fin = $appt['date_fin'] ? strtotime($appt['date_fin']) : null;
        $currentTimestamp = strtotime($currentTime);
        $patientName = $appt['patient_nom'] . ' ' . $appt['prenom_patient'];
        $patientEmail = $appt['patient_email'];
        $typeConsultation = $appt['type_consultation'] === 'hopital' ? 'à l’hôpital' : 'à domicile';
        $dateDebutFormatted = date('d/m/Y H:i', $date_debut);

        // Modified: Use duree_rdv for calculation
        $seconds = parseDureeToSeconds($appt['duree_rdv']);
        $date_fin_calculated = $date_debut + $seconds;

        if ($appt['statut'] === 'confirmé' && $currentTimestamp >= $date_debut) {
            $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'encours' WHERE id_rdv = ?");
            $stmt->execute([$id_rdv]);
            $subject = 'Début de votre rendez-vous';
            $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation du $dateDebutFormatted a commencé.";
            try {
                sendPatientEmail($patientEmail, $patientName, $subject, $body);
            } catch (Exception $e) {
                error_log("Erreur envoi e-mail: " . $e->getMessage());
            }
        } elseif ($appt['statut'] === 'encours' && $date_fin_calculated && $currentTimestamp >= $date_fin_calculated) {
            $dateFinToSave = date('Y-m-d H:i:s', $date_fin_calculated);
            $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'terminé', date_fin = ? WHERE id_rdv = ?");
            $stmt->execute([$dateFinToSave, $id_rdv]);
            $subject = 'Fin de votre rendez-vous';
            $body = "Cher(e) $patientName, votre rendez-vous $typeConsultation du $dateDebutFormatted est terminé.";
            try {
                sendPatientEmail($patientEmail, $patientName, $subject, $body);
            } catch (Exception $e) {
                error_log("Erreur envoi e-mail: " . $e->getMessage());
            }
        }
    }
} catch (PDOException $e) {
    error_log("Erreur PDO mise à jour statuts: " . $e->getMessage());
}

// Initial appointment fetch
$whereClauses = ["r.id_medecin = ?"];
$params = [$id_medecin];
$dateFilter = $_GET['date_filter'] ?? 'today';
$statusFilter = $_GET['status_filter'] ?? '';
$patientSearch = $_GET['patient_search'] ?? '';

if ($dateFilter === 'today') {
    $whereClauses[] = "DATE(r.date_debut) = CURDATE()";
} elseif ($dateFilter === 'past') {
    $whereClauses[] = "DATE(r.date_debut) < CURDATE()";
} elseif ($dateFilter === 'future') {
    $whereClauses[] = "DATE(r.date_debut) > CURDATE()";
}

if ($statusFilter && in_array($statusFilter, ['en_attente', 'confirmé', 'encours', 'terminé', 'annulé'])) {
    $whereClauses[] = "r.statut = ?";
    $params[] = $statusFilter;
}

if ($patientSearch) {
    $whereClauses[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $params[] = "%$patientSearch%";
    $params[] = "%$patientSearch%";
}

$whereSql = implode(' AND ', $whereClauses);
// Modified: Use lowercase table names and duree_rdv
$sql = "
    SELECT DISTINCT r.id_rdv, r.date_debut, r.type_consultation, r.niveau_urgence, r.statut, r.symptomes, r.duree_rdv, r.date_fin,
                    p.nom AS patient_nom, p.prenom AS prenom_patient, p.email AS patient_email
    FROM rendezvous r
    JOIN patient p ON r.id_patient = p.id_patient
    WHERE $whereSql
    ORDER BY r.date_debut ASC
";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur PDO récupération rendez-vous: " . $e->getMessage());
    $appointments = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rendez-vous</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
    /* Style de base inchangé */
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
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        z-index: 1000;
        transition: width 0.3s;
    }
    .sidebar.collapsed {
        width: 70px;
    }
    .sidebar .profile {
        text-align: center;
        padding: 20px;
        border-bottom: 2px solid #e0e0e0;
    }
    .sidebar .profile img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        border: 2px solid #93d6d0;
    }
    .sidebar.collapsed .profile img {
        width: 40px;
        height: 40px;
    }
    .sidebar .profile h4 {
        margin: 5px 0;
        color: #333;
        font-size: 16px;
    }
    .sidebar.collapsed .profile h4, .sidebar.collapsed .nav-link span {
        display: none;
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
    .sidebar.collapsed .nav-link {
        justify-content: center;
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
        padding: 20px;
        flex-grow: 1;
        overflow-y: auto;
        background-color: #f3fbfa;
        transition: margin-left 0.3s;
    }
    .main-content.collapsed {
        margin-left: 70px;
    }
    .card {
        background-color: #FFFFFF;
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        padding: 15px;
        animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .card-header {
        background-color: #93d6d0;
        color: #FFFFFF;
        padding: 10px 15px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .card-header h4 {
        margin: 0;
        font-size: 16px;
    }
    .card-body {
        padding: 15px;
    }
    .no-data {
        text-align: center;
        color: #666;
        padding: 20px;
    }
    .btn-primary {
        background-color: #93d6d0;
        border: none;
        color: #FFFFFF;
    }
    .btn-primary:hover {
        background-color: #7bc7c1;
    }
    .btn-icon {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
        padding: 6px 12px;
    }
    .modal-content {
        border-radius: 10px;
    }
    .modal-header {
        background-color: #93d6d0;
        color: #FFFFFF;
        border-radius: 10px 10px 0 0;
    }
    .filter-form {
        background-color: #FFFFFF;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .filter-form .form-label {
        font-size: 14px;
    }
    .filter-form .form-control, .filter-form .form-select {
        font-size: 14px;
    }
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }
    .error-message {
        color: #dc3545;
        text-align: center;
        margin-bottom: 15px;
    }
    .toggle-sidebar {
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1100;
        background-color: #93d6d0;
        color: #FFFFFF;
        border: none;
        padding: 8px;
        border-radius: 5px;
    }

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        .sidebar {
            width: 200px;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .main-content {
            margin-left: 200px;
        }
        .main-content.collapsed {
            margin-left: 70px;
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
        .card {
            padding: 12px;
        }
        .card-header h4 {
            font-size: 14px;
        }
        .card-body p {
            font-size: 13px;
        }
        .filter-form {
            padding: 12px;
        }
        .filter-form .form-label, .filter-form .form-control, .filter-form .form-select {
            font-size: 13px;
        }
        .btn-icon {
            font-size: 13px;
            padding: 5px 10px;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .main-content {
            margin-left: 70px;
            padding: 15px;
        }
        .main-content.collapsed {
            margin-left: 70px;
        }
        .filter-form .row > div {
            margin-bottom: 10px;
        }
        .filter-form {
            padding: 10px;
        }
        .card {
            margin-bottom: 15px;
            padding: 10px;
        }
        .card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
            padding: 8px 12px;
        }
        .card-header h4 {
            font-size: 13px;
        }
        .card-header small {
            font-size: 11px;
        }
        .card-body {
            padding: 12px;
        }
        .card-body p {
            font-size: 12px;
        }
        .btn-icon {
            font-size: 12px;
            padding: 5px 10px;
            width: 100%;
            justify-content: center;
            margin-bottom: 5px;
        }
        .modal-dialog {
            margin: 10px;
            max-width: 95%;
        }
        .modal-body {
            padding: 12px;
        }
        .modal-footer {
            padding: 8px;
        }
        .alert-container {
            top: 10px;
            right: 10px;
            width: 90%;
            margin: 0 auto;
        }
    }

    @media (max-width: 576px) {
        .sidebar {
            width: 60px;
        }
        .sidebar.collapsed {
            width: 60px;
        }
        .main-content {
            margin-left: 60px;
            padding: 10px;
        }
        .main-content.collapsed {
            margin-left: 60px;
        }
        .filter-form {
            padding: 8px;
        }
        .filter-form .form-label, .filter-form .form-control, .filter-form .form-select {
            font-size: 12px;
        }
        .card {
            padding: 8px;
        }
        .card-header h4 {
            font-size: 12px;
        }
        .card-header small {
            font-size: 10px;
        }
        .card-body p {
            font-size: 11px;
        }
        .btn-icon {
            font-size: 11px;
            padding: 4px 8px;
        }
        .modal-header h5 {
            font-size: 14px;
        }
        .modal-body .form-label {
            font-size: 12px;
        }
        .modal-body .form-control {
            font-size: 12px;
        }
        .toggle-sidebar {
            padding: 6px;
        }
    }
</style>
</head>
<body>
    <button class="toggle-sidebar d-md-none" onclick="toggleSidebar()">
        <span class="material-icons">menu</span>
    </button>
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
            <h1 class="mb-4">Gestion des Rendez-vous</h1>
            <div class="alert-container" id="alert-container"></div>
            <div id="error-message" class="error-message"></div>

            <div class="filter-form">
                <form id="filter-form" method="GET" class="row g-3">
                    <div class="col-md-4 col-sm-12">
                        <label for="date_filter" class="form-label">Filtrer par date</label>
                        <select id="date_filter" name="date_filter" class="form-select">
                            <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>Tous</option>
                            <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
                            <option value="past" <?= $dateFilter === 'past' ? 'selected' : '' ?>>Passés</option>
                            <option value="future" <?= $dateFilter === 'future' ? 'selected' : '' ?>>Futurs</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <label for="status_filter" class="form-label">Filtrer par statut</label>
                        <select id="status_filter" name="status_filter" class="form-select">
                            <option value="" <?= !$statusFilter ? 'selected' : '' ?>>Tous</option>
                            <option value="en_attente" <?= $statusFilter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="confirmé" <?= $statusFilter === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="encours" <?= $statusFilter === 'encours' ? 'selected' : '' ?>>En cours</option>
                            <option value="terminé" <?= $statusFilter === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                            <option value="annulé" <?= $statusFilter === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <label for="patient_search" class="form-label">Rechercher patient</label>
                        <input type="text" id="patient_search" name="patient_search" class="form-control" value="<?= htmlspecialchars($patientSearch) ?>" placeholder="Nom ou prénom">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-icon">
                            <span class="material-icons">filter_list</span> Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <div id="appointments-container">
                <?php if (empty($appointments)): ?>
                    <div class="no-data">
                        <span class="material-icons" style="font-size: 40px; color: #93d6d0;">info</span>
                        <p>Aucun rendez-vous trouvé avec ces critères.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="card" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                            <div class="card-header">
                                <h4><?= htmlspecialchars($appointment['patient_nom'] . ' ' . $appointment['prenom_patient']) ?></h4>
                                <small><span class="material-icons" style="vertical-align: middle;">event</span> <?= date('d/m/Y H:i', strtotime($appointment['date_debut'])) ?></small>
                            </div>
                            <div class="card-body">
                                <p><strong>Type :</strong> <?= htmlspecialchars($appointment['type_consultation']) ?></p>
                                <p><strong>Urgence :</strong> <?= htmlspecialchars($appointment['niveau_urgence'] ?: 'Normal') ?></p>
                                <p><strong>Symptômes :</strong> <?= nl2br(htmlspecialchars($appointment['symptomes'] ?? 'Non spécifié')) ?></p>
                                <p><strong>Statut :</strong> <span class="rdv-statut"><?= htmlspecialchars($appointment['statut']) ?></span></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($appointment['statut'] === 'en_attente'): ?>
                                        <button class="btn btn-primary btn-icon btn-sm btn-action" data-action="confirmer" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                                            <span class="material-icons">check</span> Confirmer
                                        </button>
                                        <button class="btn btn-danger btn-icon btn-sm btn-action" data-action="refuser" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                                            <span class="material-icons">close</span> Refuser
                                        </button>
                                    <?php elseif ($appointment['statut'] === 'confirmé'): ?>
                                        <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal_<?= $appointment['id_rdv'] ?>">
                                            <span class="material-icons">schedule</span> Reporter
                                        </button>
                                        <button class="btn btn-danger btn-icon btn-sm btn-action" data-action="annuler" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                                            <span class="material-icons">cancel</span> Annuler
                                        </button>
                                    <?php elseif ($appointment['statut'] === 'encours'): ?>
                                        <button class="btn btn-danger btn-icon btn-sm btn-action" data-action="forcer_arret" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                                            <span class="material-icons">stop</span> Forcer l'arrêt
                                        </button>
                                        <button class="btn btn-warning btn-icon btn-sm btn-action" data-action="annuler" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                                            <span class="material-icons">cancel</span> Annuler
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="reportModal_<?= $appointment['id_rdv'] ?>" tabindex="-1" aria-labelledby="reportModalLabel_<?= $appointment['id_rdv'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="reportModalLabel_<?= $appointment['id_rdv'] ?>">Reporter le Rendez-vous</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form class="report-form" data-rdv-id="<?= $appointment['id_rdv'] ?>">
                                        <div class="modal-body">
                                            <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                            <input type="hidden" name="action" value="reporter">
                                            <div class="mb-3">
                                                <label for="new_date_debut_<?= $appointment['id_rdv'] ?>" class="form-label">Nouvelle Date et Heure</label>
                                                <input type="datetime-local" class="form-control" id="new_date_debut_<?= $appointment['id_rdv'] ?>" name="new_date_debut" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-primary btn-icon">
                                                <span class="material-icons">save</span> Enregistrer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleSidebarBtn = document.querySelector('.toggle-sidebar');
    const filterForm = document.getElementById('filter-form');
    const appointmentsContainer = document.getElementById('appointments-container');
    const alertContainer = document.getElementById('alert-container');
    const errorMessageDiv = document.getElementById('error-message');

    // Cache pour stocker les rendez-vous actuels
    let currentAppointments = [];

    // Fonction pour basculer la sidebar
    window.toggleSidebar = function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
    };

    // Collapser la sidebar par défaut sur petits écrans
    if (window.innerWidth <= 768) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('collapsed');
    }

    // Gestion du formulaire de filtrage
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(filterForm);
        const queryParams = new URLSearchParams(formData).toString();
        fetchAppointments(`?action=refresh&${queryParams}`);
    });

    // Gestion des clics sur les boutons d'action
    appointmentsContainer.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-action');
        if (btn) {
            const rdvId = btn.dataset.rdvId;
            const action = btn.dataset.action;

            if (confirm(`Êtes-vous sûr de vouloir ${action.replace('_', ' ')} ce rendez-vous ?`)) {
                try {
                    const formData = new FormData();
                    formData.append('id_rdv', rdvId);
                    formData.append('action', action);

                    const response = await fetch('rendezvous.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`Erreur HTTP : ${response.status}`);
                    }

                    const result = await response.json();
                    showAlert(result.message, result.success ? 'success' : 'danger');
                    if (result.success) {
                        const formData = new FormData(filterForm);
                        const queryParams = new URLSearchParams(formData).toString();
                        fetchAppointments(`?action=refresh&${queryParams}`);
                    }
                } catch (error) {
                    console.error('Erreur AJAX:', error);
                    showAlert('Une erreur est survenue lors de l\'action.', 'danger');
                }
            }
        }
    });

    // Gestion des formulaires de report
    document.querySelectorAll('.report-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const rdvId = this.dataset.rdvId;
            const newDateDebut = this.querySelector(`#new_date_debut_${rdvId}`).value;

            if (!newDateDebut) {
                showAlert('Veuillez sélectionner une nouvelle date et heure.', 'danger');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id_rdv', rdvId);
                formData.append('action', 'reporter');
                formData.append('new_date_debut', newDateDebut);

                const response = await fetch('rendezvous.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP : ${response.status}`);
                }

                const result = await response.json();
                showAlert(result.message, result.success ? 'success' : 'danger');
                if (result.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById(`reportModal_${rdvId}`));
                    if (modal) modal.hide();
                    const formData = new FormData(filterForm);
                    const queryParams = new URLSearchParams(formData).toString();
                    fetchAppointments(`?action=refresh&${queryParams}`);
                }
            } catch (error) {
                console.error('Erreur AJAX:', error);
                showAlert('Une erreur est survenue lors du report du rendez-vous.', 'danger');
            }
        });
    });

    // Fonction pour récupérer les rendez-vous via AJAX
    async function fetchAppointments(queryParams = '') {
        try {
            const response = await fetch(`rendezvous.php${queryParams}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP : ${response.status}`);
            }

            const data = await response.json();
            if (data.error) {
                errorMessageDiv.textContent = data.error;
                appointmentsContainer.innerHTML = '';
                currentAppointments = [];
            } else {
                errorMessageDiv.textContent = '';
                updateAppointments(data.appointments);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des rendez-vous:', error);
            errorMessageDiv.textContent = 'Impossible de charger les rendez-vous. Veuillez réessayer.';
            appointmentsContainer.innerHTML = '';
            currentAppointments = [];
        }
    }

    // Fonction pour mettre à jour les rendez-vous avec une approche différentielle
    function updateAppointments(newAppointments) {
        // Créer un index des rendez-vous actuels par ID
        const currentMap = new Map(currentAppointments.map(appt => [appt.id_rdv, appt]));
        const newMap = new Map(newAppointments.map(appt => [appt.id_rdv, appt]));

        // Identifier les rendez-vous à ajouter, mettre à jour ou supprimer
        const toAdd = newAppointments.filter(appt => !currentMap.has(appt.id_rdv));
        const toUpdate = newAppointments.filter(appt => {
            if (currentMap.has(appt.id_rdv)) {
                const current = currentMap.get(appt.id_rdv);
                return JSON.stringify(current) !== JSON.stringify(appt);
            }
            return false;
        });
        const toRemove = currentAppointments.filter(appt => !newMap.has(appt.id_rdv));

        // Supprimer les rendez-vous obsolètes
        toRemove.forEach(appt => {
            const card = appointmentsContainer.querySelector(`.card[data-rdv-id="${appt.id_rdv}"]`);
            if (card) {
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
            const modal = document.getElementById(`reportModal_${appt.id_rdv}`);
            if (modal) modal.remove();
        });

        // Mettre à jour ou ajouter les rendez-vous
        const fragment = document.createDocumentFragment();
        newAppointments.forEach(appt => {
            const existingCard = appointmentsContainer.querySelector(`.card[data-rdv-id="${appt.id_rdv}"]`);
            const html = `
                <div class="card" data-rdv-id="${appt.id_rdv}" style="opacity: 0; transition: opacity 0.3s;">
                    <div class="card-header">
                        <h4>${escapeHtml(appt.patient_nom)} ${escapeHtml(appt.prenom_patient)}</h4>
                        <small><span class="material-icons" style="vertical-align: middle;">event</span> ${formatDate(appt.date_debut)}</small>
                    </div>
                    <div class="card-body">
                        <p><strong>Type :</strong> ${escapeHtml(appt.type_consultation)}</p>
                        <p><strong>Urgence :</strong> ${escapeHtml(appt.niveau_urgence || 'Normal')}</p>
                        <p><strong>Symptômes :</strong> ${nl2br(escapeHtml(appt.symptomes || 'Non spécifié'))}</p>
                        <p><strong>Statut :</strong> <span class="rdv-statut">${escapeHtml(appt.statut)}</span></p>
                        <div class="d-flex flex-wrap gap-2">
                            ${appt.statut === 'en_attente' ? `
                                <button class="btn btn-primary btn-icon btn-sm btn-action" data-action="confirmer" data-rdv-id="${appt.id_rdv}">
                                    <span class="material-icons">check</span> Confirmer
                                </button>
                                <button class="btn btn-danger btn-icon btn-sm btn-action" data-action="refuser" data-rdv-id="${appt.id_rdv}">
                                    <span class="material-icons">close</span> Refuser
                                </button>
                            ` : ''}
                            ${appt.statut === 'confirmé' ? `
                                <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal_${appt.id_rdv}">
                                    <span class="material-icons">schedule</span> Reporter
                                </button>
                                <button class="btn btn-danger btn-icon btn-sm btn-action" data-action="annuler" data-rdv-id="${appt.id_rdv}">
                                    <span class="material-icons">cancel</span> Annuler
                                </button>
                            ` : ''}
                            ${appt.statut === 'encours' ? `
                                <button class="btn btn-danger btn-icon btn-sm btn-action" data-action="forcer_arret" data-rdv-id="${appt.id_rdv}">
                                    <span class="material-icons">stop</span> Forcer l'arrêt
                                </button>
                                <button class="btn btn-warning btn-icon btn-sm btn-action" data-action="annuler" data-rdv-id="${appt.id_rdv}">
                                    <span class="material-icons">cancel</span> Annuler
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="reportModal_${appt.id_rdv}" tabindex="-1" aria-labelledby="reportModalLabel_${appt.id_rdv}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="reportModalLabel_${appt.id_rdv}">Reporter le Rendez-vous</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form class="report-form" data-rdv-id="${appt.id_rdv}">
                                <div class="modal-body">
                                    <input type="hidden" name="id_rdv" value="${appt.id_rdv}">
                                    <input type="hidden" name="action" value="reporter">
                                    <div class="mb-3">
                                        <label for="new_date_debut_${appt.id_rdv}" class="form-label">Nouvelle Date et Heure</label>
                                        <input type="datetime-local" class="form-control" id="new_date_debut_${appt.id_rdv}" name="new_date_debut" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary btn-icon">
                                        <span class="material-icons">save</span> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newCard = tempDiv.querySelector('.card');
            const newModal = tempDiv.querySelector('.modal');

            if (existingCard && toUpdate.some(u => u.id_rdv === appt.id_rdv)) {
                // Mettre à jour la carte existante
                existingCard.replaceWith(newCard);
                const existingModal = document.getElementById(`reportModal_${appt.id_rdv}`);
                if (existingModal) existingModal.replaceWith(newModal);
            } else if (!existingCard) {
                // Ajouter une nouvelle carte
                fragment.appendChild(newCard);
                fragment.appendChild(newModal);
            }

            // Appliquer la transition d'opacité pour les nouvelles cartes
            setTimeout(() => {
                if (newCard) newCard.style.opacity = '1';
            }, 10);
        });

        // Ajouter les nouvelles cartes au conteneur
        if (toAdd.length > 0 || toUpdate.length > 0) {
            appointmentsContainer.appendChild(fragment);
        }

        // Si aucun rendez-vous, afficher le message "Aucun rendez-vous"
        if (newAppointments.length === 0) {
            appointmentsContainer.innerHTML = `
                <div class="no-data">
                    <span class="material-icons" style="font-size: 40px; color: #93d6d0;">info</span>
                    <p>Aucun rendez-vous trouvé avec ces critères.</p>
                </div>
            `;
        }

        // Mettre à jour le cache
        currentAppointments = [...newAppointments];

        // Initialiser les modales Bootstrap
        newAppointments.forEach(appt => {
            const modalElement = document.getElementById(`reportModal_${appt.id_rdv}`);
            if (modalElement) {
                new bootstrap.Modal(modalElement);
            }
        });

        // Ré-attacher les gestionnaires d'événements pour les formulaires de report
        document.querySelectorAll('.report-form').forEach(form => {
            form.removeEventListener('submit', handleReportFormSubmit);
            form.addEventListener('submit', handleReportFormSubmit);
        });
    }

    // Gestionnaire pour les formulaires de report (défini à l'extérieur pour éviter les duplications)
    async function handleReportFormSubmit(e) {
        e.preventDefault();
        const rdvId = this.dataset.rdvId;
        const newDateDebut = this.querySelector(`#new_date_debut_${rdvId}`).value;

        if (!newDateDebut) {
            showAlert('Veuillez sélectionner une nouvelle date et heure.', 'danger');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id_rdv', rdvId);
            formData.append('action', 'reporter');
            formData.append('new_date_debut', newDateDebut);

            const response = await fetch('rendezvous.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP : ${response.status}`);
            }

            const result = await response.json();
            showAlert(result.message, result.success ? 'success' : 'danger');
            if (result.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById(`reportModal_${rdvId}`));
                if (modal) modal.hide();
                const formData = new FormData(filterForm);
                const queryParams = new URLSearchParams(formData).toString();
                fetchAppointments(`?action=refresh&${queryParams}`);
            }
        } catch (error) {
            console.error('Erreur AJAX:', error);
            showAlert('Une erreur est survenue lors du report du rendez-vous.', 'danger');
        }
    }

    // Fonction pour formater la date
    function formatDate(dateString) {
        const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' };
        return new Date(dateString).toLocaleDateString('fr-FR', options);
    }

    // Fonction pour échapper le HTML
    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Fonction pour convertir les retours à la ligne en <br>
    function nl2br(text) {
        if (text === null || text === undefined) {
            return '';
        }
        return text.replace(/\n/g, '<br>');
    }

    // Fonction pour afficher les alertes
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertContainer.appendChild(alertDiv);
        setTimeout(() => {
            const alert = bootstrap.Alert.getInstance(alertDiv);
            if (alert) alert.close();
        }, 5000);
    }

    // Rafraîchissement automatique toutes les 30 secondes
    setInterval(() => {
        const formData = new FormData(filterForm);
        const queryParams = new URLSearchParams(formData).toString();
        fetchAppointments(`?action=refresh&${queryParams}`);
    }, 30000);

    // Charger les rendez-vous initiaux
    const formData = new FormData(filterForm);
    const queryParams = new URLSearchParams(formData).toString();
    fetchAppointments(`?action=refresh&${queryParams}`);
});
</script>
</body>
</html>