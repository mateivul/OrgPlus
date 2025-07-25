<?php
require_once __DIR__ . '/../src/config.php';

$authService = getService('AuthService');

$currentUser = $authService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit();
}

if (empty($_GET['org_id'])) {
    header('Location: my_organizations.php?error=no_org_selected');
    exit();
}

$org_id = intval($_GET['org_id']);

$_SESSION['org_id'] = $org_id;

// Redirecționează către dashboard-ul organizației
header('Location: dashboard.php');
exit();
