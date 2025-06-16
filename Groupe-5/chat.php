<?php
session_start();

require_once 'connexion.php';

// Vérifier si le patient est connecté
if (!isset($_SESSION['id_patient'])) {
    header("Location: connexion_patient.php");
    exit;
}

$id_patient_connecte = $_SESSION['id_patient'];
$current_conversation_id = null;
$current_medecin_id = null;
$current_medecin_nom_complet = 'Sélectionnez une conversation';
$messages = [];
$error_message = '';

// --- Gestion des requêtes AJAX pour l'envoi de messages ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_message_id' => null, 'timestamp' => null];

    // Vérifier que conversation_id est fourni
    if (!isset($_POST['conversation_id']) || !is_numeric($_POST['conversation_id'])) {
        $response['message'] = "ID de conversation manquant ou invalide.";
        echo json_encode($response);
        exit;
    }

    $conversation_id = intval($_POST['conversation_id']);
    $message_content = trim($_POST['message_content'] ?? '');

    if (empty($message_content)) {
        $response['message'] = "Le message ne peut pas être vide.";
        echo json_encode($response);
        exit;
    }

    try {
        // Vérifier que la conversation appartient au patient
        $stmt_check = $pdo->prepare("SELECT id_conversation FROM CONVERSATION WHERE id_conversation = ? AND id_patient = ?");
        $stmt_check->execute([$conversation_id, $id_patient_connecte]);
        if (!$stmt_check->fetch()) {
            $response['message'] = "Conversation non autorisée.";
            echo json_encode($response);
            exit;
        }

        // Insérer le message dans la base de données
        $stmt_insert_msg = $pdo->prepare("
            INSERT INTO MESSAGE (id_conversation, id_expediteur, type_expediteur, contenu, date_message)
            VALUES (?, ?, 'patient', ?, NOW())
        ");
        $stmt_insert_msg->execute([$conversation_id, $id_patient_connecte, $message_content]);

        $response['success'] = true;
        $response['message'] = "Message envoyé avec succès.";
        $response['new_message_id'] = $pdo->lastInsertId();
        $response['timestamp'] = date('H:i');

    } catch (PDOException $e) {
        // Journaliser l'erreur pour le débogage
        error_log("Erreur lors de l'envoi du message : " . $e->getMessage());
        $response['message'] = "Erreur serveur lors de l'envoi du message.";
    }

    echo json_encode($response);
    exit;
}

