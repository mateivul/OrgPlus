<?php

function ensure_logged_in()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        error_log('DEBUG: Redirecting to login.php because user_id is not set or empty.');

        header('Location: login.php');
        exit();
    }
    return $_SESSION['user_id'];
}

function ensure_existing_org(): int
{
    if (!isset($_SESSION['org_id'])) {
        header('Location: ../public/dashboard.php');
        exit();
    }
    return $_SESSION['org_id'];
}

function user_org_role(int $userId, int $orgId): ?string
{
    $roleRepository = getService('RoleRepository');
    return $roleRepository->getUserRoleInOrganization($userId, $orgId);
}

function role_to_readable(?string $role): string
{
    if ($role === null) {
        return 'N/A';
    }
    switch ($role) {
        case 'admin':
            return 'Administrator';
        case 'owner':
            return 'Președinte';
        case 'member':
            return 'Membru';
        case 'viewer':
            return 'Vizualizator';
        default:
            return ucfirst($role);
    }
}

function getService(string $serviceName)
{
    global $container;
    if (!isset($container[$serviceName])) {
        throw new Exception("Serviciul sau Repository-ul '$serviceName' nu a fost găsit în container.");
    }
    return $container[$serviceName];
}
