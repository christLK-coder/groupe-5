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

// Gestion des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_diagnostic'])) {
        $id_rdv = filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT);
        $contenu = trim($_POST['contenu']);
        if ($id_rdv && $contenu) {
            $stmt = $pdo->prepare("INSERT INTO DIAGNOSTIC (id_rdv, contenu) VALUES (?, ?)");
            $stmt->execute([$id_rdv, $contenu]);
        }
    } elseif (isset($_POST['add_prescription'])) {
        $id_rdv = filter_var($_POST['id_rdv'], FILTER_VALIDATE_INT);
        $medicament = trim($_POST['medicament']);
        $posologie = trim($_POST['posologie']);
        $duree = trim($_POST['duree']);
        $conseils = trim($_POST['conseils']);
        if ($id_rdv && $medicament) {
            $stmt = $pdo->prepare("INSERT INTO PRESCRIPTION (id_rdv, medicament, posologie, duree, conseils) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_rdv, $medicament, $posologie, $duree, $conseils]);
        }
    }
    header("Location: diagnostics.php");
    exit();
}

// Requête : consultations du jour (confirmé ou encours)
$today = date('Y-m-d');
$sql = "
SELECT 
    r.id_rdv, r.date_heure, r.symptomes, r.type_consultation, r.niveau_urgence, r.statut,
    p.nom AS patient_nom, p.prenom AS patient_prenom,
    d.contenu AS diagnostic_contenu, d.date_diagnostic,
    pr.medicament, pr.posologie, pr.duree, pr.conseils, pr.date_creation AS date_prescription
