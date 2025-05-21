<?php
$user_id = get_user_id();

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

$sql_check_role = "SELECT role FROM roles WHERE user_id = ? AND org_id = ? AND role IN ('admin', 'owner')";
$stmt_check_role = $mysqli->prepare($sql_check_role);

if ($stmt_check_role === false) {
    die('Eroare la pregătirea interogării: ' . $mysqli->error);
}

$stmt_check_role->bind_param('ii', $_SESSION['user_id'], $_SESSION['org_id']);

if (!$stmt_check_role->execute()) {
    die('Eroare la execuția interogării: ' . $stmt_check_role->error);
}

$result_check_role = $stmt_check_role->get_result();
$is_org_admin = $result_check_role->num_rows > 0;

$sql_check_member = 'SELECT 1 FROM roles WHERE user_id = ? AND org_id = ?';
$stmt_check_member = $mysqli->prepare($sql_check_member);
if ($stmt_check_member === false) {
    die('Eroare verificare membru: ' . $mysqli->error);
}
$stmt_check_member->bind_param('ii', $_SESSION['user_id'], $_SESSION['org_id']);
if (!$stmt_check_member->execute()) {
    die('Eroare executie verificare membru: ' . $stmt_check_member->error);
}
$is_org_member = $stmt_check_member->get_result()->num_rows > 0;
?>
    <?php require 'utils/global.html'; ?>

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
                    <i class="fas fa-building me-2"></i> Mesaje
                </a>
            </li>
            <?php if ($user_id): ?>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Deconectare
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center text-danger" href="login.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logare
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
                    <?php if ($is_org_admin === true): ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center text-light" href="inbox_org.php">
                                <i class="fas fa-building me-2"></i> Mesaje interne
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