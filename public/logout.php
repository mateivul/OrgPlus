<?php
session_start(); // Începe sesiunea

// Golește toate variabilele sesiunii
$_SESSION = [];

// Dacă folosești cookie-uri de sesiune, distruge cookie-ul sesiunii.
// Notă: Aceasta va distruge sesiunea, nu doar datele sesiunii!
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// În final, distruge sesiunea.
session_destroy();

// Redirecționează către pagina de login sau acasă
header('Location: login.php'); // Sau 'index.php'
exit();
?>
