<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$personal_requests = [];

$allowed_filters = ['all', 'pending', 'accepted', 'rejected'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters) ? $_GET['filter'] : 'all';

$sql_personal_requests = "SELECT r.*, s.name AS sender_name, s.prenume AS sender_prenume,
                        o.name AS organization_name, e.name AS event_name, r.status AS status
                        FROM requests r
                        LEFT JOIN users s ON r.sender_user_id = s.id
                        LEFT JOIN organizations o ON r.organization_id = o.id
                        LEFT JOIN events e ON r.event_id = e.id
                        WHERE r.receiver_user_id = ?
                        AND r.request_type IN ('event_invitation', 'organization_invite_manual', 'organization_join_request_response')";

if ($filter !== 'all') {
    $sql_personal_requests .= ' AND r.status = ?';
}

$sql_personal_requests .= ' ORDER BY r.created_at DESC';

$stmt_personal_requests = $mysqli->prepare($sql_personal_requests);

if ($filter === 'all') {
    $stmt_personal_requests->bind_param('i', $user_id);
} else {
    $stmt_personal_requests->bind_param('is', $user_id, $filter);
}

$stmt_personal_requests->execute();
$result_personal_requests = $stmt_personal_requests->get_result();
$personal_requests = $result_personal_requests->fetch_all(MYSQLI_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && in_array($_POST['action'], ['accept', 'reject']) && isset($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];

        $mysqli->begin_transaction();

        try {
            $sql_check_request = 'SELECT * FROM requests WHERE request_id = ? FOR UPDATE';
            $stmt_check_request = $mysqli->prepare($sql_check_request);
            $stmt_check_request->bind_param('i', $request_id);
            $stmt_check_request->execute();
            $result_check_request = $stmt_check_request->get_result()->fetch_assoc();

            if ($result_check_request && $result_check_request['status'] === 'pending') {
                $request_type = $result_check_request['request_type'];
                $receiver_user_id = $result_check_request['receiver_user_id'];
                $organization_id = $result_check_request['organization_id'];
                $original_sender_user_id = $result_check_request['sender_user_id'];
                $event_id = $result_check_request['event_id'] ?? null;

                $new_status = $action === 'accept' ? 'accepted' : 'rejected';
                $sql_update_request = 'UPDATE requests SET status = ? WHERE request_id = ?';
                $stmt_update_request = $mysqli->prepare($sql_update_request);
                $stmt_update_request->bind_param('si', $new_status, $request_id);
                $stmt_update_request->execute();

                if ($action === 'accept') {
                    if ($request_type === 'event_invitation' && $event_id) {
                        $sql_check_participant = 'SELECT user_id FROM event_roles WHERE event_id = ? AND user_id = ?';
                        $stmt_check_participant = $mysqli->prepare($sql_check_participant);
                        $stmt_check_participant->bind_param('ii', $event_id, $receiver_user_id);
                        $stmt_check_participant->execute();
                        if ($stmt_check_participant->get_result()->num_rows === 0) {
                            $sql_add_participant =
                                "INSERT INTO event_roles (event_id, user_id, role) VALUES (?, ?, 'participant')";
                            $stmt_add_participant = $mysqli->prepare($sql_add_participant);
                            $stmt_add_participant->bind_param('ii', $event_id, $receiver_user_id);
                            $stmt_add_participant->execute();
                        }
                    } elseif ($request_type === 'organization_invite_manual' && $organization_id) {
                        $sql_check_member = 'SELECT user_id FROM roles WHERE org_id = ? AND user_id = ?';
                        $stmt_check_member = $mysqli->prepare($sql_check_member);
                        $stmt_check_member->bind_param('ii', $organization_id, $receiver_user_id);
                        $stmt_check_member->execute();
                        if ($stmt_check_member->get_result()->num_rows === 0) {
                            $sql_add_member = "INSERT INTO roles (org_id, user_id, role) VALUES (?, ?, 'member')";
                            $stmt_add_member = $mysqli->prepare($sql_add_member);
                            $stmt_add_member->bind_param('ii', $organization_id, $receiver_user_id);
                            $stmt_add_member->execute();
                        }
                    }
                }

                $mysqli->commit();

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'action' => $action]);
                exit();
            } else {
                $mysqli->rollback();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Cererea a fost deja procesată sau nu există']);
                exit();
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Eroare la procesare: ' . $e->getMessage()]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Mesaje Personale</title>
    <?php require 'utils/global.html'; ?>
</head>
<body class="text-light">
    <div class="d-flex">
        <?php include 'utils/sidebar.php'; ?>

        <div class="my-content p-4">
            <div class="card bg-dark text-white mb-4 p-4 shadow">
                <h2>Mesaje Personale</h2>
                
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <a href="?filter=all" class="btn btn-outline-light <?php echo !isset($_GET['filter']) ||
                        $_GET['filter'] === 'all'
                            ? 'active'
                            : ''; ?>">Toate</a>
                        <a href="?filter=pending" class="btn btn-outline-warning <?php echo isset($_GET['filter']) &&
                        $_GET['filter'] === 'pending'
                            ? 'active'
                            : ''; ?>">În așteptare</a>
                        <a href="?filter=accepted" class="btn btn-outline-success <?php echo isset($_GET['filter']) &&
                        $_GET['filter'] === 'accepted'
                            ? 'active'
                            : ''; ?>">Acceptate</a>
                        <a href="?filter=rejected" class="btn btn-outline-danger <?php echo isset($_GET['filter']) &&
                        $_GET['filter'] === 'rejected'
                            ? 'active'
                            : ''; ?>">Respinse</a>
                    </div>
                </div>
                
                <?php if (empty($personal_requests)): ?>
                    <p>Nu aveți solicitări.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($personal_requests as $request): ?>
                            <li class="list-group-item <?php echo 'bg-' . $request['status']; ?> text-light mb-2">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <?php if ($request['request_type'] === 'event_invitation'): ?>
                                            Invitație la evenimentul <strong><?php echo htmlspecialchars(
                                                $request['event_name'] ?? ''
                                            ); ?></strong>
                                            de la <?php echo htmlspecialchars(
                                                $request['sender_name'] . ' ' . $request['sender_prenume']
                                            ); ?>.
                                        <?php elseif ($request['request_type'] === 'organization_invite_manual'): ?>
                                            Invitație de a te alătura organizației <strong><?php echo htmlspecialchars(
                                                $request['organization_name']
                                            ); ?></strong>
                                            de la <?php echo htmlspecialchars(
                                                $request['sender_name'] . ' ' . $request['sender_prenume']
                                            ); ?>.
                                        <?php elseif (
                                            $request['request_type'] === 'organization_join_request_response'
                                        ): ?>
                                            Cererea ta de a te alătura organizației <strong><?php echo htmlspecialchars(
                                                $request['organization_name']
                                            ); ?></strong> a fost
                                            <span class="badge bg-<?php echo $request['status'] === 'accepted'
                                                ? 'success'
                                                : 'danger'; ?>">
                                                <?php echo $request['status'] === 'accepted'
                                                    ? 'acceptată'
                                                    : 'respinsă'; ?>
                                            </span>.
                                        <?php endif; ?>
                                    </div>
                                    <small ><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></small>
                                </div>
                                <div class="mt-2">
                                    <?php if (
                                        in_array($request['request_type'], [
                                            'event_invitation',
                                            'organization_invite_manual',
                                        ]) &&
                                        $request['status'] === 'pending'
                                    ): ?>
                                        <form method="post" class="d-inline form-process">
                                            <input type="hidden" name="action" value="accept">
                                            <input type="hidden" name="request_id" value="<?php echo $request[
                                                'request_id'
                                            ]; ?>">
                                            <button type="submit" class="btn btn-success btn-sm btn-process">Acceptă</button>
                                        </form>
                                        <form method="post" class="d-inline ms-2 form-process">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="request_id" value="<?php echo $request[
                                                'request_id'
                                            ]; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm btn-process">Refuză</button>
                                        </form>
                                    <?php endif; ?>
                                    <span class="ms-3 badge bg-<?php echo $request['status'] === 'accepted'
                                        ? 'success'
                                        : ($request['status'] === 'rejected'
                                            ? 'danger'
                                            : 'warning'); ?>">
                                        <?php echo $request['status'] === 'accepted'
                                            ? 'Acceptată'
                                            : ($request['status'] === 'rejected'
                                                ? 'Refuzată'
                                                : 'În așteptare'); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.form-process').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const action = formData.get('action');
                const submitButton = this.querySelector('button[type="submit"]');
                const allButtons = document.querySelectorAll('.btn-process');
                
                allButtons.forEach(btn => {
                    btn.disabled = true;
                    if (btn === submitButton) {
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesare...';
                    }
                });
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Succes!',
                            text: action === 'accept' ? 'Solicitare acceptată cu succes!' : 'Solicitare respinsă cu succes.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        window.location.reload();
                    } else {
                        await Swal.fire({
                            icon: 'warning',
                            title: 'Atenție',
                            text: data.message || 'Cererea a fost deja procesată',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Eroare',
                        text: 'A apărut o eroare la procesare'
                    });
                    allButtons.forEach(btn => {
                        btn.disabled = false;
                        btn.innerHTML = btn.textContent;
                    });
                }
            });
        });
    });
    </script>
</body>
</html>