<?php
session_start(); // Start the session to store user data after login

// Database connection parameters
$host = 'localhost'; // Your database host
$db   = 'hopital'; // REMPLACEZ ceci par le nom de votre base de données
$user = 'root'; // REMPLACEZ ceci par votre nom d'utilisateur de base de données
$pass = ''; // REMPLACEZ ceci par votre mot de passe de base de données
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Display connection error in an alert box
    echo "<script>alert('Erreur de connexion à la base de données: " . htmlspecialchars($e->getMessage()) . "');</script>";
    exit(); // Stop script execution
}

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
        $upload_dir = 'uploads/'; // Create this directory
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_name = uniqid() . '_' . basename($_FILES['image_user']['name']);
        $target_file = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image_user']['tmp_name'], $target_file)) {
            $image_patient = $target_file;
        } else {
            // Handle upload error (display a message to the user)
            echo "<script>alert('Erreur lors du téléchargement de l\'image.');</script>";
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO PATIENT (nom, prenom, email, mot_de_passe, telephone, adresse, image_patient) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $mot_de_passe, $telephone, $adresse, $image_patient]);

        echo "<script>alert('Inscription réussie ! Vous pouvez maintenant vous connecter.');</script>";
        // You can keep the redirection to index.html after successful registration if desired
        // If you don't want any redirection, remove the line below.
        header("Location: login_p.php");
        exit(); // Stop script execution after redirection
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) { // SQLSTATE 23000 for integrity constraint violation (e.g., duplicate email)
            echo "<script>alert('Cet email est déjà enregistré. Veuillez utiliser un autre email ou vous connecter.');</script>";
        } else {
            // Log the error for debugging, but show a generic message to the user
            error_log("Registration error: " . $e->getMessage());
            echo "<script>alert('Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');</script>";
        }
        // Do not redirect after an error, just show the alert
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

       // Inside login.php, in the successful login block:
if ($user && password_verify($password, $user['mot_de_passe'])) {
    $_SESSION['id_patient'] = $user['id_patient'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['telephone'] = $user['telephone']; // ADD THIS
    $_SESSION['adresse'] = $user['adresse'];     // ADD THIS
    $_SESSION['image_patient'] = $user['image_patient']; // Make sure this key is used consistently

    header("Location: mon_compte.php");
    exit();
} else {
            // Invalid credentials
            echo "<script>alert('Email/Nom d\'utilisateur ou mot de passe incorrect.');</script>";
            // Do not redirect, just show the alert
        }
    } catch (\PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo "<script>alert('Une erreur est survenue lors de la connexion. Veuillez réessayer.');</script>";
        // Do not redirect, just show the alert
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css"> 
    <link rel="stylesheet" href="login.css">
    <script
      src="https://kit.fontawesome.com/64d58efce2.js"
      crossorigin="anonymous"
    ></script>

    
    <title>Sign in & Sign up Form</title>
  </head>
  <body>
    <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <form method="post" action="login_p.php" class="sign-in-form" enctype="multipart/form-data">
            <h2 class="title">Sign in</h2>
            <div class="input-field">
              <i class="bx bx-user"></i>
              <input name="username" type="text" placeholder="Username" />
            </div>
            <div class="input-field">
              <i class="bx bx-lock"></i>
              <input name="password" type="password" placeholder="Password" />
            </div>
            <input type="submit" name="login" value="Login" class="btn solid" />
            <p class="social-text">Or Sign in with social platforms</p>
            <div class="social-media">
            <a href="#" class="social-icon">
                <i class="bx bxl-facebook"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="bx bxl-twitter"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="bx bxl-google"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="bx bxl-linkedin"></i>
              </a>
            </div>
          </form>
          <form method="post" action="login_p.php" class="sign-up-form" enctype="multipart/form-data">
            <h2 class="title">Sign up</h2>
            <div class="input-field">
              <i class="bx bx-user"></i>
              <input required name="username" type="text" placeholder="Username" />
            </div>
            <div class="input-field">
              <i class="bx bx-pen"></i>
              <input name="surname" type="text" placeholder="Surname" />
            </div>
            <div class="input-field">
              <i class="bx bx-envelope"></i>
              <input name="email" required type="email" placeholder="Email" />
            </div>
            <div class="input-field">
              <i class="bx bx-lock"></i>
              <input name="password" required type="password" placeholder="Password" />
            </div>
            <div class="input-field">
              <i class="bx bx-phone"></i>
              <input name="telephone" required type="text" placeholder="Telephone" />
            </div>
            <div class="cote">
              <div class="input-field">
              <i class="bx bx-map"></i>
              <input name="adresse" required type="text" placeholder="Adresse" />
            </div>
            <div class="input-field">
              <i class="bx bx-image"></i>
              <input type="file" name="image_user" accept="image/*" style="margin-top: 14px; border-raduis: 0;">
            </div>

            </div>

            <input type="submit" name="register" class="btn" value="Sign up" />
            <p class="social-text">Or Sign up with social platforms</p>
            <div class="social-media">
            <a href="#" class="social-icon">
                <i class="bx bxl-facebook"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="bx bxl-twitter"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="bx bxl-google"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="bx bxl-linkedin"></i>
              </a>
            </div>
          </form>
        </div>
      </div>  

      <div class="panels-container">
        <div class="panel left-panel">
          <div class="content">
            <h1>Hello, Friend!</h1>
            <p>
            You don't have an account yet ? Sign up and get access to all our services.
            </p>
            <button class="btn transparent" id="sign-up-btn">
              Sign up
            </button>
          </div>
          <img src="img/res.svg" class="image" alt="" />
        </div>
        <div class="panel right-panel">
          <div class="content">
            <h1>Welcome Back!</h1>
           <p>To keep connected with us please login with your personal info</p>
            <button class="btn transparent" id="sign-in-btn">
              Sign in
            </button>
          </div>
          <img src="img/res1.svg" class="image" alt="" />
        </div>
      </div>
    </div>

    <script src="app.js"></script>
    
  </body>
</html>
