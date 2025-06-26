<?php
require_once __DIR__ . '/../src/config.php';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$roleRepository = getService('RoleRepository');
$user_role = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    die('Permisiuni insuficiente');
}

$organizationRepository = getService('OrganizationRepository');
$organization = $organizationRepository->findById($org_id);

$org_name = $organization ? $organization->name : 'Organizație necunoscută';

// Use PDO from a repository (e.g. WorkedHoursRepository)
$workedHoursRepository = getService('WorkedHoursRepository');
$pdo = $workedHoursRepository->getPdo();

// Filtering logic
$allowed_filters = ['all', 'pending', 'accepted', 'rejected'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters) ? $_GET['filter'] : 'all';

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
        // Only admins/owners of this org can accept/reject
        if ($request['organization_id'] != $org_id) {
            throw new Exception('Nu ai permisiunea să procesezi această solicitare.');
        }

        $new_status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE request_id = ?');
        $stmt->execute([$new_status, $request_id]);

        // If accepted, add user as member to roles if not already
        if ($action === 'accept') {
            $sender_user_id = $request['sender_user_id'];
            $stmt = $pdo->prepare('SELECT 1 FROM roles WHERE org_id = ? AND user_id = ?');
            $stmt->execute([$org_id, $sender_user_id]);
            if (!$stmt->fetchColumn()) {
                $stmt = $pdo->prepare(
                    "INSERT INTO roles (org_id, user_id, role, join_date) VALUES (?, ?, 'member', NOW())"
                );
                $stmt->execute([$org_id, $sender_user_id]);
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

// Build SQL for join requests
$sql = "SELECT r.*, u.name AS sender_name, u.prenume AS sender_prenume
        FROM requests r
        JOIN users u ON r.sender_user_id = u.id
        WHERE r.organization_id = :org_id
        AND r.request_type = 'organization_join_request'";
$params = ['org_id' => $org_id];

if ($filter !== 'all') {
    $sql .= ' AND r.status = :status';
    $params['status'] = $filter;
}
$sql .= ' ORDER BY r.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$organization_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/global.html'; ?>
    <title>Inbox organizație - <?php echo htmlspecialchars($org_name); ?></title>
</head>
<body>
<div class="d-flex">
    <?php
    $authService = getService('AuthService');
    include_once __DIR__ . '/../includes/sidebar.php';
    ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Inbox - <?php echo htmlspecialchars($org_name); ?></h2>
            <a href="dashboard.php" class="btn btn-secondary">Înapoi</a>
        </div>

        <div class="card bg-dark">
            <div class="card-header">
                <h5 class="mb-0">Cereri de aderare la organizație</h5>
            </div>
            <div class="card-body">
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
                <?php if (empty($organization_requests)): ?>
                    <p>Nu există cereri de aderare pentru această organizație.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($organization_requests as $request): ?>
                            <li class="list-group-item bg-dark text-light mb-2">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        Utilizatorul <strong>
                                            <?php
                                            $prenume = isset($request['prenume'])
                                                ? $request['prenume']
                                                : (isset($request['sender_prenume'])
                                                    ? $request['sender_prenume']
                                                    : '');
                                            echo htmlspecialchars($request['sender_name'] . ' ' . $prenume);
                                            ?>
                                        </strong>
                                        dorește să se alăture organizației.
                                    </div>
                                    <small><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></small>
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