<?php
$currentUser = $authService->getCurrentUser();
$user_id = $currentUser ? $currentUser->id : null;

$currentPage = basename($_SERVER['PHP_SELF']);
$isDashboardPage = in_array($currentPage, [
    'dashboard.php',
    'members_org.php',
    'events.php',
    'view_event.php',
    'assign_roles.php',
    'inbox_org.php',
    'working_hours.php',
    'advanced_stats.php',
    'worked_hours_report.php',
    'monthly_hours_report.php',
    'member_hours.php',
    'working_hours_report.php',
    'public_profile.php',
]);

$is_org_admin = false;
$is_org_member = false;
$current_org_id = $_SESSION['org_id'] ?? null;

if ($user_id !== null && $current_org_id !== null && isset($roleRepository)) {
    $is_org_member = $roleRepository->isUserMemberOfOrganization($user_id, $current_org_id);
    $is_org_admin = $roleRepository->isUserAdminOrOwner($user_id, $current_org_id);
}
?>

    <?php require 'global.html'; ?>

<nav class="sidebar">
    <ul class="nav flex-column">
        <div class="menu-section">
            <div class="logo">Org<span>Plus</span></div>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center text-light" href="index.php">
                    <i class="fas fa-home me-2"></i> Acasă
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center text-light" href="profile.php">
                    <i class="fas fa-user me-2"></i> Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center text-light" href="my_organizations.php">
                    <i class="fas fa-building me-2"></i> Organizațiile Mele
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center text-light" href="inbox.php">
                    <i class="fas fa-envelope me-2"></i> Mesaje
                </a>
            </li>
            <?php if ($user_id):// Verificăm dacă user_id există
                 ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Deconectare
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-danger" href="login.php">
                        <i class="fas fa-sign-in-alt me-2"></i> Logare
                    </a>
                </li>
            <?php endif; ?>
        </div>

        <?php if ($isDashboardPage && isset($_SESSION['org_id'])): ?>
            <hr class="border-secondary my-3">
            <div class="menu-section">
                <div class="menu-title text-uppercase fw-bold text-secondary mb-3">Meniu Organizație</div>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center text-light" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Panou general
                    </a>
                </li>
                <?php if ($is_org_member): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center text-light" href="members_org.php">
                            <i class="fas fa-users me-2"></i> Membri
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center text-light" href="events.php">
                            <i class="fas fa-calendar-alt me-2"></i> Evenimente
                        </a>
                    </li>
                    <?php if ($is_org_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center text-light" href="inbox_org.php">
                                <i class="fas fa-envelope me-2"></i> Mesaje interne
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center text-light" href="working_hours.php">
                                <i class="fas fa-clock me-2"></i> Ore lucrate
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center text-light" href="advanced_stats.php">
                            <i class="fas fa-chart-pie me-2"></i> Statistici
                        </a>
                    </li>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </ul>
</nav>