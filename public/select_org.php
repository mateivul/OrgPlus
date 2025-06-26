<?php
require_once __DIR__ . '/../src/config.php';

$pdo = Database::getConnection();
$userRepository = new UserRepository($pdo);
$authService = new AuthService($userRepository);
// $roleRepository = new App\Repository\RoleRepository($pdo); // Nu mai e necesar aici

// --- Protecția paginii: Doar utilizatori autentificați pot selecta o org ---
$currentUser = $authService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit();
}

// Verifică dacă org_id a fost trimis prin GET
if (empty($_GET['org_id'])) {
    header('Location: my_organizations.php?error=no_org_selected');
    exit();
}

$org_id = intval($_GET['org_id']);

// Salvează ID-ul organizației în sesiune
$_SESSION['org_id'] = $org_id;

// Redirecționează către dashboard-ul organizației
header('Location: dashboard.php');
exit();
