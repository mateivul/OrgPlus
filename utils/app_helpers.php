<?php

// Asigură-te că config.php este inclus undeva înainte de a apela aceste funcții
// sau include-l direct aici dacă aceste funcții sunt apelate înainte de includerea config.php în scriptul principal.
// require_once __DIR__ . '../src/config.php'; // Poate fi necesar în funcție de structura ta de includere

/**
 * Verifică dacă utilizatorul este logat. Dacă nu, redirecționează către pagina de login.
 *
 * @return int ID-ul utilizatorului logat.
 */
function ensure_logged_in()
{
    // --- TEMPORARY DEBUGGING START IN HELPER ---
    error_log('DEBUG: ensure_logged_in() called.');
    error_log('DEBUG: Session status: ' . session_status()); // 0:NONE, 1:INACTIVE, 2:ACTIVE
    error_log('DEBUG: Contents of _SESSION: ' . print_r($_SESSION, true));
    error_log("DEBUG: Is _SESSION['user_id'] set? " . (isset($_SESSION['user_id']) ? 'Yes' : 'No'));
    if (isset($_SESSION['user_id'])) {
        error_log("DEBUG: _SESSION['user_id'] value: " . $_SESSION['user_id']);
    }
    // --- TEMPORARY DEBUGGING END IN HELPER ---

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        error_log('DEBUG: Redirecting to login.php because user_id is not set or empty.');

        header('Location: login.php');
        exit();
    }
    return $_SESSION['user_id'];
}
/**
 * Verifică dacă o organizație este selectată în sesiune. Dacă nu, redirecționează.
 *
 * @return int ID-ul organizației selectate.
 */
function ensure_existing_org(): int
{
    if (!isset($_SESSION['org_id'])) {
        header('Location: ../public/dashboard.php'); // Redirecționează către dashboard sau o pagină de selecție organizație
        exit();
    }
    return $_SESSION['org_id'];
}

/**
 * Obține rolul unui utilizator într-o organizație.
 *
 * @param int $userId
 * @param int $orgId
 * @return string|null Rolul utilizatorului ('admin', 'owner', 'member') sau null dacă nu are rol.
 */
function user_org_role(int $userId, int $orgId): ?string
{
    // Aici, apelăm getService care se bazează pe containerul de dependințe din config.php
    $roleRepository = getService('RoleRepository');
    return $roleRepository->getUserRoleInOrganization($userId, $orgId);
}
