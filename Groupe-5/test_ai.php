<?php
// test_ai.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Juste pour tester la réception des données
    $symptoms = $_POST['symptoms'] ?? '';

    // Réponse statique pour test
    echo json_encode([
        'success' => true,
        'received_symptoms' => $symptoms,
        'specialty' => 'Cardiologie (test)',
        'doctor' => 'Dr. Jean Dupont (test)',
        'department' => 'Cardio (test)'
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Test AI - Communication</title>
</head>
<body>
    <h2>Test formulaire symptôme</h2>
    <form id="symptomForm">
        <label for="symptoms">Symptômes :</label><br>
        <textarea name="symptoms" id="symptoms" rows="4" cols="50" placeholder="Entrez vos symptômes ici..."></textarea><br><br>
        <button type="submit">Analyser</button>
    </form>

    <div id="result" style="margin-top:20px; font-weight: bold;"></div>

    <script>
    document.getElementById('symptomForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const symptoms = this.symptoms.value.trim();

        if (!symptoms) {
            alert("Merci d'entrer des symptômes.");
            return;
        }

        try {
            const response = await fetch('test_ai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ symptoms })
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('result').innerHTML =
                    `<p>Symptômes reçus : <em>${result.received_symptoms}</em></p>` +
                    `<p>Spécialité recommandée : <strong>${result.specialty}</strong></p>` +
                    `<p>Médecin disponible : <strong>${result.doctor}</strong></p>` +
                    `<p>Département : <strong>${result.department}</strong></p>`;
            } else {
                document.getElementById('result').textContent = "Erreur dans la réponse.";
            }
        } catch (error) {
            console.error("Erreur fetch:", error);
            document.getElementById('result').textContent = "Erreur lors de la requête.";
        }
    });
    </script>
</body>
</html>
