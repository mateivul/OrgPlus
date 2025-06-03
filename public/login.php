<?php

require_once __DIR__ . '/../src/config.php';

$error = '';

// Inițializăm dependențele
$pdo = Database::getConnection(); // Obținem conexiunea PDO (singleton)
$userRepository = new UserRepository($pdo);
$authService = new AuthService($userRepository);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare CSRF
    if (!isset($_POST['csrf_token']) || !CsrfToken::validateToken($_POST['csrf_token'])) {
        // În loc de die(), este mai bine să arunci o excepție sau să setezi un mesaj de eroare
        // și să redirecționezi sau să afișezi o eroare elegantă.
        $error = 'Eroare CSRF: Cerere invalidă!';
    } else {
        $input_email = trim($_POST['email']);
        $input_password = trim($_POST['password']);

        // Aici apelăm logica de autentificare din AuthService
        $user = $authService->login($input_email, $input_password);

        if ($user) {
            // Autentificare reușită
            $_SESSION['user_id'] = $user->getId(); // <-- Asigură-te că această linie există
            header('Location: index.php'); // Redirecționare
            exit();
        } else {
            // Autentificare eșuată
            $error = 'Email sau parolă incorectă.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare</title>
    <?php require '../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/login-and-register.css">
</head>
<body>
    <div class="container-sm account-form-container">
        <div class="logo login">Org<span>Plus</span></div>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title text-center">Autentificare</h3>
                <?php if (!empty($error)) {
                    echo "<p class='text-danger'>$error</p>";
                } ?>

                <form method="POST" action="">
                    <?= CsrfToken::csrfField() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Parolă</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="d-grid mx-auto register-button">
                        <button type="submit" class="btn btn-primary">Autentificare</button>
                    </div>
                </form>
                <div class="text-center link">
                    <a class="link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="create_acc.php">
                        Nu ai cont? Creează unul
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>