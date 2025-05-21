<?php
require 'lib/conn_db.php';

while (ob_get_level()) {
    ob_end_clean();
}

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$user_role = user_org_role($user_id, $org_id);
$is_admin = in_array($user_role, ['admin', 'owner']);

if (!in_array($user_role, ['admin', 'owner'])) {
    die('Permisiuni insuficiente');
}

$sql_org = 'SELECT name FROM organizations WHERE id = ?';
$stmt_org = $mysqli->prepare($sql_org);
$stmt_org->bind_param('i', $org_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_name = $result_org->fetch_assoc()['name'];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
) {
    header('Content-Type: application/json');

    if (!in_array($user_role, ['admin', 'owner'])) {
        echo json_encode(['error' => true, 'message' => 'Permisiuni insuficiente']);
        exit();
    }

    $member_id = intval($_POST['member_id'] ?? 0);
    $hours = floatval($_POST['hours'] ?? 0);
    $work_date = $_POST['work_date'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($hours <= 0 || $hours > 24) {
        echo json_encode(['error' => true, 'message' => 'Ore invalide']);
        exit();
    }

    if (empty($work_date)) {
        echo json_encode(['error' => true, 'message' => 'Data invalida']);
        exit();
    }

    $mysqli->begin_transaction();

    try {
        $stmt_hours = $mysqli->prepare(
            'INSERT INTO worked_hours (user_id, org_id, hours, work_date, description, recorded_by) 
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt_hours->bind_param('iidssi', $member_id, $org_id, $hours, $work_date, $description, $user_id);
        $stmt_hours->execute();

        $stmt_update = $mysqli->prepare(
            'UPDATE roles SET 
             total_contribution_hours = total_contribution_hours + ?,
             last_activity_date = CURRENT_TIMESTAMP
             WHERE user_id = ? AND org_id = ?'
        );
        $stmt_update->bind_param('dii', $hours, $member_id, $org_id);
        $stmt_update->execute();

        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Ore adaugate cu succes']);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['error' => true, 'message' => 'Eroare la actualizare: ' . $e->getMessage()]);
    }
    exit();
}

$sql_members = "SELECT u.id, u.name, u.prenume, r.role, r.total_contribution_hours, 
                r.is_active, r.last_activity_date,
                (SELECT COUNT(*) FROM event_roles er 
                 WHERE er.user_id = u.id AND er.event_id IN 
                 (SELECT id FROM events WHERE org_id = ?)) AS events_participated,
                (SELECT COUNT(*) FROM events WHERE org_id = ?) AS total_events
                FROM users u
                JOIN roles r ON u.id = r.user_id
                WHERE r.org_id = ?
                ORDER BY r.total_contribution_hours DESC, events_participated DESC, u.name, u.prenume";
$stmt_members = $mysqli->prepare($sql_members);
$stmt_members->bind_param('iii', $org_id, $org_id, $org_id);
$stmt_members->execute();
$result_members = $stmt_members->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare Ore Lucrate - <?php echo htmlspecialchars($org_name); ?></title>
    <?php require 'utils/global.html'; ?>
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Gestionare Ore Lucrate - <?php echo htmlspecialchars($org_name); ?></h2>
            <a href="worked_hours_report.php?org_id=<?php echo $org_id; ?>" class="btn btn-info">Raport Ore</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Adaugă Ore Lucrate</h5>
            </div>
            <div class="card-body">
                <form id="addHoursForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="memberSelect" class="form-label">Membru</label>
                            <select class="form-select" id="memberSelect" name="member_id" required>
                                <option value="">Selectează un membru</option>
                                <?php while ($member = $result_members->fetch_assoc()): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['name'] . ' ' . $member['prenume']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="hoursInput" class="form-label">Ore</label>
                            <input type="number" step="0.5" min="0.5" max="24" class="form-control hours-input" 
                                   id="hoursInput" name="hours" required>
                        </div>
                        <div class="col-md-3">
                            <label for="dateInput" class="form-label">Data</label>
                            <input type="date" class="form-control date-input" id="dateInput" 
                                   name="work_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="descriptionInput" class="form-label">Descriere (opțional)</label>
                            <input type="text" class="form-control" id="descriptionInput" name="description">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Adaugă</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statistici Membri</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>Rol</th>
                            <th>Ore Totale</th>
                            <th>Evenimente Participare</th>
                            <th>Ultima Activitate</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $result_members->data_seek(0); ?>
                        <?php while ($member = $result_members->fetch_assoc()):
                            $is_active =
                                $member['is_active'] ||
                                $member['events_participated'] >= $member['total_events'] * 0.5 ||
                                $member['total_contribution_hours'] >= 20; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['name'] . ' ' . $member['prenume']); ?></td>
                                <td><?php echo role_to_readable($member['role']); ?></td>
                                <td><?php echo number_format($member['total_contribution_hours'] ?? 0, 1); ?></td>
                                <td><?php echo $member['events_participated'] . '/' . $member['total_events']; ?></td>
                                <td><?php echo $member['last_activity_date'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="member-status-<?php echo $is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active ? 'Activ' : 'Inactiv'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary toggle-status-btn" 
                                            data-member-id="<?php echo $member['id']; ?>"
                                            data-current-status="<?php echo $is_active ? '1' : '0'; ?>">
                                        <?php echo $is_active ? 'Marchează Inactiv' : 'Marchează Activ'; ?>
                                    </button>
                                    <a href="member_hours.php?user_id=<?php echo $member[
                                        'id'
                                    ]; ?>&org_id=<?php echo $org_id; ?>" 
                                       class="btn btn-sm btn-outline-info">Detalii ore</a>
                                </td>
                            </tr>
                        <?php
                        endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addHoursForm = document.getElementById('addHoursForm');
    
    addHoursForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(addHoursForm);
        
        fetch('working_hours.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succes!',
                    text: data.message,
                    showConfirmButton: true,
                    timer: 1500,
                    willClose: () => window.location.reload()
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Eroare',
                    text: data.message,
                    showConfirmButton: true
                });
            }
        })
        .catch(error => {
            console.error('Eroare:', error);
            Swal.fire({
                icon: 'error',
                title: 'Eroare',
                text: 'A apărut o eroare neașteptată',
                showConfirmButton: true
            });
        });
    });

    document.querySelectorAll('.toggle-status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const memberId = this.dataset.memberId;
            const orgId = <?php echo json_encode($org_id); ?>;
            const newStatus = this.dataset.currentStatus === '1' ? '0' : '1';
            
            Swal.fire({
                title: 'Confirmare',
                text: `Sigur doriți să ${newStatus === '1' ? 'activați' : 'dezactivați'} acest membru?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Da',
                cancelButtonText: 'Nu'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('toggle_member_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            member_id: memberId,
                            org_id: orgId,
                            new_status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Succes!',
                                text: data.message,
                                showConfirmButton: true,
                                timer: 1500,
                                willClose: () => window.location.reload()
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Eroare',
                                text: data.message,
                                showConfirmButton: true
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Eroare:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Eroare',
                            text: 'A apărut o eroare la actualizare',
                            showConfirmButton: true
                        });
                    });
                }
            });
        });
    });
});
</script>
</body>
</html>