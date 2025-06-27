<?php
require_once __DIR__ . '/../src/config.php';

$user_id = ensure_logged_in();

// Use PDO from a repository (e.g. WorkedHoursRepository)
$workedHoursRepository = getService('WorkedHoursRepository');
$pdo = $workedHoursRepository->getPdo();

// Filtering logic
$allowed_filters = ['all', 'pending', 'accepted', 'rejected'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters) ? $_GET['filter'] : 'all';

// Only show the request types you want, and map them to your new logic
$allowed_types = ['event_invitation', 'organization_invite_manual', 'organization_join_request_response'];

$placeholders = implode(',', array_fill(0, count($allowed_types), '?'));

$sql = "
    SELECT 
        r.request_id,
        r.request_type,
        r.status,
        r.created_at,
        r.organization_id,
        o.name AS organization_name,
        r.event_id,
        e.name AS event_name,
        e.date AS event_date,
        r.sender_user_id,
        s.name AS sender_name,
        s.prenume AS sender_prenume
    FROM requests r
    LEFT JOIN users s ON r.sender_user_id = s.id
    LEFT JOIN organizations o ON r.organization_id = o.id
    LEFT JOIN events e ON r.event_id = e.id
    WHERE r.receiver_user_id = ?
      AND r.request_type IN ($placeholders)
";
$params = [$user_id, ...$allowed_types];

if ($filter !== 'all') {
    $sql .= ' AND r.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$personal_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST actions for accept/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $response = ['success' => false, 'error' => false, 'warning' => false, 'message' => ''];
    header('Content-Type: application/json');
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    try {
        // Fetch the request and lock it for update
        $stmt = $pdo->prepare('SELECT * FROM requests WHERE request_id = ? FOR UPDATE');
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception('Solicitarea nu există.');
        }
        if ($request['status'] !== 'pending') {
            throw new Exception('Solicitarea a fost deja procesată.');
        }
        // Only the receiver can accept/reject
        if ($request['receiver_user_id'] != $user_id) {
            throw new Exception('Nu ai permisiunea să procesezi această solicitare.');
        }

        $new_status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE request_id = ?');
        $stmt->execute([$new_status, $request_id]);

        // If accepted, add user to event/org if needed
        if ($action === 'accept') {
            if ($request['request_type'] === 'event_invitation' && $request['event_id']) {
                // Add user as participant to event_roles if not already
                $stmt = $pdo->prepare('SELECT 1 FROM event_roles WHERE event_id = ? AND user_id = ?');
                $stmt->execute([$request['event_id'], $user_id]);
                if (!$stmt->fetchColumn()) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO event_roles (event_id, user_id, role) VALUES (?, ?, 'participant')"
                    );
                    $stmt->execute([$request['event_id'], $user_id]);
                }
            } elseif ($request['request_type'] === 'organization_invite_manual' && $request['organization_id']) {
                // Add user as member to roles if not already
                $stmt = $pdo->prepare('SELECT 1 FROM roles WHERE org_id = ? AND user_id = ?');
                $stmt->execute([$request['organization_id'], $user_id]);
                if (!$stmt->fetchColumn()) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO roles (org_id, user_id, role, join_date) VALUES (?, ?, 'member', NOW())"
                    );
                    $stmt->execute([$request['organization_id'], $user_id]);
                }
            }
        }

        $response['success'] = true;
        $response['message'] =
            $action === 'accept' ? 'Solicitarea a fost acceptată cu succes!' : 'Solicitarea a fost respinsă cu succes!';
    } catch (Exception $e) {
        $response['error'] = true;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/global.html'; ?>
    <title>Mesaje Personale</title>
</head>
<body class="text-light">
    <div class="d-flex">
        <?php
        $authService = getService('AuthService');
        include __DIR__ . '/../includes/sidebar.php';
        ?>
        <div class="my-content p-4">
            <div class="card bg-dark text-white mb-4 p-4 shadow">
                <h2>Mesaje Personale</h2>
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <a href="?filter=all" class="btn btn-outline-light <?= $filter === 'all'
                            ? 'active'
                            : '' ?>">Toate</a>
                        <a href="?filter=pending" class="btn btn-outline-warning <?= $filter === 'pending'
                            ? 'active'
                            : '' ?>">În așteptare</a>
                        <a href="?filter=accepted" class="btn btn-outline-success <?= $filter === 'accepted'
                            ? 'active'
                            : '' ?>">Acceptate</a>
                        <a href="?filter=rejected" class="btn btn-outline-danger <?= $filter === 'rejected'
                            ? 'active'
                            : '' ?>">Respinse</a>
                    </div>
                </div>
                <?php if (empty($personal_requests)): ?>
                    <p>Nu aveți solicitări.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($personal_requests as $request): ?>
                            <?php
                            $is_expired = false;
                            $now = new DateTime();
                            $request_status = $request['status'];

                            if ($request_status === 'pending') {
                                if ($request['request_type'] === 'event_invitation' && !empty($request['event_date'])) {
                                    $event_date = new DateTime($request['event_date']);
                                    $expiration_date = (clone $event_date)->modify('-2 days');
                                    if ($now > $expiration_date) {
                                        $is_expired = true;
                                        $request_status = 'expired';
                                    }
                                } elseif ($request['request_type'] === 'organization_invite_manual') {
                                    $created_at = new DateTime($request['created_at']);
                                    if ($created_at->diff($now)->days > 7) {
                                        $is_expired = true;
                                        $request_status = 'expired';
                                    }
                                }
                            }
                            $status_map = [
                                'pending' => ['label' => 'În așteptare', 'class' => 'warning'],
                                'accepted' => ['label' => 'Acceptată', 'class' => 'success'],
                                'rejected' => ['label' => 'Refuzată', 'class' => 'danger'],
                                'expired' => ['label' => 'Expirată', 'class' => 'secondary'],
                            ];
                            $status_info = $status_map[$request_status] ?? ['label' => 'Necunoscut', 'class' => 'dark'];
                            ?>

                            <li class="list-group-item bg-dark text-light mb-2 shadow-sm">
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
                                    <small><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></small>
                                </div>
                                <div class="mt-2">
                                    <?php if ($request['status'] === 'pending' && !$is_expired): ?>
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
                                    <span class="ms-3 badge bg-<?php echo $status_info['class']; ?>">
                                        <?php echo $status_info['label']; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
