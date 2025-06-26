<?php

require_once __DIR__ . '/../src/config.php';

// Inițializăm dependențele
$pdo = Database::getConnection();
$userRepository = new UserRepository($pdo);
$authService = new AuthService($userRepository);
$organizationRepository = new OrganizationRepository($pdo);
$roleRepository = new RoleRepository($pdo);
$eventRepository = new EventRepository($pdo);

// --- Protecția paginii: Doar pentru utilizatori autentificați ---
$currentUser = $authService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit();
}

$user_id = $currentUser->id;

// Verifică dacă org_id este setată în sesiune
if (!isset($_SESSION['org_id'])) {
    header('Location: my_organizations.php?error=no_org_selected');
    exit();
}
$org_id = $_SESSION['org_id'];

// --- Preluarea datelor pentru Dashboard ---

// Detalii organizație (mereu necesare)
$organization = $organizationRepository->findById($org_id);
if (!$organization) {
    header('Location: my_organizations.php?error=org_not_found');
    exit();
}

$org_name = $organization->name;
$created_at_str = $organization->createdAt;
$formatted_creation_date = 'N/A';
$activity_text = 'N/A';

if ($created_at_str) {
    try {
        $created_at = new DateTime($created_at_str);
        $now = new DateTime();
        $interval = $created_at->diff($now);

        $years = $interval->y;
        $months = $interval->m;

        if ($years == 0 && $months == 0) {
            $activity_text = 'Mai puțin de o lună';
        } elseif ($years == 0) {
            $activity_text = "$months luni";
        } elseif ($months == 0 && $years == 1) {
            $activity_text = 'Un an';
        } elseif ($months == 0) {
            $activity_text = "$years ani";
        } else {
            $activity_text = "$years ani și $months luni";
        }
        $formatted_creation_date = $created_at->format('d M Y');
    } catch (Exception $e) {
        error_log('Eroare la calculul activității organizației: ' . $e->getMessage());
        $activity_text = 'Eroare la calculul activității';
        $formatted_creation_date = 'Eroare la dată';
    }
}

// Numele utilizatorului curent
$name = $currentUser->firstName . ' ' . $currentUser->lastName;

// --- Logica pentru conținut condițional bazat pe rol ---
$user_role = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
$is_org_member = $roleRepository->isUserMemberOfOrganization($user_id, $org_id);
$is_org_admin_or_owner = $roleRepository->isUserAdminOrOwner($user_id, $org_id);

// Variabile de date inițializate (vor fi populate cu date reale dacă utilizatorul este membru)
$members_count = 0;
$events_count = 0;
$last_event_name = 'Niciun eveniment recent';
$last_event_date = 'N/A';

// Preluăm datele doar dacă e membru
$members_count = $roleRepository->countMembersInOrganization($org_id);
$events_count = $eventRepository->countEventsByOrganization($org_id);
$last_event = $eventRepository->findLastEventByOrganization($org_id);
$last_event_name = $last_event['name'] ?? 'Niciun eveniment recent';
$last_event_date = $last_event['date'] ?? 'N/A';

// Permisiuni pentru accesul la link-uri din carduri (nu la vizibilitatea cardurilor)
$can_access_members_page = $is_org_member; // Sau ajustează conform rolurilor specifice dacă e nevoie
$can_access_events_page = $is_org_member;

// Sau ajustează conform rolurilor specifice dacă e nevoie
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($org_name); ?></title>
    <?php require '../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/dashboard-style.css">
