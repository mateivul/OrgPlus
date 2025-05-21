<?php
require 'lib/conn_db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Token CSRF lipsă!');
    }
    $input_email = trim($_POST['email']);
    $input_password = trim($_POST['password']);

    $sql = 'SELECT id, password, hash_salt FROM users WHERE email = ?';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $input_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_password, $db_salt);
        $stmt->fetch();

        $input_hashed_password = hash('sha256', $db_salt . $input_password);

        if ($input_hashed_password === $db_password) {
            $update_sql = 'UPDATE users SET last_login = NOW() WHERE id = ?';
            $update_stmt = $mysqli->prepare($update_sql);
            $update_stmt->bind_param('i', $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $input_email;
            header('Location: index.php');
            exit();
        } else {
            $error = 'Email sau parolă incorectă.';
        }
    } else {
        $error = 'Email sau parolă incorectă.';
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare</title>
    <?php require 'utils/global.html'; ?>
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
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" require>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Parolă</label>
                        <input type="password" class="form-control" name="password" id="password" require>
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
