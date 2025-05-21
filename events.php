<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$user_role = user_org_role($user_id, $org_id);
$is_admin = in_array($user_role, ['admin', 'owner']);

$response = ['error' => false, 'success' => false, 'message' => '', 'event_id' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'create_event' && $is_admin) {
        $event_name = $_POST['event_name'] ?? '';
        $event_description = $_POST['event_description'] ?? '';
        $event_date = $_POST['event_date'] ?? '';
        $roles_input = $_POST['roles'] ?? '';

        if (empty($event_name) || empty($event_description) || empty($event_date) || empty($roles_input)) {
            $response['error'] = true;
            $response['message'] = 'Toate câmpurile sunt obligatorii.';
            echo json_encode($response);
            exit();
        }

        $roles = explode(',', $roles_input);
        $processed_roles = [];
        foreach ($roles as $role) {
            $trimmed_role = trim($role);
            if (!empty($trimmed_role)) {
                $processed_roles[] = $trimmed_role;
            }
        }
        $roles_input_cleaned = implode(',', $processed_roles);

        $sql_event =
            'INSERT INTO events (name, description, date, org_id, created_by, available_roles) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt_event = $mysqli->prepare($sql_event);
        if ($stmt_event) {
            $stmt_event->bind_param(
                'sssiis',
                $event_name,
                $event_description,
                $event_date,
                $org_id,
                $user_id,
                $roles_input_cleaned
            );
            if ($stmt_event->execute()) {
                $event_id = $mysqli->insert_id;
                $response['success'] = true;
                $response['message'] = 'Evenimentul a fost creat cu succes!';
                $response['event_id'] = $event_id;

                $sql_creator_role = "INSERT INTO event_roles (user_id, event_id, role) VALUES (?, ?, 'Organizator')";
                $stmt_creator_role = $mysqli->prepare($sql_creator_role);
                if ($stmt_creator_role) {
                    $stmt_creator_role->bind_param('ii', $user_id, $event_id);
                    if (!$stmt_creator_role->execute()) {
                        error_log('Eroare la adăugarea creatorului: ' . $stmt_creator_role->error);
                    }
                    $stmt_creator_role->close();
                } else {
                    error_log('Eroare preparare statement creator: ' . $mysqli->error);
                }

                $sql_get_members = 'SELECT user_id FROM roles WHERE org_id = ?';
                $stmt_get_members = $mysqli->prepare($sql_get_members);
                if ($stmt_get_members) {
                    $stmt_get_members->bind_param('i', $org_id);
                    $stmt_get_members->execute();
                    $result_get_members = $stmt_get_members->get_result();

                    while ($member = $result_get_members->fetch_assoc()) {
                        $member_id = $member['user_id'];
                        if ($member_id != $user_id) {
                            $sql_insert_request =
                                'INSERT INTO requests (request_type, sender_user_id, receiver_user_id, organization_id, event_id) VALUES (?, ?, ?, ?, ?)';
                            $stmt_insert_request = $mysqli->prepare($sql_insert_request);
                            if ($stmt_insert_request) {
                                $request_type = 'event_invitation';
                                $stmt_insert_request->bind_param(
                                    'siiii',
                                    $request_type,
                                    $user_id,
                                    $member_id,
                                    $org_id,
                                    $event_id
                                );
                                $stmt_insert_request->execute();
                                $stmt_insert_request->close();
                            }
                        }
                    }
                    $stmt_get_members->close();
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Eroare la crearea evenimentului: ' . $stmt_event->error;
            }
            $stmt_event->close();
        } else {
            $response['error'] = true;
            $response['message'] = 'Eroare la pregătirea interogării: ' . $mysqli->error;
        }
        echo json_encode($response);
        exit();
    }

    $response['error'] = true;
    $response['message'] = 'Acțiune invalidă sau permisiuni insuficiente.';
    echo json_encode($response);
    exit();
}

$sql_events = 'SELECT id, name, description, date FROM events WHERE org_id = ? ORDER BY date ASC';
$stmt_events = $mysqli->prepare($sql_events);
if ($stmt_events) {
    $stmt_events->bind_param('i', $org_id);
    $stmt_events->execute();
    $result_events = $stmt_events->get_result();
} else {
    error_log('Eroare la preluarea evenimentelor: ' . $mysqli->error);
    $result_events = null;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'utils/global.html'; ?>
    <title>Evenimente</title>
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

    <div class="content p-4 w-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Evenimentele organizației</h2>
            <?php if ($is_admin): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">Crează Eveniment</button>
            <?php endif; ?>
        </div>

        <?php if ($result_events && $result_events->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nume</th>
                        <th>Descriere</th>
                        <th>Data</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $result_events->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['name']) ?></td>
                            <td><?= htmlspecialchars(substr($event['description'], 0, 100)) .
                                (strlen($event['description']) > 100 ? '...' : '') ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($event['date'])) ?></td>
                            <td>
                                <a href="view_event.php?event_id=<?= $event[
                                    'id'
                                ] ?>" class="btn btn-info btn-sm">Vezi Detalii</a>
                                <?php if ($is_admin): ?>
                                    <a href="assign_roles.php?event_id=<?= $event[
                                        'id'
                                    ] ?>" class="btn btn-warning btn-sm">Roluri</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php elseif ($result_events): ?>
            <div class="alert alert-info">Nu există evenimente</div>
        <?php else: ?>
            <div class="alert alert-danger">Eroare la încărcarea datelor</div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Creează Eveniment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createEventForm">
                    <input type="hidden" name="action" value="create_event">
                    <div class="mb-3">
                        <label>Nume Eveniment</label>
                        <input type="text" class="form-control" name="event_name" required>
                    </div>
                    <div class="mb-3">
                        <label>Descriere</label>
                        <textarea class="form-control" name="event_description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Dată și Ora</label>
                        <input type="datetime-local" class="form-control" name="event_date" required>
                    </div>
                    <div class="mb-3">
                        <label>Roluri (separate prin virgulă)</label>
                        <input type="text" class="form-control" name="roles" placeholder="Ex: Manager, Participant" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Creează</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createEventForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Succes!', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Eroare!', data.message, 'error');
                }
            })
            .catch(e => console.error('Eroare:', e));
        });
    }
});
</script>
</body>
</html>