</head>
<body>
<div class="d-flex">
    <?php require '../includes/sidebar.php'; ?>
    <div class="content p-4">
        <div class="welcome-banner mb-5">
            <h2>Bine ai venit, <span class="highlight-name"><?php echo htmlspecialchars(
                $name
            ); ?></span>, în organizația <span class="highlight-org"><?php echo htmlspecialchars(
    $org_name
); ?></span>!</h2>
            <?php if (!$is_org_member): ?>
                <p class="text-warning">Nu ești încă membru al acestei organizații. Statistici limitate și funcționalități dezactivate.</p>
                <p>Poți trimite o cerere de aderare de pe pagina <a href="index.php">Organizații</a>.</p>
            <?php else: ?>
                <p>Ești membru. Rolul tău: <b><?php echo htmlspecialchars(role_to_readable($user_role)); ?></b></p>
            <?php endif; ?>
        </div>

        <div class="my-cards-container">
            <div class="my-card">
                <div class="card hover-card h-100">
                    <div class="card-body">
                        <h5 class="my-card-title"><i class="fas fa-users"></i> Membrii</h5>
                        <div class="card-content">
                            <p class="card-stats">
                                <?php echo $members_count; ?>
                            </p>
                        </div>
                        <div class="card-footer-action">
                            <?php if ($can_access_members_page): ?>
                                <a href="members_org.php" class="btn btn-link">Vezi detalii <i class="fas fa-arrow-right"></i></a>
                            <?php else: ?>
                                <span class="btn btn-link disabled-link" aria-disabled="true">Acces restricționat <i class="fas fa-lock"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-card">
                <div class="card hover-card h-100">
                    <div class="card-body">
                        <h5 class="my-card-title"><i class="fas fa-calendar-alt"></i> Evenimente</h5>
                        <div class="card-content">
                            <p class="card-stats">
                                <?php echo $events_count; ?>
                            </p>
                        </div>
                        <div class="card-footer-action">
                            <?php if ($can_access_events_page): ?>
                                <a href="events.php" class="btn btn-link">Vezi detalii <i class="fas fa-arrow-right"></i></a>
                            <?php else: ?>
                                <span class="btn btn-link disabled-link" aria-disabled="true">Acces restricționat <i class="fas fa-lock"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-card">
                <div class="card hover-card h-100">
                    <div class="card-body">
                        <h5 class="my-card-title"><i class="fas fa-wand-magic-sparkles"></i> Ultimul Eveniment</h5>
                        <div class="card-content">
                            <p class="card-description"><?php echo htmlspecialchars($last_event_name); ?></p>
                            <p class="card-meta">Data: <?php echo htmlspecialchars($last_event_date); ?></p>
                        </div>
                           <div class="card-footer-action">
                                <?php if ($can_access_events_page): ?>
                                    <a href="events.php" class="btn btn-link">Vezi detalii <i class="fas fa-arrow-right"></i></a>
                                <?php else: ?>
                                    <span class="btn btn-link disabled-link" aria-disabled="true">Acces restricționat <i class="fas fa-lock"></i></span>
                                <?php endif; ?>
                            </div>
                    </div>
                </div>
            </div>

            <div class="my-card">
                <div class="card hover-card h-100">
                    <div class="card-body">
                        <h5 class="my-card-title"><i class="fas fa-info-circle"></i> Despre Organizație</h5>
                        <div class="card-content">
                            <p class="card-description">Activitate: <span class="text-white-50"><?php echo $activity_text; ?></span></p>
                            <p class="card-meta">Creată la: <span class="text-white-50"><?php echo htmlspecialchars(
                                $formatted_creation_date
                            ); ?></span></p>
                            <?php if ($organization->website): ?>
                                <p class="card-meta">Site web: <a href="<?php echo htmlspecialchars(
                                    $organization->website
                                ); ?>" target="_blank"><?php echo htmlspecialchars($organization->website); ?></a></p>
                            <?php endif; ?>
                        </div>
                           <div class="card-footer-action">
                                <span class="btn btn-link disabled-link" aria-disabled="true">Informații generale <i class="fas fa-building"></i></span>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.hover-card').forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            this.style.setProperty('--x', `${e.clientX - rect.left}px`);
            this.style.setProperty('--y', `${e.clientY - rect.top}px`);
        });
    });
</script>
</body>
</html>