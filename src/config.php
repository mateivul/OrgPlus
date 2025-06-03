<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Asigură-te că Composer autoload este inclus dacă îl folosești
require_once __DIR__ . '/autoload.php'; // Using your custom autoloader now

// Include clasa ta de baza de date
require_once __DIR__ . '/Config/Database.php';

// Importă clasele necesare din namespace-urile lor
// use App\Config\Database; // Aici folosim noul tău namespace pentru Database
// use App\Entity\User; // <-- MAKE SURE THIS IS App\Entity\User
// use App\Entity\Organization; // <-- MAKE SURE THIS IS App\Entity\Organization
// use App\Entity\Event;
// use App\Entity\EventRole;
// use App\Entity\Request;

// Manual require for entities and models (no namespaces, no autoload)
require_once __DIR__ . '/Model/User.php';
require_once __DIR__ . '/Model/Organization.php';
require_once __DIR__ . '/Entity/Event.php';
require_once __DIR__ . '/Entity/EventRole.php';
require_once __DIR__ . '/Entity/EventTask.php';
require_once __DIR__ . '/Entity/EventParticipant.php';
require_once __DIR__ . '/Entity/Request.php';
require_once __DIR__ . '/Entity/WorkedHour.php';
// Add others as needed (Request, etc)

// Repository-uri
// use App\Repository\UserRepository;
// use App\Repository\OrganizationRepository;
// use App\Repository\RoleRepository;

// Manual require for repositories (no namespaces, no autoload)
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/OrganizationRepository.php';
require_once __DIR__ . '/Repository/RoleRepository.php';
require_once __DIR__ . '/Repository/RequestRepository.php';
require_once __DIR__ . '/Repository/EventRepository.php';
require_once __DIR__ . '/Repository/EventRoleRepository.php';
require_once __DIR__ . '/Repository/WorkedHoursRepository.php';
// use App\Repository\RequestRepository;
// use App\Repository\EventRepository;
// use App\Repository\EventRoleRepository;

// Servicii

// Manual require for services (no namespaces, no autoload)
require_once __DIR__ . '/Service/AuthService.php';
require_once __DIR__ . '/Service/OrganizationService.php';
require_once __DIR__ . '/Service/RequestService.php';
require_once __DIR__ . '/Service/EventService.php';
require_once __DIR__ . '/Service/WorkedHoursService.php';

// Security
require_once __DIR__ . '/Security/CsrfToken.php';

// Obține conexiunea la baza de date
try {
    $pdo = Database::getConnection();
} catch (PDOException $e) {
    die('Eroare la conectarea la baza de date: ' . $e->getMessage());
}

// Container de Dependințe (simplu array)
$container = [];

// --- Repository-uri ---
$container['UserRepository'] = new UserRepository($pdo);
$container['OrganizationRepository'] = new OrganizationRepository($pdo);
$container['RoleRepository'] = new RoleRepository($pdo);
$container['RequestRepository'] = new RequestRepository($pdo);
$container['EventRepository'] = new EventRepository($pdo);
$container['EventRoleRepository'] = new EventRoleRepository($pdo);
$container['WorkedHoursRepository'] = new WorkedHoursRepository($pdo);

// --- Servicii ---
// Asigură-te că toți constructorii serviciilor au dependențele corecte
$container['AuthService'] = new AuthService($container['UserRepository']);

$container['OrganizationService'] = new OrganizationService(
    $container['OrganizationRepository'],
    $container['UserRepository'], // <-- Corrected order: UserRepository as the second argument
    $container['RoleRepository'], // <-- Corrected order: RoleRepository as the third argument
    $container['RequestRepository'] // <-- Added missing RequestRepository as the fourth argument
);

$container['RequestService'] = new RequestService(
    $container['RequestRepository'],
    $container['UserRepository'],
    $container['OrganizationRepository'],
    $container['RoleRepository']
    // Presupunem că RequestService are nevoie de aceste dependențe.
    // Verifică constructorul real al RequestService.
);

$container['EventService'] = new EventService(
    $container['EventRepository'],
    $container['EventRoleRepository'],
    $container['UserRepository'],
    $container['RoleRepository'],
    $container['RequestRepository'], // Posibil să nu aibă nevoie de RequestRepository aici, verifică constructorul
    $container['OrganizationService']
);

$container['WorkedHoursService'] = new WorkedHoursService(
    $container['WorkedHoursRepository'],
    $container['OrganizationRepository'],
    $container['RoleRepository']
);

// Funcție ajutătoare pentru a obține servicii din container
if (!function_exists('getService')) {
    // Evită redefinirea funcției
    function getService(string $serviceName)
    {
        global $container;
        if (!isset($container[$serviceName])) {
            throw new Exception("Serviciul sau Repository-ul '$serviceName' nu a fost găsit în container.");
        }
        return $container[$serviceName];
    }
}
