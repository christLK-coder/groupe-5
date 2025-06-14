<?php
session_start();

// --- IMPORTANT: Verify if the patient is logged in ---
if (!isset($_SESSION['id_patient'])) {
    header("Location: connexion_patient.php");
    exit;
}

// --- Handle CLEAR CHAT request ---
// This part will be executed if the "Clear Chat" button is clicked via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_chat') {
    // Reset the chat history in the session, including the system message
    $_SESSION['openrouter_chat_history'] = [
        [
            'role' => 'system',
            'content' => 'Vous êtes un assistant IA **exclusivement spécialisé dans la santé et le bien-être humain**. Votre rôle est de fournir des informations générales et éducatives sur des sujets tels que les maladies, les symptômes, la prévention, la nutrition, l\'exercice, l\'anatomie, et la physiologie.

            **Règles Inflexibles (À Appliquer EN PRIORITÉ absolue) :**

            1.  **Refus Catégorique et Immédiat :** Si la question de l\'utilisateur **ne concerne PAS DIRECTEMENT la santé humaine ou un sujet médical**, vous devez **IMPÉRATIVEMENT ET INSTANTANÉMENT** refuser d\'y répondre.
            2.  **Réponse de Refus Unique :** La **SEULE ET UNIQUE réponse autorisée** pour une question hors-sujet est la suivante, **sans aucune modification ni ajout de contexte, définition, ou explication supplémentaire** :
                "Cette question ne concerne pas la santé donc je ne peux pas vous répondre. S\'il vous plaît, veuillez reformuler votre question pour qu\'elle soit liée au domaine de la santé."
            3.  **Pas de Conseils Médicaux :** Vous ne devez JAMAIS donner de diagnostic, de pronostic, ou de conseils de traitement personnalisés. Recommandez TOUJOURS de consulter un professionnel de la santé pour tout problème personnel.
            4.  **Identification IA :** Rappelez toujours que vous êtes une IA, pas un médecin.
            '
        ]
    ];
    echo json_encode(['success' => true, 'message' => 'Chat cleared successfully.']);
    exit; // Stop script execution after AJAX processing
}

// --- OpenRouter API Configuration ---
$openrouter_api_key = "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";
$openrouter_url = "https://openrouter.ai/api/v1/chat/completions";
$openrouter_model = "mistralai/mixtral-8x7b-instruct"; // Choose your preferred model here

// Initialize chat history if not already set, with the strict system message
if (!isset($_SESSION['openrouter_chat_history'])) {
    $_SESSION['openrouter_chat_history'] = [
        [
            'role' => 'system',
            'content' => 'Vous êtes un assistant IA **exclusivement spécialisé dans la santé et le bien-être humain**. Votre rôle est de fournir des informations générales et éducatives sur des sujets tels que les maladies, les symptômes, la prévention, la nutrition, l\'exercice, l\'anatomie, et la physiologie.

            **Règles Inflexibles (À Appliquer EN PRIORITÉ absolue) :**

            1.  **Refus Catégorique et Immédiat :** Si la question de l\'utilisateur **ne concerne PAS DIRECTEMENT la santé humaine ou un sujet médical**, vous devez **IMPÉRATIVEMENT ET INSTANTANÉMENT** refuser d\'y répondre.
            2.  **Réponse de Refus Unique :** La **SEULE ET UNIQUE réponse autorisée** pour une question hors-sujet est la suivante, **sans aucune modification ni ajout de contexte, définition, ou explication supplémentaire** :
                "Cette question ne concerne pas la santé donc je ne peux pas vous répondre. S\'il vous plaît, veuillez reformuler votre question pour qu\'elle soit liée au domaine de la santé."
            3.  **Pas de Conseils Médicaux :** Vous ne devez JAMAIS donner de diagnostic, de pronostic, ou de conseils de traitement personnalisés. Recommandez TOUJOURS de consulter un professionnel de la santé pour tout problème personnel.
            4.  **Identification IA :** Rappelez toujours que vous êtes une IA, pas un médecin.
            '
        ]
    ];
}

