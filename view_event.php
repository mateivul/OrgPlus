<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$sql_event =
    'SELECT e.*, o.name AS org_name, o.owner_id, o.id as org_id FROM events e JOIN organizations o ON e.org_id = o.id WHERE e.id = ?';
$stmt_event = $mysqli->prepare($sql_event);
if ($stmt_event === false) {
    die('Eroare la pregătirea interogării pentru eveniment: ' . $mysqli->error);
}

$stmt_event->bind_param('i', $event_id);
$stmt_event->execute();
$result_event = $stmt_event->get_result();
$event = $result_event->fetch_assoc();
$stmt_event->close();

if (!$event) {
    die('Evenimentul nu există sau nu aveți permisiunea să-l vizualizați.');
}

$org_id = $event['org_id'];
$org_owner_id = $event['owner_id'];

$user_role = user_org_role($user_id, $org_id);

$is_admin = in_array($user_role, ['admin', 'owner']);

$is_event_admin = $is_admin || $user_id == $org_owner_id;

$search_term = isset($_GET['search']) ? '%' . $mysqli->real_escape_string($_GET['search']) . '%' : '%%';

$sql_roles = "SELECT DISTINCT er.user_id, u.name, u.prenume, er.role
              FROM event_roles er
              JOIN users u ON er.user_id = u.id
              WHERE er.event_id = ? AND (u.name LIKE ? OR u.prenume LIKE ?)";
$stmt_roles = $mysqli->prepare($sql_roles);
if ($stmt_roles) {
    $stmt_roles->bind_param('iss', $event_id, $search_term, $search_term);
    $stmt_roles->execute();
    $result_roles = $stmt_roles->get_result();
    $roles = $result_roles->fetch_all(MYSQLI_ASSOC);
    $stmt_roles->close();
} else {
    die('Eroare la pregătirea interogării pentru roluri: ' . $mysqli->error);
}

$unique_roles = [];
foreach ($roles as $role) {
    if (!isset($unique_roles[$role['user_id']])) {
        $unique_roles[$role['user_id']] = $role;
    }
}

