<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../utils/app_helpers.php';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();
$member_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$roleRepository = getService('RoleRepository');
$workedHoursService = getService('WorkedHoursService');

$user_role = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    die('Permisiuni insuficiente');
}

// Get member info (fetch only the requested member for this org)
$member = null;
$members = $roleRepository->getOrganizationMembersWithRolesAndContribution($org_id);
foreach ($members as $m) {
    if ($m['id'] == $member_id) {
        $member = $m;
        break;
    }
}
if (!$member) {
    die('Membru negăsit');
}

// Get worked hours for this member (returns array of associative arrays or WorkedHour objects)
$hours = $workedHoursService->getWorkedHoursForUserInOrganization($member_id, $org_id);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/global.html'; ?>
    <title>Detalii ore - <?php echo htmlspecialchars($member['name'] . ' ' . $member['prenume']); ?></title>
</head>
<body>
<div class="d-flex">
    <?php
    // Initialize $authService before including the sidebar
    $authService = getService('AuthService');
    include_once __DIR__ . '/../includes/sidebar.php';
    ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Ore lucrate de <?php echo htmlspecialchars(
                $member['name'] . ' ' . $member['prenume']
            ); ?></h2>
            <a href="working_hours.php" class="btn btn-secondary">Înapoi</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Total ore: <?php echo number_format($member['total_contribution_hours'], 1); ?></h5>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Istoric ore</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ore</th>
                            <th>Descriere</th>
                            <th>Înregistrat la</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hours as $row): ?>
        <tr>
            <td>
                <?php
                // Safely get work_date
                $work_date = null;
                if (is_object($row)) {
                    // Use getter if available, else public property, else '-'
                    if (property_exists($row, 'work_date')) {
                        $work_date = $row->work_date;
                    }
                } else {
                    $work_date = $row['work_date'] ?? null;
                }
                echo htmlspecialchars($work_date ?? '-');
                ?>
            </td>
            <td>
                <?php
                // Safely get hours
                $hours_val = null;
                if (is_object($row)) {
                    if (property_exists($row, 'hours')) {
                        $hours_val = $row->hours;
                    }
                } else {
                    $hours_val = $row['hours'] ?? null;
                }
                echo number_format($hours_val ?? 0, 1);
                ?>
            </td>
            <td>
                <?php
                // Safely get description
                $desc = null;
                if (is_object($row)) {
                    if (property_exists($row, 'description')) {
                        $desc = $row->description;
                    }
                } else {
                    $desc = $row['description'] ?? null;
                }
                echo htmlspecialchars($desc ?? '-');
                ?>
            </td>
            <td>
                <?php
                // Show the date the hour entry was added (recorded_at)
                $recorded = null;
                if (is_object($row)) {
                    if (property_exists($row, 'recorded_at')) {
                        $recorded = $row->recorded_at;
                    }
                } else {
                    $recorded = $row['recorded_at'] ?? null;
                }
                echo htmlspecialchars($recorded ?? '-');
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>