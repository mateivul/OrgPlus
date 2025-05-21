<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();
$member_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$user_role = user_org_role($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    die('Permisiuni insuficiente');
}

$stmt_member = $mysqli->prepare(
    "SELECT u.name, u.prenume, r.role, r.total_contribution_hours 
     FROM users u JOIN roles r ON u.id = r.user_id 
     WHERE u.id = ? AND r.org_id = ?"
);
$stmt_member->bind_param('ii', $member_id, $org_id);
$stmt_member->execute();
$member = $stmt_member->get_result()->fetch_assoc();

if (!$member) {
    die('Membru negăsit');
}

$stmt_hours = $mysqli->prepare(
    "SELECT hours, work_date, description, recorded_at 
     FROM worked_hours 
     WHERE user_id = ? AND org_id = ? 
     ORDER BY work_date DESC"
);
$stmt_hours->bind_param('ii', $member_id, $org_id);
$stmt_hours->execute();
$hours = $stmt_hours->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'utils/global.html'; ?>
    <title>Detalii ore - <?php echo htmlspecialchars($member['name'] . ' ' . $member['prenume']); ?></title>
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

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
                        <?php while ($row = $hours->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['work_date']); ?></td>
                                <td><?php echo number_format($row['hours'], 1); ?></td>
                                <td><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['recorded_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>