$sql_existing_tasks = 'SELECT user_id, task_description FROM event_tasks WHERE event_id = ?';
$stmt_existing_tasks = $mysqli->prepare($sql_existing_tasks);
$tasks_by_user = [];
if ($stmt_existing_tasks) {
    $stmt_existing_tasks->bind_param('i', $event_id);
    $stmt_existing_tasks->execute();
    $result_existing_tasks = $stmt_existing_tasks->get_result();
    while ($task = $result_existing_tasks->fetch_assoc()) {
        $tasks_by_user[$task['user_id']] = $task['task_description'];
    }
    $stmt_existing_tasks->close();
} else {
    die('Eroare la pregătirea interogării pentru sarcini: ' . $mysqli->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_event_admin && isset($_POST['save_tasks'])) {
    $response = ['success' => false, 'message' => 'A apărut o eroare neașteptată.'];
    $all_updates_successful = true;

    $mysqli->begin_transaction();

    try {
        foreach ($unique_roles as $member) {
            $user_id_member = $member['user_id'];
            $task_description = $_POST['task_' . $user_id_member] ?? '';

            if (!empty($task_description)) {
                $sql_replace_task =
                    'REPLACE INTO event_tasks (event_id, user_id, task_description, assigned_by_user_id) VALUES (?, ?, ?, ?)';
                $stmt_replace_task = $mysqli->prepare($sql_replace_task);
                if ($stmt_replace_task) {
                    $stmt_replace_task->bind_param('iisi', $event_id, $user_id_member, $task_description, $user_id);
                    if (!$stmt_replace_task->execute()) {
                        throw new Exception(
                            'Eroare la salvarea sarcinii pentru utilizatorul ID ' .
                                $user_id_member .
                                ': ' .
                                $stmt_replace_task->error
                        );
                    }
                    $stmt_replace_task->close();
                } else {
                    throw new Exception('Eroare la pregătirea salvării sarcinii: ' . $mysqli->error);
                }
            } else {
                $sql_delete_task = 'DELETE FROM event_tasks WHERE event_id = ? AND user_id = ?';
                $stmt_delete_task = $mysqli->prepare($sql_delete_task);
                if ($stmt_delete_task) {
                    $stmt_delete_task->bind_param('ii', $event_id, $user_id_member);
                    if (!$stmt_delete_task->execute()) {
                        throw new Exception(
                            'Eroare la ștergerea sarcinii pentru utilizatorul ID ' .
                                $user_id_member .
                                ': ' .
                                $stmt_delete_task->error
                        );
                    }
                    $stmt_delete_task->close();
                } else {
                    throw new Exception('Eroare la pregătirea ștergerii sarcinii: ' . $mysqli->error);
                }
            }
        }
        $mysqli->commit();
        $response = ['success' => true, 'message' => 'Sarcinile au fost salvate cu succes!'];
    } catch (Exception $e) {
        $mysqli->rollback();
        $response = ['success' => false, 'message' => $e->getMessage()];
        error_log("Task save error for event $event_id: " . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Vizualizare Eveniment - <?php echo htmlspecialchars($event['name']); ?></title>
    <?php require 'utils/global.html'; ?>
    <link rel="stylesheet" href="styles/event-style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'utils/sidebar.php'; ?>

        <div class="content p-4 w-100">
            <div class="my-evet-card text-white mb-4 p-4 shadow">
                <h2><?php echo htmlspecialchars($event['name']); ?></h2>
                <p><strong>Organizație:</strong> <a class="participant-link" href="dashboard.php"><?php echo htmlspecialchars(
                    $event['org_name']
                ); ?></a></p>
                <p><strong>Descriere:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <p><strong>Data:</strong> <?php echo date('d-m-Y H:i', strtotime($event['date'])); ?></p>
                <p><a href="events.php" class="btn btn-sm btn-outline-light mt-2">&laquo; Înapoi la Evenimente</a></p>
            </div>

            <div class="my-evet-card text-light p-3 mb-4 shadow-sm">
                <h4 class="mb-3">Participanți și Roluri</h4>
                <?php if (!empty($unique_roles)): ?>
                 <ul class="list-unstyled">
                     <?php foreach ($unique_roles as $participant): ?>
                         <li>
                             <strong>
                                 <a class="participant-link" href="public_profile.php?user_id=<?php echo $participant[
                                     'user_id'
                                 ]; ?>">
                                     <?php echo htmlspecialchars(
                                         $participant['name'] . ' ' . $participant['prenume']
                                     ); ?>
                                 </a>:
                             </strong>
                             <?php echo htmlspecialchars($participant['role']); ?>
                         </li>
                     <?php endforeach; ?>
                 </ul>
                <?php else: ?>
                 <p>Nu sunt asignați participanți pentru acest eveniment încă.</p>
                <?php endif; ?>
                 <?php if ($is_admin): ?>
                     <a href="assign_roles.php?event_id=<?php echo $event_id; ?>" class="btn btn-warning btn-sm mt-2" style="max-width: 150px;">Gestionează Roluri</a>
                 <?php endif; ?>
            </div>


            <?php if ($is_event_admin): ?>
                <div class="my-evet-card text-light p-4 shadow mb-4">
                    <h3>Gestionarea Sarcinilor Membrilor</h3>
                     <?php if (!empty($unique_roles)): ?>
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
                                     <?php foreach ($unique_roles as $member): ?>
                                         <tr>
                                             <td><?php echo htmlspecialchars(
                                                 $member['name'] . ' ' . $member['prenume']
                                             ); ?></td>
                                             <td><?php echo htmlspecialchars($member['role']); ?></td>
                                             <td>
                                                 <textarea class="form-control" name="task_<?php echo $member[
                                                     'user_id'
                                                 ]; ?>" rows="2"><?php echo htmlspecialchars(
    $tasks_by_user[$member['user_id']] ?? ''
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

            

            </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

    });
    </script>
</body>
</html>