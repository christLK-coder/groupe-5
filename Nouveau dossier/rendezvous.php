<?php
session_start();
require_once 'connexion.php';
require_once 'send_email.php';

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_rdv = filter_var($_POST['id_rdv'] ?? 0, FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($id_rdv && $action) {
        try {
            // Fetch appointment and patient details
            $stmt = $pdo->prepare("
                SELECT r.date_début, r.type_consultation, r.statut, 
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.email AS patient_email
                FROM RENDEZVOUS r
                JOIN PATIENT p ON r.id_patient = p.id_patient
                WHERE r.id_rdv = ? AND r.id_medecin = ?
            ");
            $stmt->execute([$id_rdv, $id_medecin]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($appointment) {
                $patientName = $appointment['patient_nom'] . ' ' . $appointment['patient_prenom'];
                $patientEmail = $appointment['patient_email'];
                $dateDebut = date('d/m/Y H:i', strtotime($appointment['date_début']));
                $typeConsultation = $appointment['type_consultation'] === 'hopital' ? 'à l’hôpital' : 'à domicile';
                $subject = '';
                $body = '';

                switch ($action) {
                    case 'confirmer':
                        if ($appointment['statut'] === 'en_attente') {
                            $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'confirmé' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Confirmation de votre rendez-vous';
                            $body = "Votre rendez-vous $typeConsultation du $dateDebut a été confirmé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                        }
                        break;

                    case 'refuser':
                        if ($appointment['statut'] === 'en_attente') {
                            $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Annulation de votre rendez-vous';
                            $body = "Votre rendez-vous $typeConsultation du $dateDebut a été refusé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                        }
                        break;

                    case 'demarrer':
                        if ($appointment['statut'] === 'confirmé') {
                            $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'encours' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Début de votre rendez-vous';
                            $body = "Votre rendez-vous $typeConsultation du $dateDebut a commencé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                        }
                        break;

                    case 'reporter':
                        if ($appointment['statut'] === 'confirmé' && isset($_POST['new_date_debut'])) {
                            $newDateDebut = filter_var($_POST['new_date_debut'], FILTER_SANITIZE_STRING);
                            if (strtotime($newDateDebut)) {
                                $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET date_début = ? WHERE id_rdv = ?");
                                $stmt->execute([$newDateDebut, $id_rdv]);
                                $newDateFormatted = date('d/m/Y H:i', strtotime($newDateDebut));
                                $subject = 'Report de votre rendez-vous';
                                $body = "Votre rendez-vous $typeConsultation initialement prévu le $dateDebut a été reporté au $newDateFormatted.";
                                sendPatientEmail($patientEmail, $patientName, $subject, $body);
                            }
                        }
                        break;

                    case 'annuler':
                        if (in_array($appointment['statut'], ['confirmé', 'encours'])) {
                            $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ?");
                            $stmt->execute([$id_rdv]);
                            $subject = 'Annulation de votre rendez-vous';
                            $body = "Votre rendez-vous $typeConsultation du $dateDebut a été annulé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                        }
                        break;

                    case 'terminer':
                        if ($appointment['statut'] === 'encours') {
                            $dateFin = date('Y-m-d H:i:s');
                            $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'terminé', date_fin = ? WHERE id_rdv = ?");
                            $stmt->execute([$dateFin, $id_rdv]);
                            $subject = 'Fin de votre rendez-vous';
                            $body = "Votre rendez-vous $typeConsultation du $dateDebut est terminé.";
                            sendPatientEmail($patientEmail, $patientName, $subject, $body);
                        }
                        break;
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO: " . $e->getMessage());
        }
    }
    header('Location: rendezvous.php');
    exit();
}

// Handle filters
$whereClauses = ["r.id_medecin = ?"];
$params = [$id_medecin];
$dateFilter = $_GET['date_filter'] ?? 'today';
$statusFilter = $_GET['status_filter'] ?? '';
$patientSearch = $_GET['patient_search'] ?? '';

if ($dateFilter === 'today') {
    $whereClauses[] = "DATE(r.date_début) = CURDATE()";
} elseif ($dateFilter === 'past') {
    $whereClauses[] = "DATE(r.date_début) < CURDATE()";
} elseif ($dateFilter === 'future') {
    $whereClauses[] = "DATE(r.date_début) > CURDATE()";
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
$sql = "
    SELECT r.id_rdv, r.date_début, r.type_consultation, r.niveau_urgence, r.statut, r.symptomes,
           p.nom AS patient_nom, p.prenom AS patient_prenom
    FROM RENDEZVOUS r
    JOIN PATIENT p ON r.id_patient = p.id_patient
    WHERE $whereSql
    ORDER BY r.date_début ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rendez-vous</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
        }
        .card-header h4 {
            margin: 0;
            font-size: 18px;
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
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
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
            .filter-form .row > div {
                margin-bottom: 10px;
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
            <a class="nav-link" href="test.php">
                <span class="material-icons">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="nav-link active" href="rendezvous.php">
                <span class="material-icons">event</span>
                <span>Rendez-vous</span>
            </a>
            <a class="nav-link" href="messages.php">
                <span class="material-icons">chat</span>
                <span>Messages</span>
            </a>
            <a class="nav-link" href="diagnostics.php">
                <span class="material-icons">medical_services</span>
                <span>Diagnostics</span>
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
            <h1 class="mb-4">Gestion des Rendez-vous</h1>
            
            <!-- Filter Form -->
            <div class="filter-form">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="date_filter" class="form-label">Filtrer par date</label>
                        <select id="date_filter" name="date_filter" class="form-select">
                            <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>Tous</option>
                            <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
                            <option value="past" <?= $dateFilter === 'past' ? 'selected' : '' ?>>Passés</option>
                            <option value="future" <?= $dateFilter === 'future' ? 'selected' : '' ?>>Futurs</option>
                        </select>
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-md-4">
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

            <?php if (empty($appointments)): ?>
                <div class="no-data">
                    <span class="material-icons" style="font-size: 40px; color: #93d6d0;">info</span>
                    <p>Aucun rendez-vous trouvé avec ces critères.</p>
                </div>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4><?= htmlspecialchars($appointment['patient_nom'] . ' ' . $appointment['patient_prenom']) ?></h4>
                            <small><span class="material-icons" style="vertical-align: middle;">event</span> <?= date('d/m/Y H:i', strtotime($appointment['date_début'])) ?></small>
                        </div>
                        <div class="card-body">
                            <p><strong>Type :</strong> <?= htmlspecialchars($appointment['type_consultation']) ?></p>
                            <p><strong>Urgence :</strong> <?= htmlspecialchars($appointment['niveau_urgence']) ?></p>
                            <p><strong>Symptômes :</strong> <?= nl2br(htmlspecialchars($appointment['symptomes'] ?? 'Non spécifié')) ?></p>
                            <p><strong>Statut :</strong> <?= htmlspecialchars($appointment['statut']) ?></p>
                            <div class="d-flex gap-2">
                                <?php if ($appointment['statut'] === 'en_attente'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                        <input type="hidden" name="action" value="confirmer">
                                        <button type="submit" class="btn btn-primary btn-icon btn-sm">
                                            <span class="material-icons">check</span> Confirmer
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                        <input type="hidden" name="action" value="refuser">
                                        <button type="submit" class="btn btn-danger btn-icon btn-sm">
                                            <span class="material-icons">close</span> Refuser
                                        </button>
                                    </form>
                                <?php elseif ($appointment['statut'] === 'confirmé'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                        <input type="hidden" name="action" value="demarrer">
                                        <button type="submit" class="btn btn-primary btn-icon btn-sm">
                                            <span class="material-icons">play_arrow</span> Démarrer
                                        </button>
                                    </form>
                                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal_<?= $appointment['id_rdv'] ?>">
                                        <span class="material-icons">schedule</span> Reporter
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                        <input type="hidden" name="action" value="annuler">
                                        <button type="submit" class="btn btn-danger btn-icon btn-sm">
                                            <span class="material-icons">cancel</span> Annuler
                                        </button>
                                    </form>
                                <?php elseif ($appointment['statut'] === 'encours'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                        <input type="hidden" name="action" value="terminer">
                                        <button type="submit" class="btn btn-primary btn-icon btn-sm">
                                            <span class="material-icons">done_all</span> Terminer
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_rdv" value="<?= $appointment['id_rdv'] ?>">
                                        <input type="hidden" name="action" value="annuler">
                                        <button type="submit" class="btn btn-danger btn-icon btn-sm">
                                            <span class="material-icons">cancel</span> Annuler
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Reporting -->
                    <div class="modal fade" id="reportModal_<?= $appointment['id_rdv'] ?>" tabindex="-1" aria-labelledby="reportModalLabel_<?= $appointment['id_rdv'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="reportModalLabel_<?= $appointment['id_rdv'] ?>">Reporter le Rendez-vous</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>