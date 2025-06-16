<?php
session_start();

require_once 'connexion.php';

if (!isset($_SESSION['id_patient'])) {
    header("Location: login_p.php"); 
    exit();
}

$user_id = $_SESSION['id_patient']; 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json'); 


    if ($_POST['action'] === 'annuler_rdv' && isset($_POST['rdv_id'])) {
        $rdv_id = (int)$_POST['rdv_id'];

        try {

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


}



$stmt_user = $pdo->prepare("SELECT nom, prenom, email, telephone, adresse, image_patient, date_inscription FROM PATIENT WHERE id_patient = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}


$nom_display = htmlspecialchars($user_data['nom']);
$prenom_display = htmlspecialchars($user_data['prenom']);
$email_display = htmlspecialchars($user_data['email']);
$telephone_display = htmlspecialchars($user_data['telephone'] ?? 'N/A');
$adresse_display = htmlspecialchars($user_data['adresse'] ?? 'N/A');
$image_user_display = htmlspecialchars($user_data['image_patient'] ?? 'https://via.placeholder.com/100?text=User');
$date_inscription_display = htmlspecialchars($user_data['date_inscription']);



$stmt_rdv = $pdo->prepare("SELECT r.id_rdv, r.date_debut, r.type_consultation, r.niveau_urgence, r.statut, r.symptomes, m.nom AS medecin_nom, m.prenom AS medecin_prenom, s.nom AS specialite_nom
                           FROM RENDEZVOUS r
                           JOIN MEDECIN m ON r.id_medecin = m.id_medecin
                           JOIN specialite s ON m.id_specialite = s.id_specialite
                           WHERE r.id_patient = ?
                           ORDER BY r.date_heure DESC");
$stmt_rdv->execute([$user_id]);
$rendezvous = $stmt_rdv->fetchAll(PDO::FETCH_ASSOC);


$stmt_diag = $pdo->prepare("SELECT d.contenu, d.date_diagnostic, r.date_debut, m.nom AS medecin_nom, m.prenom AS medecin_prenom
                            FROM DIAGNOSTIC d
                            JOIN RENDEZVOUS r ON d.id_rdv = r.id_rdv
                            JOIN MEDECIN m ON r.id_medecin = m.id_medecin
                            WHERE r.id_patient = ? AND r.statut = 'terminé'
                            ORDER BY d.date_diagnostic DESC
                            LIMIT 5"); 
$stmt_diag->execute([$user_id]);
$diagnostics = $stmt_diag->fetchAll(PDO::FETCH_ASSOC);



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
    <link rel="stylesheet" href=" style_compt.css">
</head>

<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo $image_user_display; ?>" alt="Photo de profil" />
        <h3><?php echo $prenom_display . ' ' . $nom_display; ?></h3>
    </div>
    <nav>
        <a href="index.php" class="active"><span class="icon"><i class='bx bx-home' ></i></span> <span class="text">Accueill</span></a>
        <a href="#rendezvous"><span class="icon"><i class='bx bx-calendar' ></i></span> <span class="text">Mes rendez-vous</span></a>
        <a href="#diagnostics"><span class="icon"><i class='bx bx-first-aid'></i></span> <span class="text">Mes diagnostics</span></a>
        <a href="ia_patient.php"><span class="icon"><i class='bx bx-message-dots' ></i></span> <span class="text">IA</span></a>
        <a href="chat.php"><span class="icon"><i class='bx bx-message-dots' ></i></span> <span class="text">Mes messages</span></a>
        <a href="parametres_patient.php"><span class="icon"><i class='bx bx-cog'></i></span> <span class="text">Parametres</span></a>
        <a href="logout_P.php"><span class="icon"><i class='bx bx-log-out'></i></span> <span class="text">Déconnexion</span></a>
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
        <p><strong>Date d'inscription :</strong> <?php echo $date_inscription_display; ?></p>
    </section>

    <section id="rendezvous">
        <h2>Mes rendez-vous</h2>
        <?php if (count($rendezvous) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date du RDV</th>
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
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rdv['date_debut']))); ?></td>
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
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($diag['date_debut']))); ?></td>
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

    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('collapsed');
        
        if ($('#sidebar').hasClass('collapsed')) {
            $('.main-content').css('margin-left', '70px');
            $('#sidebarToggle').css('left', '95px');
        } else {
            $('.main-content').css('margin-left', '320px');
            $('#sidebarToggle').css('left', '330px');
        }
    });


    $('.cancel-rdv-btn').on('click', function(e) {
        e.preventDefault();
        var rdv_id = $(this).data('id');
        var $button = $(this);

        if (confirm('Voulez-vous vraiment annuler ce rendez-vous ?')) {
            $.ajax({
                url: 'mon_compte.php', 
                type: 'POST',
                data: {
                    action: 'annuler_rdv',
                    rdv_id: rdv_id
                },
                dataType: 'json', 
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);

                        $('#statut-rdv-' + rdv_id).text('annulé').removeClass().addClass('status-cell status-annulé');
                        $button.remove(); 
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

    function mettreAJourStatutsRdv() {
        $.ajax({
            url: 'get_rdv_statuses.php', 
            type: 'GET',
            dataType: 'json',
            success: function(commandes) { 
                rendezvous.forEach(function(rdv) {
                    var element = $('#statut-rdv-' + rdv.id_rdv);
                    if (element.length && element.text() !== rdv.statut) { 
                        element.text(rdv.statut).removeClass().addClass('status-cell status-' + rdv.statut);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error("Erreur de requête AJAX pour les statuts: ", status, error);
            }
        });
    }


});
</script>

</body>
</html>