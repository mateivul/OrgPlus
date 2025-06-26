<?php

require_once __DIR__ . '/../src/config.php';

// 2. Include fișierul cu funcții ajutătoare (ex: funcții de redirect, de verificare a sesiunii)
require_once __DIR__ . '/../utils/app_helpers.php';

// 3. Extrage serviciile și repository-urile necesare din container
$authService = getService('AuthService');
$eventService = getService('EventService');
$roleRepository = getService('RoleRepository'); // Pentru user_org_role și isUserAdminOrOwner
$userRepository = getService('UserRepository'); // Pentru a extrage detalii despre utilizatori
$organizationRepository = getService('OrganizationRepository'); // Pentru a extrage detalii despre organizație

// 4. Aplică logica de autentificare și autorizare
$user_id = ensure_logged_in(); // user_id este returnat de această funcție
ensure_existing_org(); // Asigură-te că o organizație este selectată în sesiune

// Preluați ID-ul evenimentului din URL
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id <= 0) {
    header('Location: dashboard.php'); // Redirecționează dacă ID-ul evenimentului e invalid
    exit();
}

// Obține detaliile evenimentului folosind EventService
$event = $eventService->getEventDetails($event_id); // Am redenumit metoda pentru claritate, dar ar trebui să corespundă cu ce ai în Service

if (!$event) {
    // Dacă evenimentul nu există, arată o eroare sau redirecționează
    die('Evenimentul nu există sau nu aveți permisiunea să-l vizualizați.');
    // Sau header('Location: events.php'); exit();
}

// Asigură-te că evenimentul aparține organizației curente din sesiune
$current_org_id = $_SESSION['org_id'];
if ($event->orgId !== $current_org_id) {
    // Dacă evenimentul nu aparține organizației curente, redirecționează
    header('Location: events.php'); // Sau o pagină de eroare/acces interzis
    exit();
}

// Obține rolul utilizatorului în organizația evenimentului
$user_role_in_org = $roleRepository->getUserRoleInOrganization($user_id, $current_org_id);
$is_org_admin = in_array($user_role_in_org, ['admin', 'owner']);

// Verifică dacă utilizatorul curent este admin sau proprietar al organizației evenimentului
$organization = $organizationRepository->findById($current_org_id);
$is_event_admin_or_owner = $is_org_admin || ($organization && $user_id === $organization->ownerId);

// Procesare cerere POST pentru salvarea sarcinilor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_event_admin_or_owner && isset($_POST['save_tasks'])) {
    $response = ['success' => false, 'message' => 'A apărut o eroare neașteptată.'];

    try {
        // Logica de salvare a sarcinilor este acum în EventService
        // Transmiterea directă a $_POST necesită validare suplimentară în EventService
        $eventService->saveEventTasks($event_id, $user_id, $_POST);
        $response = ['success' => true, 'message' => 'Sarcinile au fost salvate cu succes!'];
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
        error_log("Task save error for event $event_id: " . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Preluați participanții și rolurile lor în eveniment
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$event_participants_with_roles = $eventService->getEventParticipantsWithRoles($event_id, $search_term);

// Preluați sarcinile existente pentru eveniment
// Această structură `$tasks_by_user[$member->getUserId()]` necesită ca `getEventTasks`
// să returneze un array asociativ unde cheia este `user_id`.
// Metoda `getTasksForEvent` din EventRepository returnează un array de obiecte EventTask.
// Va trebui să transformi acest array în formatul necesar pentru template.
$raw_event_tasks = $eventService->getEventTasks($event_id);
$tasks_by_user = [];
foreach ($raw_event_tasks as $task) {
    $tasks_by_user[$task->userId] = $task->taskDescription;
}

// Include antetul (header) și bara laterală (sidebar)
// ACUM INCLUDEM FIȘIERELE HTML ÎN MOD CORECT, ÎN INTERIORUL BLOCULUI PHP
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Vizualizare Eveniment - <?php echo htmlspecialchars($event->name); ?></title>
    <?php
// Calea corectă pentru _header.php (dacă este un fișier PHP)
?>
    <link rel="stylesheet" href="styles/event-style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="d-flex">
        <?php // Calea corectă pentru sidebar.php

require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="content p-4 w-100">
            <div class="my-evet-card text-white mb-4 p-4 shadow">
                <h2><?php echo htmlspecialchars($event->name); ?></h2>
                <p><strong>Organizație:</strong> <a class="participant-link" href="dashboard.php"><?php echo htmlspecialchars(
                    $event->orgName // Aici folosim proprietatea dinamică adăugată în Service
                ); ?></a></p>
                <p><strong>Descriere:</strong> <?php echo nl2br(htmlspecialchars($event->description)); ?></p>
                <p><strong>Data:</strong> <?php echo date('d-m-Y H:i', strtotime($event->date)); ?></p>
                <p><a href="events.php" class="btn btn-sm btn-outline-light mt-2">&laquo; Înapoi la Evenimente</a></p>
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
                <?php if (
                    $is_org_admin
                ):// Verificăm rolul de admin în organizație pentru a gestiona rolurile în eveniment
                     ?>
                    <a href="assign_roles.php?event_id=<?php echo $event_id; ?>" class="btn btn-warning btn-sm mt-2" style="max-width: 150px;">Gestionează Roluri</a>
                <?php endif; ?>
            </div>

            <?php if ($is_event_admin_or_owner): ?>
                <div class="my-evet-card text-light p-4 shadow mb-4">
                    <h3>Gestionarea Sarcinilor Membrilor</h3>
                    <?php if (!empty($event_participants_with_roles)): ?>
                        <form method="POST" id="manageTasksForm">
                            <input type="hidden" name="save_tasks" value="true">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const manageTasksForm = document.getElementById('manageTasksForm');
        if (manageTasksForm) {
            manageTasksForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(manageTasksForm);

                fetch('', { // Trimite la aceeași pagină (view_event.php)
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
                        // Reîncărcarea paginii după salvare, dacă e necesar
                        // setTimeout(() => window.location.reload(), 1500);
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
    });
    </script>
</body>
</html>