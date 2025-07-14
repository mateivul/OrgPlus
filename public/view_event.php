<?php

require_once __DIR__ . '/../src/config.php';

$authService = getService('AuthService');
$eventService = getService('EventService');
$roleRepository = getService('RoleRepository');
$userRepository = getService('UserRepository');
$organizationRepository = getService('OrganizationRepository');

$user_id = ensure_logged_in();
ensure_existing_org();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$event = $eventService->getEventDetails($event_id);

if (!$event) {
    die('Evenimentul nu există sau nu aveți permisiunea să-l vizualizați.');
}

$current_org_id = $_SESSION['org_id'];
if ($event->orgId !== $current_org_id) {
    header('Location: events.php');
    exit();
}

$user_role_in_org = $roleRepository->getUserRoleInOrganization($user_id, $current_org_id);
$is_org_admin = in_array($user_role_in_org, ['admin', 'owner']);

$organization = $organizationRepository->findById($current_org_id);
$is_event_admin_or_owner = $is_org_admin || ($organization && $user_id === $organization->ownerId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_event_admin_or_owner) {
    if (!isset($_POST['csrf_token']) || !CsrfToken::validateToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit();
    }

    $response = ['success' => false, 'message' => 'A apărut o eroare neașteptată.'];

    if (isset($_POST['save_tasks'])) {
        try {
            $eventService->saveEventTasks($event_id, $user_id, $_POST);
            $response = ['success' => true, 'message' => 'Sarcinile au fost salvate cu succes!'];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
            error_log("Task save error for event $event_id: " . $e->getMessage());
        }
    } elseif (isset($_POST['delete_event'])) {
        try {
            $success = $eventService->deleteEvent($event_id, $current_org_id);
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Evenimentul a fost șters cu succes!',
                    'redirect' => 'events.php',
                ];
            } else {
                $response['message'] = 'Evenimentul nu a putut fi șters sau nu a fost găsit.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            error_log("Event deletion error for event $event_id: " . $e->getMessage());
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$event_participants_with_roles = $eventService->getEventParticipantsWithRoles($event_id, $search_term);

$raw_event_tasks = $eventService->getEventTasks($event_id);
$tasks_by_user = [];
foreach ($raw_event_tasks as $task) {
    $tasks_by_user[$task->userId] = $task->taskDescription;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Vizualizare Eveniment - <?php echo htmlspecialchars($event->name); ?></title>
    <?php  ?>
</head>
<body>
    <div class="d-flex">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="content p-4 w-100">
            <div class="my-evet-card text-white mb-4 p-4 shadow">
                <h2><?php echo htmlspecialchars($event->name); ?></h2>
                <p><strong>Organizație:</strong> <a class="participant-link" href="dashboard.php"><?php echo htmlspecialchars(
                    $event->orgName
                ); ?></a></p>
                <p><strong>Descriere:</strong> <?php echo nl2br(htmlspecialchars($event->description)); ?></p>
                <p><strong>Data:</strong> <?php echo date('d-m-Y H:i', strtotime($event->date)); ?></p>
                <p><a href="events.php" class="btn btn-sm btn-outline-light mt-2">&laquo; Înapoi la Evenimente</a></p>
                <?php if ($is_event_admin_or_owner): ?>
                    <button id="deleteEventBtn" class="btn btn-danger btn-sm mt-2">Șterge Eveniment</button>
                    <form id="deleteEventForm" class="d-none"><?= CsrfToken::csrfField() ?></form>
                <?php endif; ?>
            </div>

            <div class="my-evet-card text-light p-3 mb-4 shadow-sm">
                <h4 class="mb-3">Participanți și Roluri</h4>
                <?php if (!empty($event_participants_with_roles)): ?>
                    <ul class="list-unstyled">
                        <?php foreach ($event_participants_with_roles as $participant): ?>
                            <li>
                                <strong>
                                    <a class="participant-link" href="public_profile.php?user_id=<?php echo $participant->userId; ?>">
                                        <?php echo htmlspecialchars(
                                            $participant->userName . ' ' . $participant->userPrenume
                                        ); ?>
                                    </a>:
                                </strong>
                                <?php echo htmlspecialchars($participant->role); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Nu sunt asignați participanți pentru acest eveniment încă.</p>
                <?php endif; ?>
                <?php if ($is_org_admin): ?>
                    <a href="assign_roles.php?event_id=<?php echo $event_id; ?>" class="btn btn-warning btn-sm mt-2" style="max-width: 150px;">Gestionează Roluri</a>
                <?php endif; ?>
            </div>

            <?php if ($is_event_admin_or_owner): ?>
                <div class="my-evet-card text-light p-4 shadow mb-4">
                    <h3>Gestionarea Sarcinilor Membrilor</h3>
                    <?php if (!empty($event_participants_with_roles)): ?>
                        <form method="POST" id="manageTasksForm">
                            <input type="hidden" name="save_tasks" value="true">
                            <?= CsrfToken::csrfField() ?>
                            <table class="table table-striped table-dark">
                                <thead>
                                    <tr>
                                        <th>Nume</th>
                                        <th>Rol în Eveniment</th>
                                        <th>Sarcina Specifică</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event_participants_with_roles as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(
                                                $member->userName . ' ' . $member->userPrenume
                                            ); ?></td>
                                            <td><?php echo htmlspecialchars($member->role); ?></td>
                                            <td>
                                                <textarea class="form-control" name="task_<?php echo $member->userId; ?>" rows="2"><?php echo htmlspecialchars(
    $tasks_by_user[$member->userId] ?? ''
); ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="submit" class="btn btn-primary mt-2">Salvează Sarcinile</button>
                        </form>
                    <?php else: ?>
                        <p>Adaugă participanți și rolurile lor înainte de a asigna sarcini.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="my-evet-card text-light p-4 shadow mb-4">
                    <h3>Sarcinile Mele pentru Acest Eveniment</h3>
                    <?php
                    $my_task = $tasks_by_user[$user_id] ?? '';
                    if (!empty($my_task)): ?>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($my_task)); ?>
                        </div>
                    <?php else: ?>
                        <p>Nu ți-a fost asignată nicio sarcină specifică pentru acest eveniment.</p>
                    <?php endif;
                    ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const manageTasksForm = document.getElementById('manageTasksForm');
        if (manageTasksForm) {
            manageTasksForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(manageTasksForm);

                fetch('', { 
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succes!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Eroare!',
                            text: data.message || 'A apărut o eroare la salvarea sarcinilor.',
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Eroare!',
                        text: 'A apărut o eroare neașteptată la salvarea sarcinilor.',
                    });
                });
            });
        }

        const deleteBtn = document.getElementById('deleteEventBtn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                const csrfToken = document.querySelector('#deleteEventForm input[name="csrf_token"]');

                Swal.fire({
                    title: 'Ești sigur?',
                    text: "Vrei să ștergi acest eveniment? Acțiunea este ireversibilă.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Da, șterge!',
                    cancelButtonText: 'Anulează'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('delete_event', 'true');
                        if (csrfToken) {
                           formData.append('csrf_token', csrfToken.value);
                        } else {
                            Swal.fire('Eroare!', 'CSRF Token not found. Please refresh the page.', 'error');
                            return;
                        }

                        fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Șters!', data.message, 'success').then(() => {
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    }
                                });
                            } else {
                                Swal.fire('Eroare!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Eroare!', 'A apărut o eroare neașteptată.', 'error');
                        });
                    }
                });
            });
        }
    });
    </script>
</body>
</html>