<?php
session_start();
require_once 'connexion.php';

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
        .error-message, .success-message {
            text-align: center;
            margin-bottom: 20px;
        }
        .error-message {
            color: #dc3545;
        }
        .success-message {
            color: #28a745;
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
            <a class="nav-link active" href="diagnostics.php">
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
            <h1 class="mb-4">Diagnostics du Jour</h1>
            <div id="error-message" class="error-message"></div>
            <div id="success-message" class="success-message"></div>
            <div id="consultations-container"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function renderConsultations(consultations) {
            const container = $('#consultations-container');
            container.empty();

            if (!consultations || consultations.length === 0) {
                container.append(`
                    <div class="no-data">
                        <span class="material-icons" style="font-size: 40px; color: #93d6d0;">info</span>
                        <p>Aucune consultation prévue pour aujourd'hui.</p>
                    </div>
                `);
                return;
            }

            consultations.forEach(consultation => {
                let prescriptionsHtml = consultation.prescriptions.length === 0 
                    ? '<div class="prescription"><em>Aucune prescription.</em></div>'
                    : consultation.prescriptions.map(p => `
                        <div class="prescription">
                            <strong><span class="material-icons" style="vertical-align: middle;">medication</span> ${escapeHtml(p.medicament)}</strong> (${escapeHtml(p.duree || '')})<br>
                            <em>Posologie :</em> ${nl2br(escapeHtml(p.posologie || ''))}<br>
                            <em>Conseils :</em> ${nl2br(escapeHtml(p.conseils || ''))}<br>
                            <small><span class="material-icons" style="vertical-align: middle;">calendar_today</span> Ajouté le : ${p.date_prescription || 'N/A'}</small>
                        </div>
                    `).join('');

                container.append(`
                    <div class="card">
                        <div class="card-header">
                            <h4>${escapeHtml(consultation.patient_nom + ' ' + consultation.patient_prenom)}</h4>
                            <div>
                                <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#diagnosticModal_${consultation.id_rdv}">
                                    <span class="material-icons">add_circle</span> Diagnostic
                                </button>
                                <button class="btn btn-primary btn-icon btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#prescriptionModal_${consultation.id_rdv}">
                                    <span class="material-icons">medication</span> Prescription
                                </button>
                                <button class="btn btn-primary btn-icon btn-sm ms-2 export-pdf" data-rdv="${consultation.id_rdv}">
                                    <span class="material-icons">picture_as_pdf</span> Exporter & Envoyer PDF
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="section">
                                <strong>Date :</strong> ${consultation.date_début}
                                <span class="material-icons" style="vertical-align: middle;">event</span>
                            </div>
                            <div class="section">
                                <strong>Type :</strong> ${escapeHtml(consultation.type_consultation)}
                            </div>
                            <div class="section">
                                <strong>Urgence :</strong> ${escapeHtml(consultation.niveau_urgence)}
                            </div>
                            <div class="section">
                                <strong>Symptômes :</strong> ${nl2br(escapeHtml(consultation.symptomes))}
                            </div>
                            <div class="section">
                                <strong>Diagnostic :</strong> ${consultation.diagnostic ? nl2br(escapeHtml(consultation.diagnostic)) : '<em>Non disponible</em>'}
                                <br><small><span class="material-icons" style="vertical-align: middle;">calendar_today</span> Posé le : ${consultation.date_diagnostic || 'N/A'}</small>
                            </div>
                            <div class="section">
                                <strong>Prescriptions :</strong> ${prescriptionsHtml}
                            </div>
                        </div>
                    </div>

                    <!-- Modal Diagnostic -->
                    <div class="modal fade" id="diagnosticModal_${consultation.id_rdv}" tabindex="-1" aria-labelledby="diagnosticModalLabel_${consultation.id_rdv}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="diagnosticModalLabel_${consultation.id_rdv}">Ajouter un Diagnostic</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form class="diagnostic-form" data-rdv="${consultation.id_rdv}">
                                    <div class="modal-body">
                                        <input type="hidden" name="id_rdv" value="${consultation.id_rdv}">
                                        <div class="mb-3">
                                            <label for="contenu_${consultation.id_rdv}" class="form-label">Contenu du Diagnostic</label>
                                            <textarea class="form-control" id="contenu_${consultation.id_rdv}" name="contenu" rows="4" required></textarea>
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

                    <!-- Modal Prescription -->
                    <div class="modal fade" id="prescriptionModal_${consultation.id_rdv}" tabindex="-1" aria-labelledby="prescriptionModalLabel_${consultation.id_rdv}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="prescriptionModalLabel_${consultation.id_rdv}">Ajouter une Prescription</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form class="prescription-form" data-rdv="${consultation.id_rdv}">
                                    <div class="modal-body">
                                        <input type="hidden" name="id_rdv" value="${consultation.id_rdv}">
                                        <div class="mb-3">
                                            <label for="medicament_${consultation.id_rdv}" class="form-label">Médicament</label>
                                            <input type="text" class="form-control" id="medicament_${consultation.id_rdv}" name="medicament" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="posologie_${consultation.id_rdv}" class="form-label">Posologie</label>
                                            <textarea class="form-control" id="posologie_${consultation.id_rdv}" name="posologie" rows="2" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="duree_${consultation.id_rdv}" class="form-label">Durée</label>
                                            <input type="text" class="form-control" id="duree_${consultation.id_rdv}" name="duree">
                                        </div>
                                        <div class="mb-3">
                                            <label for="conseils_${consultation.id_rdv}" class="form-label">Conseils</label>
                                            <textarea class="form-control" id="conseils_${consultation.id_rdv}" name="conseils" rows="3"></textarea>
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
                `);
            });

            // Handle diagnostic and prescription form submissions
            $('.diagnostic-form, .prescription-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const rdvId = form.data('rdv');
                const formData = new FormData(this);
                formData.append('action', form.hasClass('diagnostic-form') ? 'add_diagnostic' : 'add_prescription');

                $.ajax({
                    url: 'fetch_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#diagnosticModal_' + rdvId + ', #prescriptionModal_' + rdvId).modal('hide');
                            form[0].reset();
                            loadConsultations();
                            $('#error-message').text('');
                            $('#success-message').text('Enregistrement réussi.');
                            setTimeout(() => $('#success-message').text(''), 3000);
                        } else {
                            $('#error-message').text(response.error || 'Erreur lors de l\'enregistrement.');
                        }
                    },
                    error: function() {
                        $('#error-message').text('Erreur de connexion au serveur.');
                    }
                });
            });

            // Handle PDF export and email
            $('.export-pdf').on('click', function() {
                const rdvId = $(this).data('rdv');
                $.ajax({
                    url: 'generate_pdf.php',
                    type: 'POST',
                    data: { id_rdv: rdvId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Send email with PDF
                            $.ajax({
                                url: 'fetch_data.php',
                                type: 'POST',
                                data: {
                                    action: 'send_pdf_email',
                                    pdf_path: response.pdf_path,
                                    patient_email: response.patient_email,
                                    patient_name: response.patient_name,
                                    id_rdv: rdvId
                                },
                                dataType: 'json',
                                success: function(emailResponse) {
                                    if (emailResponse.success) {
                                        $('#success-message').text('PDF généré et envoyé par email avec succès.');
                                        setTimeout(() => $('#success-message').text(''), 3000);
                                        // Clean up PDF file
                                        $.post('fetch_data.php', { action: 'cleanup_pdf', pdf_path: response.pdf_path });
                                    } else {
                                        $('#error-message').text(emailResponse.error || 'Erreur lors de l\'envoi de l\'email.');
                                    }
                                },
                                error: function() {
                                    $('#error-message').text('Erreur de connexion au serveur pour l\'envoi de l\'email.');
                                }
                            });
                        } else {
                            $('#error-message').text(response.error || 'Erreur lors de la génération du PDF.');
                        }
                    },
                    error: function() {
                        $('#error-message').text('Erreur de connexion au serveur pour la génération du PDF.');
                    }
                });
            });
        }

        function nl2br(str) {
            return str.replace(/\n/g, '<br>');
        }

        function loadConsultations() {
            $.ajax({
                url: 'fetch_data.php?type=diagnostics',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderConsultations(response.data);
                    } else {
                        $('#error-message').text(response.error || 'Erreur lors du chargement des données.');
                    }
                },
                error: function() {
                    $('#error-message').text('Erreur de connexion au serveur.');
                }
            });
        }

        $(document).ready(function() {
            loadConsultations();
        });
    </script>
</body>
</html>