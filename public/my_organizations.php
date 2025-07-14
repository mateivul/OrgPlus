<?php
require_once __DIR__ . '/../src/config.php';

$user_id = ensure_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_organization') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $website = trim($_POST['website']);
    $owner_id = $user_id;

    header('Content-Type: application/json');

    if (empty($name) || empty($description) || empty($email) || empty($phone) || empty($address) || empty($website)) {
        echo json_encode(['error' => true, 'message' => 'Toate câmpurile sunt obligatorii.']);
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => true, 'message' => 'Email invalid.']);
        exit();
    }

    $stmt_check = $pdo->prepare('SELECT id FROM organizations WHERE email = ?');
    $stmt_check->execute([$email]);
    if ($stmt_check->fetchColumn()) {
        echo json_encode(['error' => true, 'message' => 'Acest email este deja folosit de o organizație.']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO organizations (name, description, email, phone, address, website, owner_id, created_at, updated_at, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'active')");
    if ($stmt->execute([$name, $description, $email, $phone, $address, $website, $owner_id])) {
        $org_id = $pdo->lastInsertId();
        $stmt_role = $pdo->prepare(
            "INSERT INTO roles (user_id, org_id, role, join_date) VALUES (?, ?, 'owner', NOW())"
        );
        $stmt_role->execute([$owner_id, $org_id]);
        echo json_encode(['success' => true, 'message' => 'Organizația a fost creată cu succes!']);
        exit();
    } else {
        echo json_encode(['error' => true, 'message' => 'Eroare la crearea organizației. Încercați din nou.']);
        exit();
    }
}

$sql = "SELECT o.id, o.name, r.role
        FROM roles r
        JOIN organizations o ON r.org_id = o.id
        WHERE r.user_id = ?";
$stmt_orgs = $pdo->prepare($sql);
$stmt_orgs->execute([$user_id]);
$all_organizations = $stmt_orgs->fetchAll(PDO::FETCH_ASSOC);

$owned_organizations = [];
$member_organizations = [];
foreach ($all_organizations as $org) {
    if ($org['role'] === 'owner') {
        $owned_organizations[] = $org;
    } else {
        $member_organizations[] = $org;
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/../includes/global.html'; ?>
    <title>Organizațiile Mele</title>
</head>
<body>
    <div class="d-flex">
        <?php
        $authService = getService('AuthService');
        include __DIR__ . '/../includes/sidebar.php';
        ?>
        <div class="content p-4 w-100">
            <h2 class="mb-4">Organizațiile Mele</h2>

            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createOrgModal">Crează o organizație nouă</button>

            <div class="modal fade" id="createOrgModal" tabindex="-1" aria-labelledby="createOrgModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createOrgModalLabel">Crează o organizație nouă</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="createOrgForm" method="POST" action="">
                                <input type="hidden" name="action" value="create_organization">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nume Organizație</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                 <div class="mb-3">
                                    <label for="description" class="form-label">Descriere</label>
                                    <input type="text" class="form-control" id="description" name="description" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefon</label>
                                    <input type="text" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresă</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                <div class="mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                                    <button type="submit" class="btn btn-primary">Crează Organizație</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($owned_organizations)): ?>
                <h3 class="mt-4">Organizații pe care le deții</h3>
                <div class="list-group">
                    <?php foreach ($owned_organizations as $org): ?>
                        <a href="select_org.php?org_id=<?php echo $org[
                            'id'
                        ]; ?>" class="list-group-item list-group-item-action">
                            <strong><?php echo htmlspecialchars($org['name']); ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($member_organizations)): ?>
                <h3 class="mt-4">Organizații în care ești membru</h3>
                <div class="list-group">
                    <?php foreach ($member_organizations as $org): ?>
                        <a href="select_org.php?org_id=<?php echo $org[
                            'id'
                        ]; ?>" class="list-group-item list-group-item-action">
                             <strong><?php echo htmlspecialchars($org['name']); ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($owned_organizations) && empty($member_organizations)): ?>
                <p class="lead mt-4">Nu ești membru în nicio organizație.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const createOrgForm = document.getElementById('createOrgForm');

            if (createOrgForm) {
                createOrgForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'create_organization');

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            Swal.fire({
                                title: 'Eroare!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else if (data.success) {
                            Swal.fire({
                                title: 'Succes!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Eroare!',
                            text: 'A apărut o eroare neașteptată: ' + error,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                });
            }
        });
    </script>
</body>
</html>