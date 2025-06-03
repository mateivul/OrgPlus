<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../utils/app_helpers.php';

$user_id = ensure_logged_in();
$org_id = isset($_GET['org_id']) ? intval($_GET['org_id']) : ensure_existing_org();

$roleRepository = getService('RoleRepository');
$user_role = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    die('Permisiuni insuficiente');
}

$orgRepository = getService('OrganizationRepository');
$organization = $orgRepository->findById($org_id);
$org_name = $organization ? $organization->getName() : 'Organizație necunoscută';

$workedHoursRepository = getService('WorkedHoursRepository');

// Fetch monthly summary report for the organization
$sql = "SELECT 
            YEAR(work_date) AS year,
            MONTH(work_date) AS month,
            SUM(hours) AS total_hours,
            COUNT(DISTINCT user_id) AS members_count
        FROM worked_hours
        WHERE org_id = :org_id
        GROUP BY YEAR(work_date), MONTH(work_date)
        ORDER BY year DESC, month DESC";
$stmt = $workedHoursRepository->getPdo()->prepare($sql);
$stmt->execute(['org_id' => $org_id]);
$report = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport ore - <?php echo htmlspecialchars($org_name); ?></title>
    <?php require_once __DIR__ . '/../includes/global.html'; ?>
</head>
<body>
<div class="d-flex">
    <?php
    $authService = getService('AuthService');
    include_once __DIR__ . '/../includes/sidebar.php';
    ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Raport ore lucrate - <?php echo htmlspecialchars($org_name); ?></h2>
            <a href="working_hours.php" class="btn btn-secondary">Înapoi</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Sumar pe luni</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Luna</th>
                            <th>Ore totale</th>
                            <th>Membri activi</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report as $row):
                            $month_name = date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($month_name); ?></td>
                                <td><?php echo number_format($row['total_hours'], 1); ?></td>
                                <td><?php echo $row['members_count']; ?></td>
                                <td>
                                    <a href="monthly_hours_report.php?org_id=<?php echo $org_id; ?>&year=<?php echo $row['year']; ?>&month=<?php echo $row['month']; ?>" 
                                       class="btn btn-sm btn-outline-info">Detalii</a>
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