// This PHP part will only be executed if it's an AJAX POST request for a user question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_question'])) {
    header('Content-Type: application/json'); // Indicate that the response will be JSON

    $user_question = trim($_POST['user_question']);
    $response_data = ['success' => false, 'message' => '', 'ai_response' => ''];

    if (!empty($user_question)) {
        try {
            // Add user's new question to the chat history
            $_SESSION['openrouter_chat_history'][] = ['role' => 'user', 'content' => $user_question];

            // Prepare the data for the API request
            $data = [
                'model' => $openrouter_model,
                'messages' => $_SESSION['openrouter_chat_history'],
                'temperature' => 0.7, // Adjust for creativity (higher) or factual accuracy (lower)
                'max_tokens' => 500, // Limit the length of the AI's response
            ];

            $ch = curl_init($openrouter_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $openrouter_api_key,
                'Content-Type: application/json',
                'HTTP-Referer: ' . $_SERVER['HTTP_HOST'], // Required by OpenRouter for some requests
                'X-Title: Hospital Patient Assistant',     // Recommended by OpenRouter for analytics
            ]);

            // If you still encounter SSL errors after fixing the system's cacert.pem,
            // you *could* uncomment the line below, but it's generally NOT recommended for production
            // as it disables SSL certificate verification, making your connection insecure.
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                throw new Exception("cURL Error: " . $curl_error);
            }

            $response_api_data = json_decode($response, true);

            if ($http_code !== 200) {
                $api_error_message = 'OpenRouter API Error: HTTP ' . $http_code;
                if (isset($response_api_data['error']['message'])) {
                    $api_error_message .= ' - ' . $response_api_data['error']['message'];
                }
                throw new Exception($api_error_message);
            }

            if (isset($response_api_data['choices'][0]['message']['content'])) {
                $ai_response_content = $response_api_data['choices'][0]['message']['content'];
                // Add AI's response to the chat history
                $_SESSION['openrouter_chat_history'][] = ['role' => 'assistant', 'content' => $ai_response_content];

                $response_data['success'] = true;
                $response_data['ai_response'] = $ai_response_content;
                $response_data['message'] = "AI response received.";

            } else {
                $response_data['message'] = "Error: AI response is not in expected format.";
                error_log("OpenRouter malformed response: " . print_r($response_api_data, true));
            }

        } catch (Exception $e) {
            $response_data['message'] = "Error communicating with AI: " . $e->getMessage();
            error_log("OpenRouter API Error: " . $e->getMessage()); // Log the error for debugging
        }
    } else {
        $response_data['message'] = "Please enter a question.";
    }
    echo json_encode($response_data);
    exit; // Stop script execution after AJAX response
}
// End of AJAX question handling

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat avec l'IA - Assistance Santé</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
    <style>
        /* General Body Styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f0f2f5; /* Light gray background */
    margin: 0;
    padding: 0;
    min-height: 100vh;
    color: #333;
}
.body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f3fbfa; /* Light gray background */
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: #333;

}

/* Chat Container */
.chat-container {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 760px; /* Wider for better readability */
    height: 90vh; /* Make it tall like a chat app */
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Hide overflow from rounded corners */
}

