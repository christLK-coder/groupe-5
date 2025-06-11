<?php
session_start();

// Configuration de la connexion à la base de données
$host = 'localhost'; // Remplacez par l'adresse de votre serveur de base de données
$db   = 'hopital';   // Le nom de votre base de données
$user = 'root';     // Votre nom d'utilisateur
$pass = '';         // Votre mot de passe (laissez vide si pas de mot de passe)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("La connexion à la base de données a échoué : " . $e->getMessage());
}

// Vérifier si le patient est connecté
if (!isset($_SESSION['id_patient'])) {
    header("Location: connexion_patient.php"); // Rediriger vers la page de connexion
    exit;
}

$id_patient_connecte = $_SESSION['id_patient'];
$current_conversation_id = null;
$current_medecin_id = null;
$current_medecin_nom_complet = 'Sélectionnez une conversation';
$messages = [];
$error_message = '';

// --- Logique de sélection ou création de conversation ---
if (isset($_GET['conversation_id'])) {
    // Cas 1: On sélectionne une conversation existante depuis la liste des conversations
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
    // Cas 2: On vient du profil d'un médecin (pour créer ou continuer une conversation)
    $target_medecin_id = intval($_GET['medecin_id_cible']);

    try {
        // 1. Vérifier si une conversation existe déjà entre ce patient et ce médecin
        $stmt_check_conv = $pdo->prepare("SELECT id_conversation FROM CONVERSATION WHERE id_patient = ? AND id_medecin = ?");
        $stmt_check_conv->execute([$id_patient_connecte, $target_medecin_id]);
        $conversation = $stmt_check_conv->fetch(PDO::FETCH_ASSOC);

        if ($conversation) {
            $current_conversation_id = $conversation['id_conversation'];
            $current_medecin_id = $target_medecin_id;
        } else {
            // 2. Si non, créer une nouvelle conversation
            $stmt_create_conv = $pdo->prepare("INSERT INTO CONVERSATION (id_patient, id_medecin) VALUES (?, ?)");
            $stmt_create_conv->execute([$id_patient_connecte, $target_medecin_id]);
            $current_conversation_id = $pdo->lastInsertId();
            $current_medecin_id = $target_medecin_id;
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données lors de la création/recherche de la conversation : " . $e->getMessage();
    }
}

// --- Récupérer les informations du médecin actif et les messages si une conversation est sélectionnée ---
if ($current_conversation_id && $current_medecin_id) {
    try {
        // Récupérer le nom du médecin pour l'affichage
        $stmt_medecin_name = $pdo->prepare("SELECT nom, prenom FROM MEDECIN WHERE id_medecin = ?");
        $stmt_medecin_name->execute([$current_medecin_id]);
        $medecin_data = $stmt_medecin_name->fetch(PDO::FETCH_ASSOC);
        if ($medecin_data) {
            $current_medecin_nom_complet = htmlspecialchars($medecin_data['prenom'] . ' ' . $medecin_data['nom']);
        } else {
            $current_medecin_nom_complet = 'Médecin Inconnu';
        }

        // Récupérer les messages de la conversation active
        $stmt_messages = $pdo->prepare("
            SELECT
                id_expediteur,
                type_expediteur,
                contenu,
                date_message
            FROM
                MESSAGE
            WHERE
                id_conversation = ?
            ORDER BY
                date_message ASC
        ");
        $stmt_messages->execute([$current_conversation_id]);
        $messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_message = "Erreur de base de données lors du chargement des messages : " . $e->getMessage();
    }
}

// --- Logique d'envoi de message (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_content']) && $current_conversation_id) {
    $message_content = trim($_POST['message_content']);
    if (!empty($message_content)) {
        try {
            $stmt_insert_msg = $pdo->prepare("
                INSERT INTO MESSAGE (id_conversation, id_expediteur, type_expediteur, contenu)
                VALUES (?, ?, 'patient', ?)
            ");
            $stmt_insert_msg->execute([$current_conversation_id, $id_patient_connecte, $message_content]);

            // Recharger la page pour afficher le nouveau message
            // Important: rediriger vers l'ID de conversation pour maintenir le contexte
            header("Location: chat.php?conversation_id=" . $current_conversation_id);
            exit;
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'envoi du message : " . $e->getMessage();
        }
    }
}

// --- Récupérer toutes les conversations du patient pour la liste latérale ---
$conversations_list = [];
try {
    $stmt_conversations = $pdo->prepare("
        SELECT
            C.id_conversation,
            M.nom AS medecin_nom,
            M.prenom AS medecin_prenom,
            (SELECT contenu FROM MESSAGE WHERE id_conversation = C.id_conversation ORDER BY date_message DESC LIMIT 1) AS last_message_content,
            (SELECT date_message FROM MESSAGE WHERE id_conversation = C.id_conversation ORDER BY date_message DESC LIMIT 1) AS last_message_date
        FROM
            CONVERSATION C
        JOIN
            MEDECIN M ON C.id_medecin = M.id_medecin
        WHERE
            C.id_patient = ?
        ORDER BY
            last_message_date DESC, C.date_creation DESC
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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            min-height: 100vh;
        }
        .main-chat-wrapper {
            display: flex;
            width: 100%; /* Adjust as needed */
            height: 100vh; /* Fixed height for the chat interface */
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Conversation List Sidebar */
        .conversation-list {
            flex: 0 0 300px; /* Fixed width sidebar */
            border-right: 1px solid #eee;
            background-color: #f8f8f8;
            overflow-y: auto;
            padding: 10px 0;
        }
        .conversation-list h2 {
            font-size: 1.2em;
            color: #2c3e50;
            padding: 10px 20px;
            margin: 0;
            border-bottom: 1px solid #eee;
        }
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: #333;
            display: block;
        }
        .conversation-item:hover, .conversation-item.active {
            background-color: #e0f2f7; /* Lighter blue for hover/active */
        }
        .conversation-item h3 {
            margin: 0;
            font-size: 1em;
            color: #3498db;
        }
        .conversation-item p {
            margin: 5px 0 0;
            font-size: 0.85em;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-item .last-message-time {
            font-size: 0.75em;
            color: #999;
            text-align: right;
            display: block;
        }


        /* Chat Area (Right side) */
        .chat-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background-color: #3498db;
            color: white;
            padding: 15px 20px;
            font-size: 1.3em;
            font-weight: bold;
            border-bottom: 1px solid #2980b9;
            text-align: center;
        }
        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #e9ebee;
            display: flex;
            flex-direction: column;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 20px;
            margin-bottom: 10px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        .message-patient {
            background-color: #dcf8c6;
            align-self: flex-end;
            text-align: right;
        }
        .message-medecin {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            align-self: flex-start;
            text-align: left;
        }
        .message-time {
            font-size: 0.75em;
            color: #777;
            margin-top: 5px;
            display: block;
        }
        .chat-input {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
        }
        .chat-input textarea {
            flex-grow: 1;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            font-size: 1em;
            resize: none;
            max-height: 100px;
            margin-right: 10px;
        }
        .chat-input button {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .chat-input button:hover {
            background-color: #2980b9;
        }
        .error-message {
            color: red;
            text-align: center;
            padding: 10px;
        }
        .no-conversation-selected {
            text-align: center;
            padding: 50px;
            color: #555;
            font-size: 1.1em;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-chat-wrapper {
                flex-direction: column; /* Stack vertically on small screens */
                height: 95vh;
            }
            .conversation-list {
                flex: none; /* Remove fixed width */
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
                height: 150px; /* Max height for scrollable list */
            }
            .chat-area {
                flex: 1; /* Take remaining space */
            }
            .chat-header {
                font-size: 1.1em;
            }
            .chat-input {
                padding: 10px;
            }
            .chat-input textarea {
                padding: 8px;
            }
            .chat-input button {
                padding: 8px 12px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="main-chat-wrapper">
        <div class="conversation-list">
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
                            $sender_name = $is_patient_message ? 'Vous' : $current_medecin_nom_complet; // Ici le nom du médecin doit être celui de la conversation active
                        ?>
                        <div class="message-bubble <?php echo $is_patient_message ? 'message-patient' : 'message-medecin'; ?>">
                            <strong><?php echo $sender_name; ?>:</strong>
                            <?php echo htmlspecialchars($message['contenu']); ?>
                            <span class="message-time"><?php echo date('H:i', strtotime($message['date_message'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form class="chat-input" method="POST" action="">
                <?php if ($current_conversation_id): // N'affiche le champ que si une conversation est active ?>
                    <textarea name="message_content" placeholder="Écrivez votre message..." rows="1"></textarea>
                    <button type="submit">Envoyer</button>
                <?php else: ?>
                    <p style="text-align: center; flex-grow: 1; color: #777;">Sélectionnez une conversation pour envoyer un message.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        // Scroll to the bottom of the chat messages when a conversation is active
        var chatMessages = document.getElementById('chatMessages');
        if (chatMessages && chatMessages.scrollHeight > chatMessages.clientHeight) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Auto-resize textarea
        const textarea = document.querySelector('.chat-input textarea');
        if (textarea) { // Only if textarea exists (i.e., conversation is active)
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
            });
        }
    </script>
</body>
</html>