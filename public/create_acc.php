<?php

require_once __DIR__ . '/../src/config.php';
$error = '';
$success = '';
// Inițializăm dependențele
$userService = getService('UserService');
$userRepository = getService('UserRepository');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !CsrfToken::validateToken($_POST['csrf_token'])) {
        $error = 'Eroare CSRF: Cerere invalidă!';
    } else {
        $name = trim($_POST['name']);
        $prenume = trim($_POST['prenume']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($name) || empty($prenume) || empty($email) || empty($password)) {
            $error = 'Toate câmpurile sunt obligatorii.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalid.';
        } elseif (strlen($password) < 6) {
            $error = 'Parola trebuie să aibă cel puțin 6 caractere.';
        } else {
            $registeredUser = $userService->registerUser($name, $prenume, $email, $password);

            if ($registeredUser) {
                $success = 'Contul a fost creat cu succes! Te poți autentifica acum.';
                header('Location: login.php?registered=true');
                exit();
            } else {
                // Verificăm dacă eroarea a fost cauzată de un email duplicat
                if ($userRepository->findByEmail($email)) {
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
                    echo "<p class='text-danger'>$error</p>";
                } elseif (!empty($success)) {
                    echo "<p class='text-success'>$success</p>";
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