// --- Logique de sélection ou création de conversation ---
if (isset($_GET['conversation_id'])) {
    $selected_conversation_id = intval($_GET['conversation_id']);
    try {
        $stmt = $pdo->prepare("SELECT id_conversation, id_medecin FROM CONVERSATION WHERE id_conversation = ? AND id_patient = ?");
        $stmt->execute([$selected_conversation_id, $id_patient_connecte]);
        $conversation_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conversation_data) {
            $current_conversation_id = $conversation_data['id_conversation'];
            $current_medecin_id = $conversation_data['id_medecin'];
        } else {
            $error_message = "Conversation introuvable ou non autorisée.";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données lors de la sélection de la conversation: " . $e->getMessage();
    }
} elseif (isset($_GET['medecin_id_cible'])) {
    $target_medecin_id = intval($_GET['medecin_id_cible']);
    try {
        $stmt_check_conv = $pdo->prepare("SELECT id_conversation FROM CONVERSATION WHERE id_patient = ? AND id_medecin = ?");
        $stmt_check_conv->execute([$id_patient_connecte, $target_medecin_id]);
        $conversation = $stmt_check_conv->fetch(PDO::FETCH_ASSOC);

        if ($conversation) {
            $current_conversation_id = $conversation['id_conversation'];
            $current_medecin_id = $target_medecin_id;
        } else {
            $stmt_create_conv = $pdo->prepare("INSERT INTO CONVERSATION (id_patient, id_medecin) VALUES (?, ?)");
            $stmt_create_conv->execute([$id_patient_connecte, $target_medecin_id]);
            $current_conversation_id = $pdo->lastInsertId();
            $current_medecin_id = $target_medecin_id;
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données lors de la création/recherche de la conversation : " . $e->getMessage();
    }
}

// --- Récupérer les informations du médecin actif et les messages ---
if ($current_conversation_id && $current_medecin_id) {
    try {
        $stmt_medecin_name = $pdo->prepare("SELECT nom, prenom FROM MEDECIN WHERE id_medecin = ?");
        $stmt_medecin_name->execute([$current_medecin_id]);
        $medecin_data = $stmt_medecin_name->fetch(PDO::FETCH_ASSOC);
        if ($medecin_data) {
            $current_medecin_nom_complet = htmlspecialchars($medecin_data['prenom'] . ' ' . $medecin_data['nom']);
        } else {
            $current_medecin_nom_complet = 'Médecin Inconnu';
        }

        $stmt_messages = $pdo->prepare("
            SELECT id_message, id_expediteur, type_expediteur, contenu, date_message
            FROM MESSAGE
            WHERE id_conversation = ?
            ORDER BY date_message ASC
        ");
        $stmt_messages->execute([$current_conversation_id]);
        $messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données lors du chargement des messages : " . $e->getMessage();
    }
}

// --- Récupérer toutes les conversations du patient ---
$conversations_list = [];
try {
    $stmt_conversations = $pdo->prepare("
        SELECT
            C.id_conversation,
            M.nom AS medecin_nom,
            M.prenom AS medecin_prenom,
            (SELECT contenu FROM MESSAGE WHERE id_conversation = C.id_conversation ORDER BY date_message DESC LIMIT 1) AS last_message_content,
            (SELECT date_message FROM MESSAGE WHERE id_conversation = C.id_conversation ORDER BY date_message DESC LIMIT 1) AS last_message_date
        FROM CONVERSATION C
        JOIN MEDECIN M ON C.id_medecin = M.id_medecin
        WHERE C.id_patient = ?
        ORDER BY last_message_date DESC, C.date_creation DESC
    ");
    $stmt_conversations->execute([$id_patient_connecte]);
    $conversations_list = $stmt_conversations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors du chargement des conversations : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Conversations - Chat</title>
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
    <link rel="stylesheet" href="chat_css.css">
</head>
<body>
    <div class="main-chat-wrapper">
        <div class="conversation-list">
            <a href="mon_compte.php"><i class="bx bx-arrow-back" style="color: green; font-size: 20px; margin: 20px;"></i></a>
            <h2>Mes Conversations</h2>
            <?php if (empty($conversations_list)): ?>
                <p style="text-align: center; padding: 20px; color: #777;">
                    Vous n'avez pas encore de conversations.
                </p>
            <?php else: ?>
                <?php foreach ($conversations_list as $conv_item): ?>
                    <?php
                        $is_active = ($conv_item['id_conversation'] == $current_conversation_id) ? 'active' : '';
                        $medecin_full_name = htmlspecialchars($conv_item['medecin_prenom'] . ' ' . $conv_item['medecin_nom']);
                        $last_msg = htmlspecialchars($conv_item['last_message_content'] ?? 'Pas de message');
                        $last_msg_time = $conv_item['last_message_date'] ? date('d/m H:i', strtotime($conv_item['last_message_date'])) : '';
                    ?>
                    <a href="chat.php?conversation_id=<?php echo $conv_item['id_conversation']; ?>" class="conversation-item <?php echo $is_active; ?>">
                        <h3>Dr. <?php echo $medecin_full_name; ?></h3>
                        <p><?php echo $last_msg; ?></p>
                        <span class="last-message-time"><?php echo $last_msg_time; ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-area">
            <div class="chat-header">
                Conversation avec <?php echo $current_medecin_nom_complet; ?>
            </div>
            <div class="chat-messages" id="chatMessages">
                <?php if ($current_conversation_id === null): ?>
                    <p class="no-conversation-selected">Sélectionnez une conversation à gauche pour commencer.</p>
                <?php elseif (empty($messages)): ?>
                    <p style="text-align: center; color: #555;">Commencez cette nouvelle conversation !</p>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <?php
                            $is_patient_message = ($message['type_expediteur'] === 'patient');
                            $sender_name = $is_patient_message ? 'Vous' : 'Dr. ' . $current_medecin_nom_complet;
                        ?>
                        <div class="message-bubble <?php echo $is_patient_message ? 'message-patient' : 'message-medecin'; ?>" data-message-id="<?php echo $message['id_message']; ?>">
                            <strong><?php echo $sender_name; ?>:</strong>
                            <?php echo nl2br(htmlspecialchars($message['contenu'])); ?>
                            <span class="message-time"><?php echo date('H:i', strtotime($message['date_message'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form class="chat-input" id="messageForm">
                <?php if ($current_conversation_id): ?>
                    <textarea name="message_content" id="messageInput" placeholder="Écrivez votre message..." rows="1"></textarea>
                    <button type="submit" id="sendMessageBtn">Envoyer</button>
                <?php else: ?>
                    <p style="text-align: center; flex-grow: 1; color: #777;">Sélectionnez une conversation pour envoyer un message.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        const currentConversationId = <?php echo json_encode($current_conversation_id); ?>;
        const currentMedecinNomComplet = <?php echo json_encode($current_medecin_nom_complet); ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatMessagesContainer = document.getElementById('chatMessages');
            const messageInput = document.getElementById('messageInput');
            const messageForm = document.getElementById('messageForm');
            const sendMessageBtn = document.getElementById('sendMessageBtn');

            let lastMessageId = 0;

            const scrollToBottom = () => {
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            };

            const appendMessage = (senderName, content, isPatientMessage, messageId, messageTime) => {
                const messageBubble = document.createElement('div');
                messageBubble.classList.add('message-bubble');
                messageBubble.classList.add(isPatientMessage ? 'message-patient' : 'message-medecin');
                messageBubble.setAttribute('data-message-id', messageId);

                messageBubble.innerHTML = `
                    <strong>${senderName}:</strong>
                    ${content.replace(/\n/g, '<br>')}
                    <span class="message-time">${messageTime}</span>
                `;
                chatMessagesContainer.appendChild(messageBubble);
                scrollToBottom();
            };

            const existingMessages = chatMessagesContainer.querySelectorAll('.message-bubble');
            if (existingMessages.length > 0) {
                lastMessageId = parseInt(existingMessages[existingMessages.length - 1].dataset.messageId);
            }

            if (messageForm && messageInput && sendMessageBtn && currentConversationId) {
                messageForm.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const messageContent = messageInput.value.trim();
                    if (messageContent === '') return;

                    appendMessage('Vous', messageContent, true, 'temp', 'Maintenant');

                    messageInput.value = '';
                    messageInput.style.height = 'auto';

                    sendMessageBtn.disabled = true;

                    try {
                        const response = await fetch('chat.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=send_message&conversation_id=${currentConversationId}&message_content=${encodeURIComponent(messageContent)}`
                        });

                        const data = await response.json();

                        if (data.success) {
                            const tempMessage = chatMessagesContainer.querySelector('[data-message-id="temp"]');
                            if (tempMessage) {
                                tempMessage.setAttribute('data-message-id', data.new_message_id);
                                tempMessage.querySelector('.message-time').textContent = data.timestamp;
                                lastMessageId = data.new_message_id;
                            }
                        } else {
                            console.error("Erreur lors de l'envoi du message:", data.message);
                            alert("Erreur: " + data.message); // Afficher l'erreur à l'utilisateur
                        }
                    } catch (error) {
                        console.error("Erreur réseau:", error);
                        alert("Erreur réseau lors de l'envoi du message.");
                    } finally {
                        sendMessageBtn.disabled = false;
                    }
                });
            }

            let pollingInterval;

            const fetchNewMessages = async () => {
                if (!currentConversationId) return;

                try {
                    const response = await fetch(`get_messages.php?conversation_id=${currentConversationId}&last_message_id=${lastMessageId}`);
                    const data = await response.json();

                    if (data.error) {
                        console.error("Erreur lors de la récupération des messages:", data.error);
                        return;
                    }

                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            if (!document.querySelector(`[data-message-id="${msg.id_message}"]`)) {
                                appendMessage(msg.sender_name, msg.contenu, msg.type_expediteur === 'patient', msg.id_message, msg.formatted_time);
                                lastMessageId = msg.id_message;
                            }
                        });
                    }
                } catch (error) {
                    console.error("Erreur réseau lors du polling:", error);
                }
            };

            if (currentConversationId) {
                pollingInterval = setInterval(fetchNewMessages, 3000);
                fetchNewMessages();
            }

            window.addEventListener('beforeunload', () => {
                if (pollingInterval) clearInterval(pollingInterval);
            });

            if (messageInput) {
                messageInput.addEventListener('input', () => {
                    messageInput.style.height = 'auto';
                    messageInput.style.height = (messageInput.scrollHeight) + 'px';
                });
            }

            if (chatMessagesContainer.scrollHeight > chatMessagesContainer.clientHeight) {
                scrollToBottom();
            }
        });
    </script>
</body>
</html>

