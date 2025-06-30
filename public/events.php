<?php

require_once __DIR__ . '/../src/config.php';

$authService = getService('AuthService');
$roleRepository = getService('RoleRepository');

ensure_logged_in();
ensure_existing_org();

$eventService = getService('EventService');
$organizationService = getService('OrganizationService');
$roleRepository = getService('RoleRepository');

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$user_role = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
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

        try {
            $result = $eventService->createEvent(
                $event_name,
                $event_description,
                $event_date,
                $org_id,
                $user_id,
                $roles_input_cleaned
            );

            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = $result['message'];
                $response['event_id'] = $result['event_id'];
            } else {
                $response['error'] = true;
                $response['message'] = $result['message'];
            }
        } catch (Exception $e) {
            $response['error'] = true;
            $response['message'] = 'Eroare la crearea evenimentului: ' . $e->getMessage();
            error_log('Event creation error: ' . $e->getMessage());
        }
        echo json_encode($response);
        exit();
    }

    $response['error'] = true;
    $response['message'] = 'Acțiune invalidă sau permisiuni insuficiente.';
    echo json_encode($response);
    exit();
}

try {
    $events = $eventService->getEventsForOrganization($org_id);
} catch (Exception $e) {
    error_log('Error fetching events for organization: ' . $e->getMessage());
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../includes/global.html'; ?>
    <title>Evenimente</title>
</head>
<body>
<div class="d-flex">
    <?php require '../includes/sidebar.php'; ?>

    <div class="content p-4 w-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Evenimentele organizației</h2>
            <?php if ($is_admin): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">Crează Eveniment</button>
            <?php endif; ?>
        </div>

        <?php if (!empty($events)): ?>
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
                    <?php foreach ($events as $event): ?>
                        <?php
                        /** @var Event $event */
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($event->name) ?></td>
                            <td><?= htmlspecialchars(substr($event->description, 0, 100)) .
                                (strlen($event->description) > 100 ? '...' : '') ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($event->date)) ?></td>
                            <td>
                                <a href="view_event.php?event_id=<?= $event->id ?>" class="btn btn-info btn-sm">Vezi Detalii</a>
                                <?php if ($is_admin): ?>
                                    <a href="assign_roles.php?event_id=<?= $event->id ?>" class="btn btn-warning btn-sm">Roluri</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">Nu există evenimente</div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventLabe_header.phpl" aria-hidden="true">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok: ' + r.statusText);
                }
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Succes!', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Eroare!', data.message, 'error');
                }
            })
            .catch(e => {
                console.error('Error:', e);
                Swal.fire('Eroare!', 'A apărut o eroare la trimiterea cererii: ' + e.message, 'error');
            });
        });
    }
});
</script>
</body>
</html>