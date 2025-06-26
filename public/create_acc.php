<?php

require_once __DIR__ . '/../src/config.php';
$error = '';
$success = '';
// Inițializăm dependențele
$pdo = Database::getConnection();

if (!$pdo) {
    echo 'DEBUG EROARE: Conexiunea la baza de date a eșuat. Verifică Database.php.<br>';
    exit();
} else {
    echo 'DEBUG: Conexiune la baza de date stabilită cu succes.<br>'; // Mesaj de succes
}

$userRepository = new UserRepository($pdo);
$userService = new UserService($userRepository); // Instanțiem UserService

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF
    if (!isset($_POST['csrf_token']) || !CsrfToken::validateToken($_POST['csrf_token'])) {
        $error = 'Eroare CSRF: Cerere invalidă!';
    } else {
        $name = trim($_POST['name']);
        $prenume = trim($_POST['prenume']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Validări de bază (similar cu codul tău vechi)
        if (empty($name) || empty($prenume) || empty($email) || empty($password)) {
            $error = 'Toate câmpurile sunt obligatorii.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalid.';
        } elseif (strlen($password) < 6) {
            $error = 'Parola trebuie să aibă cel puțin 6 caractere.';
        } else {
            // Apelăm metoda registerUser din UserService
            $registeredUser = $userService->registerUser($name, $prenume, $email, $password);

            if ($registeredUser) {
                // Înregistrare reușită
                $success = 'Contul a fost creat cu succes! Te poți autentifica acum.';
                // Alternativ, redirecționează direct la pagina de login
                header('Location: login.php?registered=true'); // Adaugăm un parametru pentru mesaj
                exit();
            } else {
                // Înregistrarea a eșuat, probabil email-ul există deja sau eroare la bază de date
                // UserService ar returna null dacă email-ul există deja.
                // Putem face o verificare mai specifică dacă vrem mesaje de eroare diferite.
                // Pentru simplitate acum, un mesaj generic.
                if ($userRepository->findByEmail($email)) {
                    // Verificăm din nou pentru a da un mesaj mai specific
                    $error = 'Acest email este deja folosit.';
                } else {
                    $error = 'Eroare la înregistrare. Încercați din nou.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creare Cont</title>
    <?php require '../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/login-and-register.css">
</head>
<body>
    <div class="container-sm account-form-container">
        <div class="logo login">Org<span>Plus</span></div>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title text-center">Creare cont</h3>
                <?php if (!empty($error)) {
                    echo "<p class='text-danger'>$error</p>"; // Folosim text-danger pentru Bootstrap
                } elseif (!empty($success)) {
                    echo "<p class='text-success'>$success</p>"; // Folosim text-success pentru Bootstrap
                } ?>
                <form method="POST" action="">
                    <?= CsrfToken::csrfField() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nume</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenume" class="form-label">Prenume</label>
                        <input type="text" class="form-control" id="prenume" name="prenume" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Parolă</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid mx-auto register-button">
                        <button type="submit" class="btn btn-primary">Înregistrează-te</button>
                    </div>
                </form>
                <div class="text-center link">
                    <a class="link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="login.php">
                        Ai deja un cont? Autentifică-te
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>