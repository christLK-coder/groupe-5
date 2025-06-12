<?php
session_start();
require_once 'connexion.php';

// Gemini API settings
$api_key = 'AIzaSyCRTYZhF-qfqKrbOXwIBUu29pDs1I5B9sg';
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

function callGeminiAPI($prompt, $api_key, $api_url) {
    $data = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ];
    
    $ch = curl_init($api_url . '?key=' . $api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['success' => false, 'error' => "Erreur API: Code $http_code"];
    }

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return ['success' => true, 'content' => $result['candidates'][0]['content']['parts'][0]['text']];
    }
    return ['success' => false, 'error' => 'Réponse API invalide'];
}

function findDoctorBySpecialty($pdo, $specialty) {
    try {
        $stmt = $pdo->prepare("SELECT nom, prenom, specialite, departement FROM MEDECIN WHERE LOWER(specialite) LIKE LOWER(?)");
        $stmt->execute(["%$specialty%"]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'analyze_symptoms') {
        $symptoms = trim($_POST['symptoms'] ?? '');
        if (empty($symptoms)) {
            $result = ['success' => false, 'error' => 'Racontez-nous ce que vous ressentez !'];
        } else {
            $prompt = "Salut ! Je suis ton assistant médical tout gentil. Quelqu’un nous dit : « $symptoms ». Peux-tu regarder ça avec soin et nous dire quelle spécialité médicale (par ex. : Cardiologie, Neurologie) serait la mieux adaptée ? Réponds juste avec le nom de la spécialité (ex. : Neurologie).";
            $api_response = callGeminiAPI($prompt, $api_key, $api_url);
            
            if ($api_response['success']) {
                $specialty = trim($api_response['content']);
                $doctor = findDoctorBySpecialty($pdo, $specialty);
                
                if ($doctor) {
                    $result = [
                        'success' => true,
                        'specialty' => $doctor['specialite'],
                        'doctor' => "Dr. {$doctor['prenom']} {$doctor['nom']}",
                        'department' => $doctor['departement'] ?? 'Consultations'
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'error' => "Oups, on n’a pas de médecin pour la spécialité « $specialty » en ce moment. 📞 Appelle la clinique pour qu’on t’aide !"
                    ];
                }
            } else {
                $result = ['success' => false, 'error' => $api_response['error']];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } elseif ($_POST['action'] === 'emergency' || $_POST['action'] === 'custom_emergency') {
        $emergency = trim($_POST['emergency'] ?? '');
        if (empty($emergency)) {
            $result = ['success' => false, 'error' => 'Dis-nous de quelle urgence il s’agit !'];
        } else {
            $prompt = "Coucou ! Je suis là pour aider avec un grand sourire. 😊 Pour une urgence comme « $emergency », peux-tu nous donner des conseils simples et sécuritaires sur ce qu’il faut faire en attendant un médecin ? Liste les étapes avec des points (ex. : - Faire ceci). Sois clair et chaleureux !";
            $api_response = callGeminiAPI($prompt, $api_key, $api_url);
            
            if ($api_response['success']) {
                $result = ['success' => true, 'measures' => $api_response['content'], 'emergency' => $emergency];
            } else {
                $result = ['success' => false, 'error' => $api_response['error']];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinique Lemongo - On est là pour toi !</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background-color: #f3fbfa;
            font-family: 'Comfortaa', sans-serif;
            color: #333;
            overflow-x: hidden;
        }
        .container {
            max-width: 800px;
            padding: 30px 20px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #93d6d0;
            font-size: 2rem;
            margin-top: 15px;
        }
        .section {
            background-color: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #ffe8d6;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .section h2 {
            color: #93d6d0;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }
        .form-control {
            border-color: #93d6d0;
            border-radius: 10px;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #93d6d0;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 1rem;
            transition: transform 0.2s, background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #7bc7c1;
            transform: scale(1.05);
        }
        .emergency-list {
            list-style: none;
            padding: 0;
        }
        .emergency-list li {
            margin-bottom: 12px;
        }
        .emergency-list .btn, .custom-emergency-btn {
            width: 100%;
            text-align: left;
            background-color: #e9f6f5;
            color: #333;
            border: 1px solid #93d6d0;
            border-radius: 10px;
            padding: 12px;
            font-size: 1rem;
            transition: background-color 0.3s, transform 0.2s;
        }
        .emergency-list .btn:hover, .custom-emergency-btn:hover {
            background-color: #d4ecea;
            transform: scale(1.02);
        }
        .modal-content {
            border-radius: 15px;
            background-color: #fff;
        }
        .modal-header {
            background-color: #93d6d0;
            color: #fff;
            border-radius: 15px 15px 0 0;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .custom-emergency-form {
            margin-top: 20px;
        }
        @media (max-width: 576px) {
            .container {
                padding: 20px 15px;
            }
            .section {
                padding: 20px;
            }
            h1 {
                font-size: 1.5rem;
            }
            h2 {
                font-size: 1.2rem;
            }
            .btn-primary, .emergency-list .btn, .custom-emergency-btn {
                font-size: 0.9rem;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6bfd0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Clinique Lemongo">
            <h1>Bienvenue à la Clinique Lemongo ! 😊</h1>
        </div>
        
        <!-- Symptom Section -->
        <div class="section">
            <h2>Parlez-nous de ce que vous ressentez</h2>
            <p>Expliquez ce qui ne va pas, on va trouver la bonne personne pour vous aider !</p>
            <form id="symptom-form" method="POST">
                <div class="mb-3">
                    <textarea class="form-control" id="symptoms" name="symptoms" rows="4" placeholder="Ex. : J’ai mal à la tête et je me sens tout étourdi..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <span class="material-icons">search</span> Voir ce qu’il faut faire
                </button>
            </form>
            <div id="symptom-error" class="error-message"></div>
        </div>

        <!-- Emergency Section -->
        <div class="section">
            <h2>Besoin d’aide urgente ? 🚑</h2>
            <p>Cliquez sur une situation ci-dessous ou ajoutez la vôtre !</p>
            <ul class="emergency-list">
                <?php
                $emergencies = [
                    'Brûlure grave',
                    'Accident de la circulation',
                    'Hémorragie interne',
                    'Crise d’épilepsie',
                    'Arrêt cardiaque'
                ];
                foreach ($emergencies as $emergency) {
                    echo "<li><button class='btn emergency-btn' data-emergency='" . htmlspecialchars($emergency) . "'>" . htmlspecialchars($emergency) . "</button></li>";
                }
                ?>
            </ul>
            <div class="custom-emergency-form">
                <h3>Autre urgence ?</h3>
                <form id="custom-emergency-form">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="custom-emergency" name="custom-emergency" placeholder="Ex. : Chute grave, intoxication...">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 custom-emergency-btn">
                        <span class="material-icons">add</span> Ajouter
                    </button>
                </form>
                <div id="custom-emergency-error" class="error-message"></div>
            </div>
        </div>

        <!-- Result Modal -->
        <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resultModalLabel">On a des infos pour vous !</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">On regarde...</span>
                            </div>
                            <p>Un instant, on vérifie !</p>
                        </div>
                        <div id="modal-content"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK, merci !</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Symptom form submission
            $('#symptom-form').on('submit', function(e) {
                e.preventDefault();
                const symptoms = $('#symptoms').val().trim();
                if (!symptoms) {
                    $('#symptom-error').text('Racontez-nous ce que vous ressentez !');
                    return;
                }

                $('#symptom-error').text('');
                $('#modal-content').hide();
                $('.loading').show();
                $('#resultModal').modal('show');

                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    data: { action: 'analyze_symptoms', symptoms: symptoms },
                    dataType: 'json',
                    success: function(response) {
                        $('.loading').hide();
                        $('#modal-content').show();
                        if (response.success) {
                            $('#modal-content').html(`
                                <p><strong>Spécialité conseillée :</strong> ${response.specialty}</p>
                                <p><strong>Médecin :</strong> ${response.doctor}</p>
                                <p><strong>Service :</strong> ${response.department}</p>
                                <p>Prenez rendez-vous avec ${response.doctor} pour aller mieux ! 😊</p>
                            `);
                        } else {
                            $('#modal-content').html(`<p class="text-danger">${response.error}</p>`);
                        }
                    },
                    error: function() {
                        $('.loading').hide();
                        $('#modal-content').show().html('<p class="text-danger">Oups, quelque chose ne va pas. Réessayez !</p>');
                    }
                });
            });

            // Predefined emergency buttons
            $('.emergency-btn').on('click', function() {
                const emergency = $(this).data('emergency');
                
                $('#modal-content').hide();
                $('.loading').show();
                $('#resultModal').modal('show');

                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    data: { action: 'emergency', emergency: emergency },
                    dataType: 'json',
                    success: function(response) {
                        $('.loading').hide();
                        $('#modal-content').show();
                        if (response.success) {
                            $('#modal-content').html(`
                                <h6>Pour « ${response.emergency} », voici quoi faire :</h6>
                                <div>${response.measures.replace(/\n/g, '<br>').replace(/-\s*/g, '• ')}</div>
                                <p>Restez calme, on est là ! 😊</p>
                            `);
                        } else {
                            $('#modal-content').html(`<p class="text-danger">${response.error}</p>`);
                        }
                    },
                    error: function() {
                        $('.loading').hide();
                        $('#modal-content').show().html('<p class="text-danger">Oups, un souci ! Réessayez. 😔</p>');
                    }
                });
            });

            // Custom emergency form
            $('#custom-emergency-form').on('submit', function(e) {
                e.preventDefault();
                const emergency = $('#custom-emergency').val().trim();
                if (!emergency) {
                    $('#custom-emergency-error').text('Dites-nous quelle urgence !');
                    return;
                }

                $('#custom-emergency-error').text('');
                $('#modal-content').hide();
                $('.loading').show();
                $('#resultModal').modal('show');

                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    data: { action: 'custom_emergency', emergency: emergency },
                    dataType: 'json',
                    success: function(response) {
                        $('.loading').hide();
                        $('#modal-content').show();
                        if (response.success) {
                            $('#modal-content').html(`
                                <h6>Pour « ${response.emergency} », voici quoi faire :</h6>
                                <div>${response.measures.replace(/\n/g, '<br>').replace(/-\s*/g, '• ')}</div>
                                <p>Restez calme, on est là ! 😊</p>
                            `);
                            $('#custom-emergency').val(''); // Clear input
                        } else {
                            $('#modal-content').html(`<p class="text-danger">${response.error}</p>`);
                        }
                    },
                    error: function() {
                        $('.loading').hide();
                        $('#modal-content').show().html('<p class="text-danger">Oups, un souci ! Réessayez. 😔</p>');
                    }
                });
            });
        });
    </script>
</body>
</html>