<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/autoload.php';

require_once __DIR__ . '/Config/Database.php';

// require for entities and models
require_once __DIR__ . '/Model/User.php';
require_once __DIR__ . '/Model/Organization.php';
require_once __DIR__ . '/Entity/Role.php';
require_once __DIR__ . '/Entity/Event.php';
require_once __DIR__ . '/Entity/EventRole.php';
require_once __DIR__ . '/Entity/EventTask.php';
require_once __DIR__ . '/Entity/EventParticipant.php';
require_once __DIR__ . '/Entity/Request.php';
require_once __DIR__ . '/Entity/WorkedHour.php';

// require for repositories
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/OrganizationRepository.php';
require_once __DIR__ . '/Repository/RoleRepository.php';
require_once __DIR__ . '/Repository/RequestRepository.php';
require_once __DIR__ . '/Repository/EventRepository.php';
require_once __DIR__ . '/Repository/EventRoleRepository.php';
require_once __DIR__ . '/Repository/WorkedHoursRepository.php';

// require for services
require_once __DIR__ . '/Service/AuthService.php';
require_once __DIR__ . '/Service/OrganizationService.php';
require_once __DIR__ . '/Service/RequestService.php';
require_once __DIR__ . '/Service/EventService.php';
require_once __DIR__ . '/Service/WorkedHoursService.php';
require_once __DIR__ . '/Service/UserService.php';

// Security
require_once __DIR__ . '/Security/CsrfToken.php';

// Helpers
require_once __DIR__ . '/../utils/app_helpers.php';

// conexiunea la baza de date
try {
    $pdo = Database::getConnection();
} catch (PDOException $e) {
    die('Eroare la conectarea la baza de date: ' . $e->getMessage());
}

// container de dependințe
$container = [];

// repository-uri
$container['UserRepository'] = new UserRepository($pdo);
$container['OrganizationRepository'] = new OrganizationRepository($pdo);
$container['RoleRepository'] = new RoleRepository($pdo);
$container['RequestRepository'] = new RequestRepository($pdo);
$container['EventRepository'] = new EventRepository($pdo);
$container['EventRoleRepository'] = new EventRoleRepository($pdo);
$container['WorkedHoursRepository'] = new WorkedHoursRepository($pdo);

$container['AuthService'] = new AuthService($container['UserRepository']);
$container['UserService'] = new UserService($container['UserRepository']);

$container['OrganizationService'] = new OrganizationService(
    $container['OrganizationRepository'],
    $container['UserRepository'],
    $container['RoleRepository'],
    $container['RequestRepository']
);

$container['RequestService'] = new RequestService(
    $container['RequestRepository'],
    $container['UserRepository'],
    $container['OrganizationRepository'],
    $container['RoleRepository']
);

$container['EventService'] = new EventService(
    $container['EventRepository'],
    $container['EventRoleRepository'],
    $container['UserRepository'],
    $container['RoleRepository'],
    $container['RequestRepository'],
    $container['OrganizationService']
);

$container['WorkedHoursService'] = new WorkedHoursService(
    $container['WorkedHoursRepository'],
    $container['OrganizationRepository'],
    $container['RoleRepository']
);
