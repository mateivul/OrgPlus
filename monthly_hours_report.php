<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$org_id = isset($_GET['org_id']) ? intval($_GET['org_id']) : ensure_existing_org();
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

$user_role = user_org_role($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    die('Permisiuni insuficiente');
}

$stmt_org = $mysqli->prepare('SELECT name FROM organizations WHERE id = ?');
$stmt_org->bind_param('i', $org_id);
$stmt_org->execute();
$org_name = $stmt_org->get_result()->fetch_assoc()['name'];

$stmt_report = $mysqli->prepare(
    "SELECT 
        u.id AS user_id,
        u.name,
        u.prenume,
        wh.hours,
        wh.work_date,
        wh.description,
        wh.recorded_at
     FROM worked_hours wh
     JOIN users u ON wh.user_id = u.id
     WHERE wh.org_id = ? AND YEAR(wh.work_date) = ? AND MONTH(wh.work_date) = ?
     ORDER BY wh.work_date DESC, u.name, u.prenume"
);
$stmt_report->bind_param('iii', $org_id, $year, $month);
$stmt_report->execute();
$report = $stmt_report->get_result();

$month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'utils/global.html'; ?>
    <title>Raport lunar ore - <?php echo htmlspecialchars($org_name); ?></title>
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Raport ore <?php echo htmlspecialchars($month_name); ?> - <?php echo htmlspecialchars(
     $org_name
 ); ?></h2>
            <a href="worked_hours_report.php?org_id=<?php echo $org_id; ?>" class="btn btn-secondary">Înapoi</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detalii ore lucrate</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Membru</th>
                            <th>Data</th>
                            <th>Ore</th>
                            <th>Înregistrat la</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_user = null;
                        while ($row = $report->fetch_assoc()):
                            if ($current_user !== $row['user_id']) {
                                $current_user = $row['user_id'];
                                $show_name = true;
                            } else {
                                $show_name = false;
                            } ?>
                            <tr>
                                <td>
                                    <?php if ($show_name): ?>
                                        <span class="member-name"><?php echo htmlspecialchars(
                                            $row['name'] . ' ' . $row['prenume']
                                        ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['work_date']); ?></td>
                                <td><?php echo number_format($row['hours'], 1); ?></td>
                                <td><?php echo htmlspecialchars($row['recorded_at']); ?></td><td>
                                    <a href="member_hours.php?user_id=<?php echo $row[
                                        'user_id'
                                    ]; ?>&org_id=<?php echo $org_id; ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" 
                                       class="btn btn-sm btn-outline-info">Detalii</a>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>