/* Chat Header */
.chat-header {
    position: relative; /* IMPORTANT: For the absolutely positioned clear button */
    background-color: #007bff; /* Primary blue for header */
    color: white;
    padding: 20px;
    text-align: center;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.chat-header h1 {
    margin: 0;
    font-size: 1.8em;
    font-weight: 600;
}

.chat-header .subtitle {
    margin: 5px 0 0;
    font-size: 0.9em;
    opacity: 0.9;
}

/* Clear Chat Button */
.clear-chat-btn {
    position: absolute; /* Position the button relative to the header */
    top: 20px; /* Adjust vertical position */
    right: 20px; /* Adjust horizontal position */
    background-color: #dc3545; /* Red color for delete action */
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 15px;
    font-size: 0.9em;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: flex; /* Allows aligning icon and text */
    align-items: center;
    gap: 5px; /* Space between icon and text */
}

.clear-chat-btn:hover {
    background-color: #c82333; /* Darker shade on hover */
}

.clear-chat-btn i {
    font-size: 1.1em; /* Adjust icon size */
}

/* Disclaimer Banner */
.disclaimer-banner {
    background-color: #fff3cd; /* Light yellow for warning */
    color: #856404; /* Darker yellow text */
    padding: 12px 20px;
    font-size: 0.85em;
    text-align: center;
    border-bottom: 1px solid #ffeeba;
    box-shadow: inset 0 -1px 5px rgba(0,0,0,0.05);
}

.disclaimer-banner strong {
    color: #664d03;
}

/* Chat Messages Area */
.chat-messages {
    flex-grow: 1; /* Takes up available space */
    padding: 20px;
    overflow-y: auto; /* Scrollable messages */
    background-color: #f9fbfd; /* Very light background for messages */
    scroll-behavior: smooth; /* Smooth scrolling to new messages */
}

/* Individual Message Styling */
.message {
    display: flex;
    margin-bottom: 15px;
    align-items: flex-start;
}

.message .sender {
    font-weight: bold;
    margin-right: 10px;
    min-width: 45px; /* Ensures sender name has some space */
    text-align: right;
    padding-top: 4px;
    color: #555;
    font-size: 0.85em;
}

.message .content {
    padding: 12px 15px;
    border-radius: 18px;
    max-width: 75%; /* Limit message width */
    line-height: 1.6;
    word-wrap: break-word; /* Break long words */
}

/* User Message */
.message.user {
    justify-content: flex-end; /* Align user messages to the right */
}

.message.user .content {
    background-color: #007bff; /* Blue for user messages */
    color: white;
    border-bottom-right-radius: 4px; /* Sharper corner for user message bubble */
}
.message.user .sender {
    order: 2; /* Move sender name after content for right alignment */
    text-align: left;
    margin-right: 0;
    margin-left: 10px;
}

/* AI Message */
.message.assistant .content {
    background-color: #e2e8f0; /* Light gray for AI messages */
    color: #333;
    border-bottom-left-radius: 4px; /* Sharper corner for AI message bubble */
}

/* Info Message (e.g., welcome message) */
.message.info-message {
    justify-content: center;
    text-align: center;
    margin-top: 20px;
}
.message.info-message .content {
    background-color: #e9f5e9; /* Light green for info */
    color: #28a745;
    font-style: italic;
    border: 1px solid #c3e6cb;
    max-width: 85%;
}
.message.info-message .sender {
    display: none; /* Hide sender for info messages */
}


/* Chat Input Area */
.chat-input-area {
    display: flex;
    padding: 15px 20px;
    background-color: #f9f9f9;
    border-top: 1px solid #eee;
    align-items: center;
}

.chat-input-area textarea {
    flex-grow: 1; /* Takes up most of the space */
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 20px;
    font-size: 1em;
    resize: none; /* Disable vertical resizing */
    max-height: 100px; /* Limit max height for textarea */
    overflow-y: auto;
    margin-right: 10px;
    transition: all 0.2s ease-in-out;
}

.chat-input-area textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    outline: none;
}

.chat-input-area button {
    background-color: #28a745; /* Green send button */
    color: white;
    border: none;
    border-radius: 50%; /* Circular button */
    width: 45px;
    height: 45px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    font-size: 1.5em; /* Icon size */
    transition: background-color 0.2s ease, transform 0.1s ease;
    flex-shrink: 0; /* Prevent button from shrinking */
}

.chat-input-area button:hover {
    background-color: #218838;
    transform: scale(1.05);
}

.chat-input-area button:active {
    transform: scale(0.98);
}

/* Error Display */
.error-display {
    color: white;
    background-color: #dc3545; /* Red for errors */
    padding: 10px 20px;
    text-align: center;
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
    font-size: 0.9em;
    display: none; /* Hidden by default, shown by JS */
}
    </style>
