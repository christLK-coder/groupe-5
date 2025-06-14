<?php
session_start();
require_once 'connexion.php'; // Assure-toi que ta connexion PDO $pdo est bien configurée ici

// --- OpenRouter API Configuration ---
$openrouter_api_key = "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";
$openrouter_url = "https://openrouter.ai/api/v1/chat/completions";
$openrouter_model = "mistralai/mixtral-8x7b-instruct";

// Fonction API OpenRouter
function callOpenRouterAPI($prompt, $api_key, $url, $model) {
    $headers = [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json",
        "HTTP-Referer: https://your-domain.com",
        "X-Title: Clinique Lemongo"
    ];

    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['success' => false, 'error' => "Erreur API: Code $http_code"];
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => trim($result['choices'][0]['message']['content'])];
    }

    return ['success' => false, 'error' => 'Réponse API invalide'];
}

// Recherche d'un médecin par spécialité
function findDoctorBySpecialty($pdo, $specialty) {
    try {
        $stmt = $pdo->prepare("SELECT nom, prenom, specialite, departement FROM MEDECIN WHERE LOWER(specialite) LIKE LOWER(?) LIMIT 1");
        $stmt->execute(["%$specialty%"]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur DB : " . $e->getMessage());
        return false;
    }
}

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'analyze_symptoms') {
        $symptoms = trim($_POST['symptoms'] ?? '');
        if (empty($symptoms)) {
            echo json_encode(['success' => false, 'error' => 'Veuillez décrire vos symptômes.']);
            exit;
        }

        $prompt = "Tu es un assistant médical. Voici les symptômes d'un patient : « $symptoms ». Quelle spécialité médicale (ex: Cardiologie, Neurologie) serait la mieux adaptée ? Réponds uniquement par le nom de la spécialité.";

        $api_response = callOpenRouterAPI($prompt, $openrouter_api_key, $openrouter_url, $openrouter_model);

        if ($api_response['success']) {
            $specialty = trim($api_response['content']);
            $doctor = findDoctorBySpecialty($pdo, $specialty);

            if ($doctor) {
                echo json_encode([
                    'success' => true,
                    'specialty' => $doctor['specialite'],
                    'doctor' => "Dr. {$doctor['prenom']} {$doctor['nom']}",
                    'department' => $doctor['departement'] ?? 'Consultations'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => "Aucun médecin disponible pour la spécialité « $specialty »."]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $api_response['error']]);
        }
        exit;
    }

    if ($_POST['action'] === 'emergency') {
        $emergency = trim($_POST['emergency'] ?? '');
        if (empty($emergency)) {
            echo json_encode(['success' => false, 'error' => 'Veuillez décrire l’urgence.']);
            exit;
        }

        $prompt = "Un patient signale une urgence : « $emergency ». Donne des conseils clairs et pratiques à suivre immédiatement, avant qu’un médecin arrive. Sois rassurant.";

        $api_response = callOpenRouterAPI($prompt, $openrouter_api_key, $openrouter_url, $openrouter_model);

        if ($api_response['success']) {
            echo json_encode([
                'success' => true,
                'measures' => nl2br(htmlspecialchars($api_response['content']))
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $api_response['error']]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Assistant Clinique Lemongo</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        textarea, input[type="text"] { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
        button { padding: 10px 20px; border: none; background-color: #007BFF; color: white; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        #result, #emergency-result { margin-top: 15px; }
        .error { color: red; }
    </style>
</head>
<body> 

<h1>🤖 Assistant de la Clinique Lemongo</h1>

<div class="box">
    <h2>Analyser vos symptômes</h2>
    <form id="symptomForm">
        <label>Quels sont vos symptômes ?</label>
        <textarea name="symptoms" required></textarea>
        <button type="submit">Analyser</button>
    </form>
    <div id="result"></div>
</div>

<div class="box">
    <h2>En cas d’urgence</h2>
    <form id="emergencyForm">
        <label>Décrivez brièvement l’urgence</label>
        <input type="text" name="emergency" required>
        <button type="submit">Obtenir des conseils</button>
    </form>
    <div id="emergency-result"></div>
</div>

<script>
document.getElementById('symptomForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const symptoms = this.symptoms.value.trim();
    if (!symptoms) {
        alert("Merci d'entrer des symptômes.");
        return;
    }

    const response = await fetch('ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'analyze_symptoms', symptoms })
    });

    const result = await response.json();
    const container = document.getElementById('result');
    if (result.success) {
        container.innerHTML = `<p>🩺 Spécialité recommandée : <strong>${result.specialty}</strong><br>
        👨‍⚕️ Médecin disponible : <strong>${result.doctor}</strong><br>
        🏥 Département : <strong>${result.department}</strong></p>`;
    } else {
        container.innerHTML = `<p class="error">❌ ${result.error}</p>`;
    }
});

document.getElementById('emergencyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const emergency = this.emergency.value.trim();
    if (!emergency) {
        alert("Merci de décrire l'urgence.");
        return;
    }

    const response = await fetch('ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'emergency', emergency })
    });

    const result = await response.json();
    const container = document.getElementById('emergency-result');
    if (result.success) {
        container.innerHTML = `<p><strong>Conseils immédiats :</strong><br>${result.measures}</p>`;
    } else {
        container.innerHTML = `<p class="error">❌ ${result.error}</p>`;
    }
});
</script>

</body>
</html>
