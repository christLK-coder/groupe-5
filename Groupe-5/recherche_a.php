<?php
session_start();

// --- Constantes de configuration ---
const OPENROUTER_API_KEY = "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";
const OPENROUTER_URL = "https://openrouter.ai/api/v1/chat/completions";
const OPENROUTER_MODEL = "mistralai/mixtral-8x7b-instruct"; // Modèle puissant pour la concision

// Chemin vers le bundle de certificats CA pour cURL.
// Assure-toi que ce fichier est au même niveau que index.php
const CACERT_PATH = __DIR__ . DIRECTORY_SEPARATOR . "cacert.pem";

// --- DÉBUT : Suppression de la logique de connexion à la base de données ---
// Plus besoin de ces variables ni du bloc try-catch pour la DB
// $host = 'localhost';
// $dbname = 'hopital';
// $username = 'root';
// $password = '';

// $pdo = null; // Supprimé
// $db_connection_error = null; // Supprimé
// $available_specialties_names = []; // Non utilisé pour l'IA dans cette version
// $available_services_data = [];     // Non utilisé
// $specialties_by_id = [];           // Non utilisé
// $available_doctors_data = [];      // Non utilisé
// --- FIN : Suppression de la logique de connexion à la base de données ---


// Le message système est CLÉ pour diriger le comportement de l'IA.
// Il est modifié pour que l'IA suggère elle-même les spécialités/services.
$system_message_content = 'Vous êtes un assistant IA de pré-orientation médicale concis et direct.
Votre objectif est de déterminer la **spécialité médicale la plus probable**, le **type de service** et le **type de médecin** en fonction des symptômes décrits par l\'utilisateur.

**Règles Inflexibles :**
1.  **Concision Maximale :** Vos réponses doivent être très courtes et directes.
2.  **Format Strict pour Symptômes :** Pour les descriptions de symptômes, répondez **UNIQUEMENT** avec un format similaire à :
    "Vos symptômes sont très similaires à ceux d\'un problème de [DOMAINE_CORPOREL/TYPE_DE_MALADIE].
    Le service qui pourrait vous aider est : [NOM_DU_SERVICE_SUGGÉRÉ].
    Un médecin spécialisé pouvant vous aider est un [SPÉCIALITÉ_SUGGÉRÉE].
    Ceci est une pré-orientation, consultez rapidement un professionnel de santé pour un diagnostic et des soins adaptés."
    Exemples de réponses attendues pour symptômes:
    - "Vos symptômes sont très similaires à ceux d\'un problème de cœur. Le service qui pourrait vous aider est : Cardiologie. Un médecin spécialisé pouvant vous aider est un Cardiologue. Ceci est une pré-orientation, consultez rapidement un professionnel de santé pour un diagnostic et des soins adaptés."
    - "Vos symptômes sont très similaires à ceux d\'un problème de peau. Le service qui pourrait vous aider est : Dermatologie. Un médecin spécialisé pouvant vous aider est un Dermatologue. Ceci est une pré-orientation, consultez rapidement un professionnel de santé pour un diagnostic et des soins adaptés."
    - "Vos symptômes sont très similaires à ceux d\'un problème général. Le service qui pourrait vous aider est : Médecine Générale. Un médecin spécialisé pouvant vous aider est un Généraliste. Ceci est une pré-orientation, consultez rapidement un professionnel de santé pour un diagnostic et des soins adaptés."
    Si aucun des symptômes ne correspond clairement à une spécialité reconnaissable, ou si vous n\'êtes pas sûr, répondez : "Vos symptômes nécessitent une évaluation médicale. Veuillez consulter un médecin généraliste pour une première consultation. Ceci est une pré-orientation, consultez rapidement un professionnel de santé pour un diagnostic et des soins adaptés."
    Ne donnez pas d\'autres phrases ou explications que celles du format demandé.
3.  **Urgences - Étapes Précises :** Pour les urgences, donnez 2-3 étapes de premiers secours **extrêmement précises, courtes et faciles à appliquer immédiatement**. Ne donnez PAS de détails ou d\'explications supplémentaires ni de spécialités.
    Terminez toujours par : "Appelez immédiatement les urgences (112, 18, ou numéro local) !"
    Exemple: "1. Allongez la personne. 2. surélevez ses jambes. 3. desserrez ses vêtements. Appelez immédiatement les urgences (112, 18, ou numéro local) !"