</head>
<body>
    <a href="mon_compte.php"><i class="bx bx-arrow-back" style= "color: green; font-size: 20px; margin: 20px; position: fixed;"></i></a>
    <section class="body">
        <div class="chat-container">
        <header class="chat-header">
            <h1>Chat avec l'IA</h1>
            <p class="subtitle">Assistant de santé généraliste</p>
            <button id="clearChatBtn" class="clear-chat-btn">
                <i class='bx bx-trash'></i> Vider le chat
            </button>
        </header>

        <div class="disclaimer-banner">
            <p><strong>Avertissement :</strong> Cette IA fournit des informations générales et ne remplace pas un avis médical professionnel. Consultez toujours un médecin pour tout problème de santé.</p>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php
            // Display initial chat history (excluding the system message)
            if (count($_SESSION['openrouter_chat_history']) > 1) {
                for ($i = 1; $i < count($_SESSION['openrouter_chat_history']); $i++) {
                    $entry = $_SESSION['openrouter_chat_history'][$i];
                    $role_class = htmlspecialchars($entry['role']);
                    $sender = ($entry['role'] === 'user') ? 'Vous' : 'IA';
                    echo '<div class="message ' . $role_class . '">';
                    echo '<span class="sender">' . $sender . '</span>';
                    echo '<div class="content">' . nl2br(htmlspecialchars($entry['content'])) . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="message info-message">';
                echo '<span class="sender">Système</span>';
                echo '<div class="content">Bienvenue ! Posez-moi une question sur la santé générale.</div>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="chat-input-area">
            <textarea id="userQuestion" placeholder="Écrivez votre message..." rows="1"></textarea>
            <button id="sendMessageBtn">
                <i class='bx bx-send'></i> </button>
        </div>

        <div id="errorMessage" class="error-display"></div>
    </div>

    </section>

    
    <script>
         document.addEventListener('DOMContentLoaded', () => {
    const chatMessages = document.getElementById('chatMessages');
    const userQuestionInput = document.getElementById('userQuestion');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const errorMessageDisplay = document.getElementById('errorMessage');
    const clearChatBtn = document.getElementById('clearChatBtn'); // New button element

    // Function to scroll to the bottom of the chat
    const scrollToBottom = () => {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    // Function to display messages in the chat UI
    const appendMessage = (sender, content, roleClass) => {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', roleClass);

        const senderSpan = document.createElement('span');
        senderSpan.classList.add('sender');
        senderSpan.textContent = sender;

        const contentDiv = document.createElement('div');
        contentDiv.classList.add('content');
        contentDiv.innerHTML = content.replace(/\n/g, '<br>'); // Preserve newlines

        if (roleClass === 'user') {
            messageDiv.appendChild(contentDiv);
            messageDiv.appendChild(senderSpan);
        } else {
            messageDiv.appendChild(senderSpan);
            messageDiv.appendChild(contentDiv);
        }

        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    };

    // Function to display error messages
    const displayError = (message) => {
        errorMessageDisplay.textContent = message;
        errorMessageDisplay.style.display = 'block';
        setTimeout(() => {
            errorMessageDisplay.style.display = 'none';
        }, 5000); // Hide after 5 seconds
    };

    // Handle sending message
    const sendMessage = async () => {
        const question = userQuestionInput.value.trim();
        if (question === '') {
            displayError("Veuillez taper votre question.");
            return;
        }

        // Add user's message to UI immediately
        appendMessage('Vous', question, 'user');
        userQuestionInput.value = ''; // Clear input field

        // Disable input and button while waiting for response
        userQuestionInput.disabled = true;
        sendMessageBtn.disabled = true;
        sendMessageBtn.innerHTML = '<i class="bx bx-loader bx-spin"></i>'; // Loading spinner

        try {
            const response = await fetch('ia_patient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_question=${encodeURIComponent(question)}`
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
            }

            const data = await response.json();

            if (data.success) {
                appendMessage('IA', data.ai_response, 'assistant');
            } else {
                displayError(data.message || "Une erreur inattendue est survenue.");
            }

        } catch (error) {
            console.error('Fetch error:', error);
            displayError("Erreur de communication : " + error.message);
        } finally {
            // Re-enable input and button
            userQuestionInput.disabled = false;
            sendMessageBtn.disabled = false;
            sendMessageBtn.innerHTML = '<i class="bx bx-send"></i>'; // Reset icon
            userQuestionInput.focus(); // Focus back to input
            scrollToBottom();
        }
    };

    // --- NEW: Handle clearing chat ---
    const clearChat = async () => {
        if (!confirm("Êtes-vous sûr de vouloir vider l'historique du chat ?")) {
            return; // Cancel if the user doesn't confirm
        }

        // Visually clear messages from the chat area
        chatMessages.innerHTML = '';
        // Add a fresh welcome message
        appendMessage('Système', 'Bienvenue ! Posez-moi une question sur la santé générale.', 'info-message');

        try {
            const response = await fetch('ia_patient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=clear_chat` // Send a specific action to the PHP script
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                displayError(data.message || "Erreur lors du vidage du chat côté serveur.");
            } else {
                // If needed, you could add a temporary success message here
                // displayError("Chat vidé avec succès !", 'success');
            }

        } catch (error) {
            console.error('Clear chat error:', error);
            displayError("Erreur lors de la communication avec le serveur pour vider le chat : " + error.message);
        } finally {
            scrollToBottom(); // Ensure it scrolls to the top of the new welcome message
        }
    };

    // Event Listeners
    sendMessageBtn.addEventListener('click', sendMessage);
    clearChatBtn.addEventListener('click', clearChat); // Add event listener for the new button

    userQuestionInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { // Send on Enter, allow Shift+Enter for new line
            e.preventDefault(); // Prevent default Enter behavior (new line)
            sendMessage();
        }
    });

    // Auto-resize textarea
    userQuestionInput.addEventListener('input', () => {
        userQuestionInput.style.height = 'auto'; // Reset height
        userQuestionInput.style.height = userQuestionInput.scrollHeight + 'px'; // Set to scroll height
    });

    // Scroll to bottom on page load to see the latest messages
    scrollToBottom();
});
    </script>
</body>
</html>