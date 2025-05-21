<?php
require 'lib/conn_db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $sql_check = 'SELECT id FROM users WHERE email = ?';
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param('s', $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $error = 'Acest email este deja folosit.';
            }
            $stmt_check->close();
        }

        if (empty($error)) {
            $salt = bin2hex(random_bytes(32));
            $hashed_password = hash('sha256', $salt . $password);
            if ($hashed_password === false) {
                $error = 'Eroare la procesarea parolei.';
            } else {
                $sql = 'INSERT INTO users (name, prenume, email, password, hash_salt) VALUES (?, ?, ?, ?, ?)';
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param('sssss', $name, $prenume, $email, $hashed_password, $salt);
                    if ($stmt->execute()) {
                        header('Location: login.php');
                        exit();
                    } else {
                        $error = 'Eroare la înregistrare. Încercați din nou.';
                    }
                    $stmt->close();
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
    <?php require 'utils/global.html'; ?>
    <link rel="stylesheet" href="styles/login-and-register.css">
</head>
<body>
    <div class="container-sm account-form-container">
        <div class="logo login">Org<span>Plus</span></div>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title text-center">Creare cont</h3>
                <?php if (!empty($error)) {
                    echo "<p class='error-msg'>$error</p>";
                } ?>
                <form method="POST" action="">
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
