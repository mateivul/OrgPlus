<?php
require_once __DIR__ . '/../src/config.php';

$authService = getService('AuthService');
$roleRepository = getService('RoleRepository');
$organizationService = getService('OrganizationService');
$workedHoursService = getService('WorkedHoursService');
$eventService = getService('EventService');

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$user_role = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
$is_admin = in_array($user_role, ['admin', 'owner']);

if (!$is_admin) {
    header('Location: dashboard.php?error=no_permission');
    exit('Permisiuni insuficiente');
}

$organization = $organizationService->getOrganizationById($org_id);
$org_name = $organization ? $organization->name : 'Organizație Necunoscută';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'toggle_member_status' && $is_admin) {
        $memberId = (int) ($input['member_id'] ?? 0);
        $newStatus = (int) ($input['new_status'] ?? 0);

        if ($memberId > 0) {
            $success = $roleRepository->updateMemberStatus($memberId, $org_id, $newStatus);
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Status membru actualizat cu succes!';
            } else {
                $response['message'] = 'Eroare la actualizarea statusului membrului.';
            }
        } else {
            $response['message'] = 'ID membru invalid.';
        }
        echo json_encode($response);
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_hours' && $is_admin) {
        $member_id = intval($_POST['member_id'] ?? 0);
        $hours = floatval($_POST['hours'] ?? 0);
        $work_date = $_POST['work_date'] ?? '';
        $description = $_POST['description'] ?? '';

        $result = $workedHoursService->addWorkedHours($member_id, $org_id, $hours, $work_date, $user_id, $description);
        $response = $result;
    } else {
        $response['message'] = 'Acțiune invalidă sau permisiuni insuficiente.';
    }

    echo json_encode($response);
    exit();
}

try {
    $membersData = $workedHoursService->getMembersWithContribution($org_id);
} catch (Exception $e) {
    error_log('Error fetching members for worked hours page: ' . $e->getMessage());
    $membersData = [];
}

$members = $roleRepository->getMembersByOrganization($org_id);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare Ore Lucrate - <?php echo htmlspecialchars($org_name); ?></title>
    <?php require_once __DIR__ . '/../includes/global.html'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

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
                    <input type="hidden" name="action" value="add_hours">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="memberSelect" class="form-label">Membru</label>
                            <select class="form-select" id="memberSelect" name="member_id" required>
                                <option value="">Selectează un membru</option>
                                <?php foreach ($membersData as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['name'] . ' ' . $member['prenume']); ?>
                                    </option>
                                <?php endforeach; ?>
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
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['name'] . ' ' . $member['prenume']); ?></td>
                                <td><?php echo role_to_readable($member['role']); ?></td>
                                <td><?php echo number_format($member['total_contribution_hours'] ?? 0, 1); ?></td>
                                <td><?php echo $member['events_participated'] ?? '0'; ?></td>
                                <td><?php echo $member['last_activity_date'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="member-status-<?php echo $member['is_active']
                                        ? 'active'
                                        : 'inactive'; ?>">
                                        <?php echo $member['is_active'] ? 'Activ' : 'Inactiv'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary toggle-status-btn"
                                            data-member-id="<?php echo $member['user_id']; ?>"
                                            data-current-status="<?php echo $member['is_active'] ? '1' : '0'; ?>">
                                        <?php echo $member['is_active'] ? 'Marchează Inactiv' : 'Marchează Activ'; ?>
                                    </button>
                                    <a href="member_hours.php?user_id=<?php echo $member[
                                        'user_id'
                                    ]; ?>&org_id=<?php echo $org_id; ?>"
                                       class="btn btn-sm btn-outline-info">Detalii ore</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addHoursForm = document.getElementById('addHoursForm');

    addHoursForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(addHoursForm);
        formData.append('action', 'add_hours');

        fetch('working_hours.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest' 
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text) });
            }
            return response.json();
        })
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
                text: 'A apărut o eroare neașteptată: ' + error.message,
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
                text: `Sigur doriți să ${newStatus === '1' ? 'marcați ca activ' : 'marcați ca inactiv'} acest membru?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Da',
                cancelButtonText: 'Nu'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('working_hours.php', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            action: 'toggle_member_status', 
                            member_id: memberId,
                            org_id: orgId,
                            new_status: newStatus
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => { throw new Error(text) });
                        }
                        return response.json();
                    })
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
                            text: 'A apărut o eroare la actualizare: ' + error.message,
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