4.  **Refus Non-Santé :** Refusez toute question non liée à la santé : "Cette question ne concerne pas la santé, je ne peux pas vous répondre. Reformulez pour qu’elle soit liée à la santé."
5.  **Pas de Diagnostic/Conseils Personnels :** Ne donnez JAMAIS de diagnostic, pronostic, ou conseils personnalisés. Recommandez toujours de consulter un professionnel.
6.  **Identification :** Identifiez-vous comme une IA, pas comme un médecin.';

// Initialiser l'historique de chat si non défini, en utilisant le message système dynamique
if (!isset($_SESSION['openrouter_chat_history'])) {
    $_SESSION['openrouter_chat_history'] = [
        ['role' => 'system', 'content' => $system_message_content]
    ];
} else {
    // S'assurer que le SYSTEM_MESSAGE est mis à jour (au cas où il y aurait des changements futurs)
    $_SESSION['openrouter_chat_history'][0]['content'] = $system_message_content;
}


// Gérer les requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response_data = ['success' => false, 'message' => '', 'ai_response' => ''];

    // --- DÉBUT : Suppression du bloc de vérification de connexion DB ---
    // if (isset($db_connection_error) && $_POST['action'] === 'analyze_symptoms') {
    //     $response_data['message'] = "Service indisponible pour l'analyse des symptômes (problème de base de données).";
    //     $response_data['ai_response'] = "<p style='color: red;'><strong>" . $response_data['message'] . "</strong></p>";
    //     echo json_encode($response_data);
    //     exit;
    // }
    // --- FIN : Suppression du bloc de vérification de connexion DB ---


    // Action pour vider le chat
    if (isset($_POST['action']) && $_POST['action'] === 'clear_chat') {
        // Réinitialise l'historique, en gardant le SYSTEM_MESSAGE mis à jour
        $_SESSION['openrouter_chat_history'] = [
            ['role' => 'system', 'content' => $system_message_content]
        ];
        echo json_encode(['success' => true, 'message' => 'Historique vidé !', 'ai_response' => 'L\'historique du chat a été vidé.']);
        exit;
    }

    // Gérer les symptômes ou les urgences
    if (isset($_POST['action']) && in_array($_POST['action'], ['analyze_symptoms', 'emergency'])) {
        $input = trim($_POST['input'] ?? '');

        // Validation côté serveur basique pour les entrées vides
        if (empty($input)) {
            $response_data['message'] = $_POST['action'] === 'analyze_symptoms' ? "Décris tes symptômes !" : "Quelle est l’urgence ?";
            $response_data['ai_response'] = "<p style='color: red;'><strong>" . $response_data['message'] . "</strong></p>";
            echo json_encode($response_data);
            exit;
        }

        try {
            $sanitized_input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $prompt_text = '';

            if ($_POST['action'] === 'analyze_symptoms') {
                // Pour les symptômes, l'IA doit nous donner la spécialité, service et médecin
                $prompt_text = "Propose le service, la spécialité et le type de médecin pour les symptômes suivants : " . $sanitized_input;
            } else { // emergency
                // Pour les urgences, le comportement reste inchangé
                $prompt_text = "Donne les étapes d'urgence pour : " . $sanitized_input;
            }

            // Ajouter l'entrée utilisateur à l'historique AVANT d'envoyer à l'API
            $_SESSION['openrouter_chat_history'][] = ['role' => 'user', 'content' => $prompt_text];

            $data = [
                'model' => OPENROUTER_MODEL,
                'messages' => $_SESSION['openrouter_chat_history'],
                'temperature' => 0.1, // Température plus basse pour des réponses plus déterministes (format strict)
                'max_tokens' => ($_POST['action'] === 'analyze_symptoms' ? 150 : 200) // Plus de tokens pour les symptômes car l'IA doit générer plus d'infos
            ];

            $ch = curl_init(OPENROUTER_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . OPENROUTER_API_KEY,
                    'Content-Type: application/json',
                    'HTTP-Referer: ' . $_SERVER['HTTP_HOST'],
                    'X-Title: Clinique Lemongo'
                ],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CAINFO         => CACERT_PATH, // Assure-toi que ce chemin est correct
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                throw new Exception("Erreur cURL lors de la requête API : $curl_error");
            }

            $response_api_data = json_decode($response, true);

            if ($http_code !== 200) {
                $msg = "Erreur de l'API OpenRouter (HTTP $http_code)";
                if (isset($response_api_data['error']['message'])) {
                    $msg .= ' : ' . $response_api_data['error']['message'];
                }
                throw new Exception($msg);
            }

            if (isset($response_api_data['choices'][0]['message']['content'])) {
                $ai_response_raw = trim($response_api_data['choices'][0]['message']['content']);
                // Ajouter la réponse de l'IA à l'historique
                $_SESSION['openrouter_chat_history'][] = ['role' => 'assistant', 'content' => $ai_response_raw];

                // Dans cette version, nous affichons directement la réponse de l'IA pour les symptômes
                // car elle contient toutes les informations (service, spécialité, etc.).
                $response_data['ai_response'] = nl2br(htmlspecialchars($ai_response_raw));

                $response_data['success'] = true;
                $response_data['message'] = "Réponse reçue !";
            } else {
                throw new Exception("Format de réponse de l'API inattendu ou contenu vide.");
            }
        } catch (Exception $e) {
            error_log("Erreur OpenRouter/Application : " . $e->getMessage());
            $response_data['message'] = "Une erreur est survenue lors du traitement. Veuillez réessayer. Si le problème persiste, contactez le support.";
            $response_data['ai_response'] = "<p style='color: red;'>Désolé, une erreur technique est survenue : " . htmlspecialchars($e->getMessage()) . ". Veuillez réessayer plus tard.</p>";
        }
    }
    echo json_encode($response_data);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinique Lemongo - À tes côtés ! 😊</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Comfortaa', sans-serif;
            background: linear-gradient(135deg, #f3fbfa 0%, #e0f4f3 100%);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            overflow: auto;
        }
        .container {
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 1000px;
            padding: 30px;
            animation: fadeIn 0.8s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        .header h1 {
            font-size: 2.5em;
            color: #93d6d0;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .header .subtitle {
            font-size: 1.1em;
            color: #666;
            margin-top: 10px;
        }
        .clear-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-size: 0.9em;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s, transform 0.2s;
        }
        .clear-btn:hover {
            background: #e55a5a;
            transform: scale(1.05);
        }
        .disclaimer {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 12px;
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
        }
        .symptom-form {
            margin-bottom: 40px;
        }
        .symptom-form label {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 15px;
            display: block;
        }
        .symptom-form textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #93d6d0;
            border-radius: 15px;
            font-size: 1em;
            resize: none;
            height: 120px;
            background: #f9fbfd;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .symptom-form textarea:focus {
            border-color: #7bc7c1;
            box-shadow: 0 0 10px rgba(123, 199, 193, 0.3);
            outline: none;
        }
        .symptom-form button {
            background: #93d6d0;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-size: 1.1em;
            cursor: pointer;
            display: block;
            margin: 15px auto 0;
            transition: background 0.3s, transform 0.2s;
        }
        .symptom-form button:hover {
            background: #7bc7c1;
            transform: scale(1.05);
        }
        .emergencies h2 {
            font-size: 1.4em;
            color: #555;
            margin-bottom: 20px;
            text-align: center;
        }
        .emergency-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .emergency-btn {
            background: #e9f6f5;
            color: #333;
            border: 2px solid #93d6d0;
            border-radius: 12px;
            padding: 15px;
            font-size: 0.95em;
            cursor: pointer;
            text-align: center;
            transition: background 0.3s, transform 0.2s;
        }
        .emergency-btn:hover {
            background: #d4ecea;
            transform: scale(1.03);
        }
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .popup {
            background: #fff;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            animation: popupIn 0.4s ease;
        }
        @keyframes popupIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .popup-header {
            background: linear-gradient(135deg, #93d6d0 0%, #7bc7c1 100%);
            color: white;
            padding: 15px 20px;
            font-size: 1.2em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .popup-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .popup-header .close-btn:hover {
            transform: scale(1.2);
        }
        .popup-content {
            padding: 20px;
            font-size: 1em;
            line-height: 1.6;
            color: #333;
        }
        .popup-content strong {
            color: #93d6d0;
        }
        .error-display {
            display: none !important;
        }
        .loading-indicator {
            display: none;
            text-align: center;
            margin-top: 20px;
            font-size: 1.1em;
            color: #93d6d0;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                border-radius: 15px;
            }
            .header h1 {
                font-size: 2em;
            }
            .emergency-btn {
                font-size: 0.9em;
                padding: 12px;
            }
            .popup {
                width: 95%;
            }
            .clear-btn {
                position: static;
                margin-top: 15px;
                width: fit-content;
                float: right;
            }
            .header {
                text-align: left;
                padding-right: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Clinique Lemongo</h1>
            <p class="subtitle">On est là pour t’aider ! 😊</p>
            <button id="clearBtn" class="clear-btn">
                <i class='bx bx-trash'></i> Vider
            </button>
        </div>
        <div class="disclaimer">
            <p><strong>Attention :</strong> Je suis une IA, pas un médecin. Mes conseils sont des pré-orientations. Consulte un pro pour tout souci !</p>
        </div>
        <div class="symptom-form">
            <label for="symptoms">Quels sont tes symptômes ?</label>
            <textarea id="symptoms" placeholder="Ex. : Douleurs thoraciques, essoufflement..."></textarea>
            <button id="analyzeBtn">Analyser</button>
        </div>
        <div class="emergencies">
            <h2>Urgences fréquentes</h2>
            <div class="emergency-buttons">
                <?php
                $emergencies = ['Brûlure grave', 'Accident de la circulation', 'Hémorragie interne', 'Crise d’épilepsie', 'Arrêt cardiaque'];
                foreach ($emergencies as $emergency) {
                    echo "<button class='emergency-btn' data-emergency='" . htmlspecialchars($emergency) . "'>" . htmlspecialchars($emergency) . "</button>";
                }
                ?>
            </div>
        </div>
        <div id="loadingIndicator" class="loading-indicator" style="display:none;">
            Chargement... Merci de patienter !
        </div>
        <div id="errorMessage" class="error-display"></div>
    </div>
    <div class="popup-overlay" id="popupOverlay">
        <div class="popup">
            <div class="popup-header">
                <span>Résultat</span>
                <button class="close-btn" id="closePopup">×</button>
            </div>
            <div class="popup-content" id="popupContent"></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const symptomsInput = document.getElementById('symptoms');
            const analyzeBtn = document.getElementById('analyzeBtn');
            const emergencyButtons = document.querySelectorAll('.emergency-btn');
            const clearBtn = document.getElementById('clearBtn');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const popupOverlay = document.getElementById('popupOverlay');
            const popupContent = document.getElementById('popupContent');
            const closePopup = document.getElementById('closePopup');

            const showPopup = (content) => {
                popupContent.innerHTML = content;
                popupOverlay.style.display = 'flex';
            };

            const hidePopup = () => {
                popupOverlay.style.display = 'none';
            };

            const toggleLoading = (isLoading) => {
                loadingIndicator.style.display = isLoading ? 'block' : 'none';
                symptomsInput.disabled = isLoading;
                analyzeBtn.disabled = isLoading;
                emergencyButtons.forEach(btn => btn.disabled = isLoading);
                clearBtn.disabled = isLoading;
            };

            const sendRequest = async (action, input) => {
                if (!input.trim() && action !== 'clear_chat') {
                    showPopup("<p style='color: red;'><strong>" + (action === 'analyze_symptoms' ? "Décris tes symptômes !" : "Quelle est l’urgence ?") + "</strong></p>");
                    return;
                }

                toggleLoading(true);

                try {
                    const response = await fetch('index.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${action}&input=${encodeURIComponent(input)}`
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error("Network response was not ok:", errorText);
                        let errorMessage = `Erreur réseau : HTTP ${response.status}`;
                        try {
                            const errorData = JSON.parse(errorText);
                            errorMessage = errorData.message || errorMessage;
                        } catch (e) {
                            // Not JSON, use generic message
                        }
                        throw new Error(errorMessage);
                    }

                    const data = await response.json();
                    if (data.ai_response) {
                        showPopup(data.ai_response);
                        if (action === 'analyze_symptoms') {
                            symptomsInput.value = '';
                        }
                    } else if (data.message && !data.success) {
                        showPopup("<p style='color: red;'><strong>" + data.message + "</strong></p>");
                    } else if (data.message && data.success) {
                         showPopup("<p style='color: green;'><strong>" + data.message + "</strong></p>");
                    }

                } catch (error) {
                    showPopup("<p style='color: red;'>Désolé, une erreur technique est survenue : " + error.message + ". Veuillez réessayer plus tard.</p>");
                } finally {
                    toggleLoading(false);
                    symptomsInput.focus();
                }
            };

            const clearHistory = async () => {
                if (!confirm("Veux-tu vraiment vider l’historique de chat ?")) return;
                await sendRequest('clear_chat', '');
            };

            analyzeBtn.addEventListener('click', () => {
                sendRequest('analyze_symptoms', symptomsInput.value);
            });

            symptomsInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendRequest('analyze_symptoms', symptomsInput.value);
                }
            });

            emergencyButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    sendRequest('emergency', btn.dataset.emergency);
                });
            });

            clearBtn.addEventListener('click', clearHistory);

            closePopup.addEventListener('click', hidePopup);
            popupOverlay.addEventListener('click', (e) => {
                if (e.target === popupOverlay) hidePopup();
            });

            symptomsInput.addEventListener('input', () => {
                symptomsInput.style.height = 'auto';
                symptomsInput.style.height = symptomsInput.scrollHeight + 'px';
            });
        });
    </script>
</body>
</html>