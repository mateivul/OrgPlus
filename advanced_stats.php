<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$user_role = user_org_role($user_id, $org_id);

$sql_org = 'SELECT name FROM organizations WHERE id = ?';
$stmt_org = $mysqli->prepare($sql_org);
$stmt_org->bind_param('i', $org_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_name = $result_org->fetch_assoc()['name'];
$result_org->close();
$stmt_org->close();

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : null;

$stats = [
    'total_members' => 0,
    'active_members' => 0,
    'total_events' => 0,
    'total_hours' => 0,
    'avg_hours_per_member' => 0,
    'participation_rate' => 0,
    'events_by_month' => [],
    'hours_by_month' => [],
    'member_roles_distribution' => [],
    'hours_distribution' => [],
    'top_members' => [],
];

$sql_members = "SELECT COUNT(*) as total,
                 SUM(CASE WHEN is_active = 1 OR
                           (SELECT COUNT(*) FROM event_roles WHERE user_id = r.user_id) > 0 OR
                           total_contribution_hours >= 20 THEN 1 ELSE 0 END) as active
                 FROM roles r WHERE org_id = ?";
$stmt_members = $mysqli->prepare($sql_members);
$stmt_members->bind_param('i', $org_id);
$stmt_members->execute();
$result_members = $stmt_members->get_result();
if ($row = $result_members->fetch_assoc()) {
    $stats['total_members'] = $row['total'];
    $stats['active_members'] = $row['active'];
}
$result_members->close();
$stmt_members->close();

$sql_events = 'SELECT COUNT(*) as total FROM events WHERE org_id = ?';
if ($month) {
    $sql_events .= ' AND YEAR(date) = ? AND MONTH(date) = ?';
    $stmt_events = $mysqli->prepare($sql_events);
    $stmt_events->bind_param('iii', $org_id, $year, $month);
} else {
    $sql_events .= ' AND YEAR(date) = ?';
    $stmt_events = $mysqli->prepare($sql_events);
    $stmt_events->bind_param('ii', $org_id, $year);
}
$stmt_events->execute();
$result_events = $stmt_events->get_result();
$stats['total_events'] = $result_events->fetch_assoc()['total'] ?? 0;
$result_events->close();
$stmt_events->close();

$sql_hours = 'SELECT SUM(hours) as total FROM worked_hours WHERE org_id = ?';
if ($month) {
    $sql_hours .= ' AND YEAR(work_date) = ? AND MONTH(work_date) = ?';
    $stmt_hours = $mysqli->prepare($sql_hours);
    $stmt_hours->bind_param('iii', $org_id, $year, $month);
} else {
    $sql_hours .= ' AND YEAR(work_date) = ?';
    $stmt_hours = $mysqli->prepare($sql_hours);
    $stmt_hours->bind_param('ii', $org_id, $year);
}
$stmt_hours->execute();
$result_hours = $stmt_hours->get_result();
$total_hours = $result_hours->fetch_assoc()['total'];
$stats['total_hours'] = $total_hours ? round($total_hours, 2) : 0;
$stats['avg_hours_per_member'] =
    $stats['total_members'] > 0 ? round($stats['total_hours'] / $stats['total_members'], 2) : 0;
$result_hours->close();
$stmt_hours->close();

$sql_participation = "SELECT
                      (SELECT COUNT(DISTINCT user_id) FROM event_roles er
                       JOIN events e ON er.event_id = e.id
                       WHERE e.org_id = ?";
if ($month) {
    $sql_participation .= ' AND YEAR(e.date) = ? AND MONTH(e.date) = ?';
} else {
    $sql_participation .= ' AND YEAR(e.date) = ?';
}
$sql_participation .= ") as participants,
                     (SELECT COUNT(*) FROM roles WHERE org_id = ?) as total_members";

$stmt_participation = $mysqli->prepare($sql_participation);
if ($month) {
    $stmt_participation->bind_param('iiii', $org_id, $year, $month, $org_id);
} else {
    $stmt_participation->bind_param('iii', $org_id, $year, $org_id);
}
$stmt_participation->execute();
$result_participation = $stmt_participation->get_result();
$participation = $result_participation->fetch_assoc();
$stats['participation_rate'] =
    ($participation['total_members'] ?? 0) > 0
        ? round((($participation['participants'] ?? 0) / ($participation['total_members'] ?? 0)) * 100, 1) // Adăugat ?? 0
        : 0;
$result_participation->close();
$stmt_participation->close();

$sql_events_month = "SELECT MONTH(date) as month, COUNT(*) as count
                     FROM events
                     WHERE org_id = ? AND YEAR(date) = ?
                     GROUP BY MONTH(date)
                     ORDER BY month";
$stmt_events_month = $mysqli->prepare($sql_events_month);
$stmt_events_month->bind_param('ii', $org_id, $year);
$stmt_events_month->execute();
$result_events_month = $stmt_events_month->get_result();
while ($row = $result_events_month->fetch_assoc()) {
    $stats['events_by_month'][$row['month']] = $row['count'];
}
$result_events_month->close();
$stmt_events_month->close();

$sql_hours_month = "SELECT MONTH(work_date) as month, SUM(hours) as total
                    FROM worked_hours
                    WHERE org_id = ? AND YEAR(work_date) = ?
                    GROUP BY MONTH(work_date)
                    ORDER BY month";
$stmt_hours_month = $mysqli->prepare($sql_hours_month);
$stmt_hours_month->bind_param('ii', $org_id, $year);
$stmt_hours_month->execute();
$result_hours_month = $stmt_hours_month->get_result();
while ($row = $result_hours_month->fetch_assoc()) {
    $stats['hours_by_month'][$row['month']] = round($row['total'], 2);
}
$result_hours_month->close();
$stmt_hours_month->close();

$sql_top_members = "SELECT u.id, u.name, u.prenume, SUM(wh.hours) as total_hours
                    FROM worked_hours wh
                    JOIN users u ON wh.user_id = u.id
                    WHERE wh.org_id = ?";
if ($month) {
    $sql_top_members .= ' AND YEAR(wh.work_date) = ? AND MONTH(wh.work_date) = ?';
} else {
    $sql_top_members .= ' AND YEAR(wh.work_date) = ?';
}
$sql_top_members .= ' GROUP BY wh.user_id ORDER BY total_hours DESC LIMIT 5';

$stmt_top_members = $mysqli->prepare($sql_top_members);
if ($month) {
    $stmt_top_members->bind_param('iii', $org_id, $year, $month);
} else {
    $stmt_top_members->bind_param('ii', $org_id, $year);
}
$stmt_top_members->execute();
$result_top_members = $stmt_top_members->get_result();
while ($row = $result_top_members->fetch_assoc()) {
    $stats['top_members'][] = [
        'id' => $row['id'],
        'name' => $row['name'] . ' ' . $row['prenume'],
        'hours' => round($row['total_hours'], 2),
    ];
}
$result_top_members->close();
$stmt_top_members->close();

$user_in_top = false;
foreach ($stats['top_members'] as $member) {
    if ($member['id'] == $user_id) {
        $user_in_top = true;
        break;
    }
}

$user_rank = null;
$user_hours = 0;
$user_name = '';

if (!$user_in_top) {
    $sql_user = "SELECT SUM(wh.hours) as total_hours, u.name, u.prenume
                 FROM worked_hours wh
                 JOIN users u ON wh.user_id = u.id
                 WHERE wh.org_id = ?
                 AND wh.user_id = ?";

    if ($month) {
        $sql_user .= ' AND YEAR(wh.work_date) = ? AND MONTH(wh.work_date) = ?';
        $stmt_user = $mysqli->prepare($sql_user);
        $stmt_user->bind_param('iiii', $org_id, $user_id, $year, $month);
    } else {
        $sql_user .= ' AND YEAR(wh.work_date) = ?';
        $stmt_user = $mysqli->prepare($sql_user);
        $stmt_user->bind_param('iii', $org_id, $user_id, $year);
    }

    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($row = $result_user->fetch_assoc()) {
        $user_hours = round($row['total_hours'] ?? 0, 2);
        $user_name = ($row['name'] ?? '') . ' ' . ($row['prenume'] ?? '');
    }
    $result_user->close();
    $stmt_user->close();

    if ($user_hours > 0) {
        $sql_rank =
            "SELECT COUNT(*) as rank
                 FROM (
                     SELECT user_id, SUM(hours) as total
                     FROM worked_hours
                     WHERE org_id = ?
                     AND YEAR(work_date) = ?
                     " .
            ($month ? 'AND MONTH(work_date) = ?' : '') .
            "
                     GROUP BY user_id
                 ) as t
                 WHERE t.total > ?";

        $stmt_rank = $mysqli->prepare($sql_rank);
        $params = [$org_id, $year];
        if ($month) {
            $params[] = $month;
        }
        $params[] = $user_hours;

        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt_rank->bind_param($types, ...$params);
        $stmt_rank->execute();

        $result_rank = $stmt_rank->get_result();
        if ($row_rank = $result_rank->fetch_assoc()) {
            $user_rank = $row_rank['rank'];
        }
        $result_rank->close();
        $stmt_rank->close();
    }
}

$sql_roles = 'SELECT role, COUNT(*) as count FROM roles WHERE org_id = ? GROUP BY role';
$stmt_roles = $mysqli->prepare($sql_roles);
$stmt_roles->bind_param('i', $org_id);
$stmt_roles->execute();
$result_roles = $stmt_roles->get_result();
while ($row = $result_roles->fetch_assoc()) {
    $stats['member_roles_distribution'][] = [
        'role' => role_to_readable($row['role']),
        'count' => $row['count'],
    ];
}
$result_roles->close();
$stmt_roles->close();

$sql_hours_distribution = "SELECT
                             hours_range,
                             COUNT(user_id) as members_count
                             FROM (
                                 SELECT
                                     wh.user_id,
                                     CASE
                                         WHEN SUM(wh.hours) < 10 THEN 'Sub 10 ore'
                                         WHEN SUM(wh.hours) BETWEEN 10 AND 50 THEN '10-50 ore'
                                         WHEN SUM(wh.hours) BETWEEN 51 AND 100 THEN '51-100 ore'
                                         ELSE 'Peste 100 ore'
                                     END as hours_range
                                 FROM worked_hours wh
                                 WHERE wh.org_id = ?";

if ($month) {
    $sql_hours_distribution .= ' AND YEAR(wh.work_date) = ? AND MONTH(wh.work_date) = ?';
} else {
    $sql_hours_distribution .= ' AND YEAR(wh.work_date) = ?';
}

$sql_hours_distribution .= " GROUP BY wh.user_id
                             ) as subquery
                             GROUP BY hours_range
                             ORDER BY FIELD(hours_range, 'Sub 10 ore', '10-50 ore', '51-100 ore', 'Peste 100 ore')"; // Adăugat ORDER BY pentru coerență

$stmt_hours_distribution = $mysqli->prepare($sql_hours_distribution);

if ($month) {
    $stmt_hours_distribution->bind_param('iii', $org_id, $year, $month);
} else {
    $stmt_hours_distribution->bind_param('ii', $org_id, $year);
}

$stmt_hours_distribution->execute();
$result_hours_distribution = $stmt_hours_distribution->get_result();

while ($row = $result_hours_distribution->fetch_assoc()) {
    $stats['hours_distribution'][] = [
        'range' => $row['hours_range'],
        'count' => $row['members_count'],
    ];
}
$result_hours_distribution->close();
$stmt_hours_distribution->close();

$chart_data = [
    'events_by_month' => [],
    'hours_by_month' => [],
    'roles_distribution' => [],
    'hours_distribution' => [],
];

for ($i = 1; $i <= 12; $i++) {
    $month_name = date('M', mktime(0, 0, 0, $i, 1));
    $chart_data['events_by_month'][] = [
        'month' => $month_name,
        'count' => $stats['events_by_month'][$i] ?? 0,
    ];
    $chart_data['hours_by_month'][] = [
        'month' => $month_name,
        'hours' => $stats['hours_by_month'][$i] ?? 0,
    ];
}

foreach ($stats['member_roles_distribution'] as $role) {
    $chart_data['roles_distribution'][] = [
        'role' => $role['role'],
        'count' => $role['count'],
    ];
}

$hours_ranges_order = ['Sub 10 ore', '10-50 ore', '51-100 ore', 'Peste 100 ore'];
$hours_distribution_map = array_column($stats['hours_distribution'], 'count', 'range');

foreach ($hours_ranges_order as $range) {
    $chart_data['hours_distribution'][] = [
        'range' => $range,
        'count' => $hours_distribution_map[$range] ?? 0,
    ];
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'utils/global.html'; ?>
    <title>Statistici - <?php echo htmlspecialchars($org_name); ?></title>
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Statistici Organizație - <?php echo htmlspecialchars($org_name); ?></h2>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Membri Total</h5>
                        <h2 class="card-text"><?php echo $stats['total_members']; ?></h2>
                        <small><?php echo $stats['active_members']; ?> activi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Evenimente</h5>
                        <h2 class="card-text"><?php echo $stats['total_events']; ?></h2>
                        <small>în <?php echo $month ? date('F Y', mktime(0, 0, 0, $month, 1, $year)) : $year; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Ore Lucrate</h5>
                        <h2 class="card-text"><?php echo $stats['total_hours']; ?></h2>
                        <small><?php echo $stats['avg_hours_per_member']; ?> ore/membru</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Participare</h5>
                        <h2 class="card-text"><?php echo $stats['participation_rate']; ?>%</h2>
                        <small>rata de participare</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Evenimente pe luni</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="eventsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Ore lucrate pe luni</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="hoursChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Distribuție roluri membri</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="rolesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Distribuție ore pe membri</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="hoursDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Top membri după ore lucrate</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Membru</th>
                                    <th>Ore</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['top_members'])): ?>
                                    <?php foreach ($stats['top_members'] as $index => $member): ?>
                                        <tr <?php if ($member['id'] == $user_id) {
                                            echo 'class="fw-bold"';
                                        } ?>>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                                            <td><?php echo $member['hours']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (!$user_in_top && $user_rank !== null && $user_hours > 0): ?>
                                        <tr class="fw-bold table-primary"> <td><?php echo $user_rank +
                                            1; ?></td> <td><?php echo htmlspecialchars($user_name); ?></td>
                                            <td><?php echo $user_hours; ?></td>
                                        </tr>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nu există date pentru top membri</td>
                                    </tr>
                                    <?php if ($user_hours > 0): ?>
                                         <tr class="fw-bold table-primary"> <td>N/A</td> <td><?php echo htmlspecialchars(
                                             $user_name
                                         ); ?></td>
                                            <td><?php echo $user_hours; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Ultimele evenimente</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $sql_last_events = "SELECT id, name, date FROM events
                                             WHERE org_id = ?
                                             ORDER BY date DESC LIMIT 5";
                        $stmt_last_events = $mysqli->prepare($sql_last_events);
                        $stmt_last_events->bind_param('i', $org_id);
                        $stmt_last_events->execute();
                        $result_last_events = $stmt_last_events->get_result();
                        ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Eveniment</th>
                                    <th>Data</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_last_events->num_rows > 0): ?>
                                    <?php while ($event = $result_last_events->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['name']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($event['date'])); ?></td>
                                            <td>
                                                <a href="view_event.php?event_id=<?php echo $event[
                                                    'id'
                                                ]; ?>" class="btn btn-sm btn-info">Vezi</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nu există evenimente</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php
                        if ($result_last_events) {
                            $result_last_events->close();
                        }
                        if ($stmt_last_events) {
                            $stmt_last_events->close();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventsData = <?php echo json_encode($chart_data['events_by_month']); ?>;
    const hoursData = <?php echo json_encode($chart_data['hours_by_month']); ?>;
    const rolesData = <?php echo json_encode($chart_data['roles_distribution']); ?>;
    const hoursDistributionData = <?php echo json_encode($chart_data['hours_distribution']); ?>;

    const eventsCtx = document.getElementById('eventsChart').getContext('2d');
    new Chart(eventsCtx, {
        type: 'bar',
        data: {
            labels: eventsData.map(item => item.month),
            datasets: [{
                label: 'Număr evenimente',
                data: eventsData.map(item => item.count),
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
             plugins: {
                legend: {
                    display: false 
                }
            }
        }
    });

    const hoursCtx = document.getElementById('hoursChart').getContext('2d');
    new Chart(hoursCtx, {
        type: 'line',
        data: {
            labels: hoursData.map(item => item.month),
            datasets: [{
                label: 'Ore lucrate',
                data: hoursData.map(item => item.hours),
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
             plugins: {
                legend: {
                    display: false 
                }
            }
        }
    });

    const rolesCtx = document.getElementById('rolesChart').getContext('2d');
    new Chart(rolesCtx, {
        type: 'pie',
        data: {
            labels: rolesData.map(item => item.role),
            datasets: [{
                data: rolesData.map(item => item.count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: '#ffffff', 
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom', 
                }
            }
        }
    });

    const hoursDistCtx = document.getElementById('hoursDistributionChart').getContext('2d');
    new Chart(hoursDistCtx, {
        type: 'doughnut',
        data: {
            labels: hoursDistributionData.map(item => item.range),
            datasets: [{
                data: hoursDistributionData.map(item => item.count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)', 
                    'rgba(54, 162, 235, 0.7)', 
                    'rgba(255, 206, 86, 0.7)', 
                    'rgba(75, 192, 192, 0.7)' 
                ],
                 borderColor: '#ffffff', 
                 borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
             plugins: {
                legend: {
                    position: 'bottom', 
                }
            }
        }
    });

    document.getElementById('yearSelect').addEventListener('change', function() {
        applyFilters();
    });

    document.getElementById('monthSelect').addEventListener('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const year = document.getElementById('yearSelect').value;
        const month = document.getElementById('monthSelect').value;

        let url = `org_statistics.php?year=${year}`;
        if (month) {
            url += `&month=${month}`;
        }

        window.location.href = url;
    }

    document.getElementById('exportBtn').addEventListener('click', function() {
        const year = document.getElementById('yearSelect').value;
        const month = document.getElementById('monthSelect').value;

        let url = `export_statistics.php?org_id=<?php echo $org_id; ?>&year=${year}`;
        if (month) {
            url += `&month=${month}`;
        }

        window.open(url, '_blank');

    });
});
</script>
</body>
</html>