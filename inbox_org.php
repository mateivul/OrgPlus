<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$organization_requests = [];
$is_org_admin = false;
$current_org_id = null;

if (isset($_SESSION['org_id']) && is_numeric($_SESSION['org_id'])) {
    $current_org_id = intval($_SESSION['org_id']);

    $sql_check_role = "SELECT role FROM roles WHERE user_id = ? AND org_id = ? AND role IN ('admin', 'owner')";
    $stmt_check_role = $mysqli->prepare($sql_check_role);
    $stmt_check_role->bind_param('ii', $user_id, $current_org_id);
    $stmt_check_role->execute();
    $result_check_role = $stmt_check_role->get_result();

    if ($result_check_role->num_rows > 0) {
        $is_org_admin = true;

        $allowed_filters = ['all', 'pending', 'accepted', 'rejected'];
        $filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters) ? $_GET['filter'] : 'all';

        $sql_org_requests = "SELECT r.*, u.name AS sender_name, u.prenume AS sender_prenume
                            FROM requests r
                            JOIN users u ON r.sender_user_id = u.id
                            WHERE r.organization_id = ?
                            AND r.request_type = 'organization_join_request'";

        if ($filter !== 'all') {
            $sql_org_requests .= ' AND r.status = ?';
        }

        $sql_org_requests .= ' ORDER BY r.created_at DESC';

        $stmt_org_requests = $mysqli->prepare($sql_org_requests);

        if ($stmt_org_requests === false) {
            die('Eroare la pregătirea interogării: ' . $mysqli->error);
        }

        if ($filter === 'all') {
            $stmt_org_requests->bind_param('i', $current_org_id);
        } else {
            $stmt_org_requests->bind_param('is', $current_org_id, $filter);
        }

        $stmt_org_requests->execute();
        $result_org_requests = $stmt_org_requests->get_result();
        $organization_requests = $result_org_requests->fetch_all(MYSQLI_ASSOC);
    }
}

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
                    if ($request_type === 'organization_join_request' && $organization_id && $original_sender_user_id) {
                        $sql_add_member =
                            "INSERT INTO roles (org_id, user_id, role, join_date) VALUES (?, ?, 'member', NOW())";
                        $stmt_add_member = $mysqli->prepare($sql_add_member);
                        $stmt_add_member->bind_param('ii', $organization_id, $original_sender_user_id);
                        $stmt_add_member->execute();

                        $notification_type = 'organization_join_request_response';
                        $notification_status = 'accepted';
                        $sql_notification = "INSERT INTO requests (request_type, sender_user_id, receiver_user_id, organization_id, status, created_at) 
                                           VALUES (?, ?, ?, ?, ?, NOW())";
                        $stmt_notification = $mysqli->prepare($sql_notification);
                        $stmt_notification->bind_param(
                            'siiis',
                            $notification_type,
                            $user_id,
                            $original_sender_user_id,
                            $organization_id,
                            $notification_status
                        );
                        $stmt_notification->execute();
                    }
                } elseif (
                    $action === 'reject' &&
                    $request_type === 'organization_join_request' &&
                    $organization_id &&
                    $original_sender_user_id
                ) {
                    $notification_type = 'organization_join_request_response';
                    $notification_status = 'rejected';
                    $sql_notification = "INSERT INTO requests (request_type, sender_user_id, receiver_user_id, organization_id, status, created_at) 
                                       VALUES (?, ?, ?, ?, ?, NOW())";
                    $stmt_notification = $mysqli->prepare($sql_notification);
                    $stmt_notification->bind_param(
                        'siiis',
                        $notification_type,
                        $user_id,
                        $original_sender_user_id,
                        $organization_id,
                        $notification_status
                    );
                    $stmt_notification->execute();
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
    <title>Mesajele Organizației</title>
    <?php require 'utils/global.html'; ?>
</head>
<body class="text-light">
    <div class="d-flex">
        <?php include 'utils/sidebar.php'; ?>

        <div class="my-content p-4">
            <?php if ($current_org_id && $is_org_admin): ?>
                <div class="card bg-dark text-white mb-4 p-4 shadow">
                    <h2>Mesajele Organizației (ID: <?php echo $current_org_id; ?>)</h2>
                    
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn btn-outline-light <?= !isset($_GET['filter']) ||
                            $_GET['filter'] === 'all'
                                ? 'active'
                                : '' ?>">Toate</a>
                            <a href="?filter=pending" class="btn btn-outline-warning <?= isset($_GET['filter']) &&
                            $_GET['filter'] === 'pending'
                                ? 'active'
                                : '' ?>">În așteptare</a>
                            <a href="?filter=accepted" class="btn btn-outline-success <?= isset($_GET['filter']) &&
                            $_GET['filter'] === 'accepted'
                                ? 'active'
                                : '' ?>">Acceptate</a>
                            <a href="?filter=rejected" class="btn btn-outline-danger <?= isset($_GET['filter']) &&
                            $_GET['filter'] === 'rejected'
                                ? 'active'
                                : '' ?>">Respinse</a>
                        </div>
                    </div>
                    
                    <?php if (empty($organization_requests)): ?>
                        <p>Nu există cereri de aderare pentru această organizație.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($organization_requests as $request): ?>
                                <li class="list-group-item <?php echo 'bg-' . $request['status']; ?> text-light mb-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            Utilizatorul <strong><?php echo htmlspecialchars(
                                                $request['sender_name'] . ' ' . $request['sender_prenume']
                                            ); ?></strong>
                                            dorește să se alăture organizației.
                                        </div>
                                        <small><?php echo date(
                                            'd.m.Y H:i',
                                            strtotime($request['created_at'])
                                        ); ?></small>
                                    </div>
                                    <div class="mt-2">
                                        <?php if ($request['status'] === 'pending'): ?>
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
            <?php else: ?>
                <div class="alert alert-warning">Nu aveți permisiunea de a vizualiza inbox-ul acestei organizații sau nu sunteți conectat la o organizație.</div>
            <?php endif; ?>
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