FROM RENDEZVOUS r
JOIN PATIENT p ON r.id_patient = p.id_patient
LEFT JOIN DIAGNOSTIC d ON d.id_rdv = r.id_rdv
LEFT JOIN PRESCRIPTION pr ON pr.id_rdv = r.id_rdv
WHERE r.id_medecin = ? AND DATE(r.date_heure) = ? AND r.statut IN ('confirmé', 'encours')
ORDER BY r.date_heure ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_medecin, $today]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organisation des données
$consultations = [];
foreach ($rows as $row) {
    $key = $row['id_rdv'];
    if (!isset($consultations[$key])) {
        $consultations[$key] = [
            'id_rdv' => $row['id_rdv'],
            'patient_nom' => $row['patient_nom'],
            'patient_prenom' => $row['patient_prenom'],
            'date_heure' => $row['date_heure'],
            'symptomes' => $row['symptomes'],
            'type_consultation' => $row['type_consultation'],
            'niveau_urgence' => $row['niveau_urgence'],
            'statut' => $row['statut'],
            'diagnostic' => $row['diagnostic_contenu'],
            'date_diagnostic' => $row['date_diagnostic'],
            'prescriptions' => []
        ];
    }
    if ($row['medicament']) {
        $consultations[$key]['prescriptions'][] = [
            'medicament' => $row['medicament'],
            'posologie' => $row['posologie'],
            'duree' => $row['duree'],
            'conseils' => $row['conseils'],
            'date_prescription' => $row['date_prescription']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostics du Jour</title>
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
        .section {
            margin-bottom: 15px;
        }
        .section strong {
            color: #333;
            display: inline-block;
            width: 120px;
        }
        .prescription {
            background-color: #f3fbfa;
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
        .btn-primary {
            background-color: #93d6d0;
            border: none;
            color: #FFFFFF;
            transition: background-color 0.2s;
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
            <a class="nav-link" href="test.php">
                <span class="material-icons">dashboard</span>
                <span>Services</span>
            </a>
            <a class="nav-link" href="rendezvous.php">
                <span class="material-icons">event</span>
                <span>Rendez-vous</span>
            </a>
            <a class="nav-link" href="messages.php">
                <span class="material-icons">chat</span>
                <span>Messages</span>
            </a>
            <a class="nav-link active" href="diagnostics.php">
                <span class="material-icons">medical_services</span>
                <span>Contact</span>
            </a>
            <a class="nav-link" href="historique.php">
                <span class="material-icons">history</span>
                <span>Historique</span>
            </a>
            <a class="nav-link" href="logout.php">
                <span class="material-icons">logout</span>
                <span>Exit</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Diagnostics du Jour</h1>
            <?php if (empty($consultations)): ?>
                <div class="no-data">
                    <span class="material-icons" style="font-size: 40px; color: #93d6d0;">info</span>
                    <p>Aucune consultation prévue pour aujourd'hui.</p>
                </div>
            <?php else: ?>
                <?php foreach ($consultations as $consultation): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4><?= htmlspecialchars($consultation['patient_nom'] . ' ' . $consultation['patient_prenom']) ?></h4>
                            <div>
                                <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#diagnosticModal_<?=$consultation['id_rdv']?>">
                                    <span class="material-icons">add_circle_outline</span> Diagnostic
                                </button>
                                <button class="btn btn-primary btn-icon btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#prescriptionModal_<?=$consultation['id_rdv']?>">
                                    <span class="material-icons">medication</span> Prescription
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="section">
                                <strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($consultation['date_heure'])) ?>
                                <span class="material-icons" style="vertical-align: middle;">event</span>
                            </div>
                            <div class="section">
                                <strong>Type :</strong> <?= htmlspecialchars($consultation['type_consultation']) ?>
                            </div>
                            <div class="section">
                                <strong>Urgence :</strong> <?= htmlspecialchars($consultation['niveau_urgence']) ?>
                            </div>
                            <div class="section">
                                <strong>Symptomes :</strong>
                                <?= nl2br(htmlspecialchars($consultation['symptomes'] ?? 'Non spécifié')) ?>
                            </div>
                            <div class="section">
                                <strong>Diagnostic :</strong>
                                <?= $consultation['diagnostic'] ? nl2br(htmlspecialchars($consultation['diagnostic'])) : '<em>Non disponible</em>' ?>
                                <br>
                                <small><span class="material-icons" style="vertical-align: middle;">calendar_today</span> Posé le : <?= $consultation['date_diagnostic'] ? date('d/m/Y H:i', strtotime($consultation['date_diagnostic'])) : 'N/A' ?></small>
                            </div>
                            <div class="section">
                                <strong>Prescriptions :</strong>
                                <?php if (empty($consultation['prescriptions'])): ?>
                                    <div class="prescription"><em>Aucune prescription enregistrée.</em></div>
                                <?php else: ?>
                                    <?php foreach ($consultation['prescriptions'] as $prescription): ?>
                                        <div class="prescription">
                                            <strong><span class="material-icons" style="vertical-align: middle;">medication</span> <?= htmlspecialchars($prescription['medicament']) ?></strong> (<?= htmlspecialchars($prescription['duree']) ?>)<br>
                                            <em>Posologie :</em> <?= nl2br(htmlspecialchars($prescription['posologie'])) ?><br>
                                            <em>Conseils :</em> <?= nl2br(htmlspecialchars($prescription['conseils'])) ?><br>
                                            <small><span class="material-icons" style="vertical-align: middle;">calendar_today</span> Ajouté le : <?= date('d/m/Y H:i', strtotime($prescription['date_prescription'])) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Diagnostic -->
                    <div class="modal fade" id="diagnosticModal_<?=$consultation['id_rdv']?>" tabindex="-1" aria-labelledby="diagnosticModalLabel_<?=$consultation['id_rdv']?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="diagnosticModalLabel_<?=$consultation['id_rdv']?>">Ajouter un Diagnostic</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="id_rdv" value="<?=$consultation['id_rdv']?>">
                                        <div class="mb-3">
                                            <label for="contenu_<?=$consultation['id_rdv']?>" class="form-label">Contenu du Diagnostic</label>
                                            <textarea class="form-control" id="contenu_<?=$consultation['id_rdv']?>" name="contenu" rows="4" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" name="add_diagnostic" class="btn btn-primary btn-icon">
                                            <span class="material-icons">save</span> Enregistrer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Prescription -->
                    <div class="modal fade" id="prescriptionModal_<?=$consultation['id_rdv']?>" tabindex="-1" aria-labelledby="prescriptionModalLabel_<?=$consultation['id_rdv']?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="prescriptionModalLabel_<?=$consultation['id_rdv']?>">Ajouter une Prescription</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="id_rdv" value="<?=$consultation['id_rdv']?>">
                                        <div class="mb-3">
                                            <label for="medicament_<?=$consultation['id_rdv']?>" class="form-label">Médicament</label>
                                            <input type="text" class="form-control" id="medicament_<?=$consultation['id_rdv']?>" name="medicament" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="posologie_<?=$consultation['id_rdv']?>" class="form-label">Posologie</label>
                                            <textarea class="form-control" id="posologie_<?=$consultation['id_rdv']?>" name="posologie" rows="2" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="duree_<?=$consultation['id_rdv']?>" class="form-label">Durée</label>
                                            <input type="text" class="form-control" id="duree_<?=$consultation['id_rdv']?>" name="duree">
                                        </div>
                                        <div class="mb-3">
                                            <label for="conseils_<?=$consultation['id_rdv']?>" class="form-label">Conseils</label>
                                            <textarea class="form-control" id="conseils_<?=$consultation['id_rdv']?>" name="conseils" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" name="add_prescription" class="btn btn-primary btn-icon">
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