<?php
session_start(); // Start the session to store user data after login

require_once 'connexion.php';

// --- Handle User Registration (Sign Up Form) ---
if (isset($_POST['register'])) {
    $nom = $_POST['username']; // This is 'username' in your form for name
    $prenom = $_POST['surname']; // This is 'surname' in your form for prenom
    $email = $_POST['email'];
    $mot_de_passe = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password for security
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];
    $image_patient = null; // Default to null, handle file upload separately

    // Handle image upload (if a file was provided)
    if (isset($_FILES['image_user']) && $_FILES['image_user']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/patients/'; // Create this directory
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_name = uniqid() . '_' . basename($_FILES['image_user']['name']);
        $target_file = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image_user']['tmp_name'], $target_file)) {
            $image_patient = $target_file;
        } else {
            // Handle upload error (e.g., display a message to the user)
            echo "Error uploading image.";
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO PATIENT (nom, prenom, email, mot_de_passe, telephone, adresse, image_patient) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $mot_de_passe, $telephone, $adresse, $image_patient]);

        echo "<script>alert('Inscription réussie ! Vous pouvez maintenant vous connecter.'); window.location.href='index.html';</script>";
        exit(); // Stop script execution after redirection
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) { // SQLSTATE 23000 for integrity constraint violation (e.g., duplicate email)
            echo "<script>alert('Cet email est déjà enregistré. Veuillez utiliser un autre email ou vous connecter.'); window.location.href='index.html';</script>";
        } else {
            // Log the error for debugging, but show a generic message to the user
            error_log("Registration error: " . $e->getMessage());
            echo "<script>alert('Une erreur est survenue lors de l\'inscription. Veuillez réessayer.'); window.location.href='index.html';</script>";
        }
        exit();
    }
}

// --- Handle User Login (Sign In Form) ---
if (isset($_POST['login'])) {
    $username_or_email = $_POST['username']; // In your form, this is 'username'
    $password = $_POST['password'];

    try {
        // Try to find the user by email first (as it's unique in PATIENT)
        $stmt = $pdo->prepare("SELECT id_patient, nom, prenom, email, mot_de_passe FROM PATIENT WHERE email = ?");
        $stmt->execute([$username_or_email]);
        $user = $stmt->fetch();

        // If not found by email, maybe they entered their username (nom)
        if (!$user) {
             $stmt = $pdo->prepare("SELECT id_patient, nom, prenom, email, mot_de_passe FROM PATIENT WHERE nom = ?");
             $stmt->execute([$username_or_email]);
             $user = $stmt->fetch();
        }

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Login successful
            $_SESSION['id_patient'] = $user['id_patient'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['email'] = $user['email'];

            // Redirect to the "My Account" page
            header("Location: mon_compte.php");
            exit();
        } else {
            // Invalid credentials
            echo "<script>alert('Email/Nom d\'utilisateur ou mot de passe incorrect.'); window.location.href='index.html';</script>";
            exit();
        }
    } catch (\PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo "<script>alert('Une erreur est survenue lors de la connexion. Veuillez réessayer.'); window.location.href='index.html';</script>";
        exit();
    }
}
?>