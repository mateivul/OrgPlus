<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$org_id = $_SESSION['org_id'];

$sql_org = 'SELECT name, created_at FROM organizations WHERE id = ?';
$stmt_org = $mysqli->prepare($sql_org);
$stmt_org->bind_param('i', $org_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$organization = $result_org->fetch_assoc();
$org_name = $organization['name'] ?? 'Organizație necunoscută';
$created_at_str = $organization['created_at'] ?? null;
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
        $activity_text = 'Eroare la calculul activității';
        $formatted_creation_date = 'Eroare la dată';
    }
}

// Numărul de membri
$sql_members_count = 'SELECT COUNT(*) as total FROM roles WHERE org_id = ?';
$stmt_members_count = $mysqli->prepare($sql_members_count);
$stmt_members_count->bind_param('i', $org_id);
$stmt_members_count->execute();
$result_members_count = $stmt_members_count->get_result();
$members_count = $result_members_count->fetch_assoc()['total'];
$stmt_members_count->close();

// Numărul de evenimente
$sql_events_count = 'SELECT COUNT(*) as total FROM events WHERE org_id = ?';
$stmt_events_count = $mysqli->prepare($sql_events_count);
$stmt_events_count->bind_param('i', $org_id);
$stmt_events_count->execute();
$result_events_count = $stmt_events_count->get_result();
$events_count = $result_events_count->fetch_assoc()['total'];
$stmt_events_count->close();

// Ultimul eveniment
$sql_last_event = 'SELECT name, date FROM events WHERE org_id = ? ORDER BY date DESC LIMIT 1';
$stmt_last_event = $mysqli->prepare($sql_last_event);
$stmt_last_event->bind_param('i', $org_id);
$stmt_last_event->execute();
$result_last_event = $stmt_last_event->get_result();
$last_event = $result_last_event->fetch_assoc();
$last_event_name = $last_event['name'] ?? 'Niciun eveniment recent';
$last_event_date = $last_event['date'] ?? 'N/A';
$stmt_last_event->close();

// Numele utilizatorului curent
$name = get_user_name($user_id);

$user_role = user_org_role($user_id, $org_id);

$can_access_members = $user_role === 'admin' || $user_role === 'member' || $user_role === 'owner';
$can_access_events = $user_role === 'admin' || $user_role === 'member' || $user_role === 'owner';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($org_name); ?></title>
    <?php require 'utils/global.html'; ?>
    <link rel="stylesheet" href="styles/dashboard-style.css">
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

    <div class="content p-4">
        <div class="welcome-banner mb-5">
            <h2>Bine ai venit, <span class="highlight-name"><?php echo htmlspecialchars(
                $name
            ); ?></span>, în organizația <span class="highlight-org"><?php echo htmlspecialchars(
    $org_name
); ?></span>!</h2>
        </div>

        <div class="my-cards-container">
            <div class="my-card">
                <div class="card hover-card h-100">
                    <div class="card-body">
                        <h5 class="my-card-title"><i class="fas fa-users"></i> Membrii</h5>
                        <div class="card-content">
                            <p class="card-stats"><?php echo $members_count; ?></p>
                        </div>
                        <div class="card-footer-action">
                            <?php if ($can_access_members): ?>
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
                            <p class="card-stats"><?php echo $events_count; ?></p>
                        </div>
                        <div class="card-footer-action">
                            <?php if ($can_access_events): ?>
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
                             <?php if ($can_access_events): ?>
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


<?php $mysqli->close(); ?>
</body>
</html>