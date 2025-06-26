<?php

require_once __DIR__ . '/../src/config.php';

// Inițializăm dependențele
$pdo = Database::getConnection();
$userRepository = new UserRepository($pdo);
$organizationRepository = new OrganizationRepository($pdo);
$roleRepository = new RoleRepository($pdo);
$eventRepository = new EventRepository($pdo);
$requestRepository = new RequestRepository($pdo); // Inițializăm RequestRepository
$authService = new AuthService($userRepository);
// Aici instanțiem noul service pentru managementul membrilor
$organizationService = new OrganizationService(
    $organizationRepository,
    $userRepository,
    $roleRepository,
    $requestRepository
);

// --- Protecția paginii: Doar pentru utilizatori autentificați ---
$currentUser = $authService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit();
}

$user_id = $currentUser->id;

// Verifică dacă org_id este setată în sesiune
if (!isset($_SESSION['org_id'])) {
    header('Location: my_organizations.php?error=no_org_selected');
    exit();
}
$org_id = $_SESSION['org_id'];

// Preluăm organizația
$organization = $organizationRepository->findById($org_id);
if (!$organization) {
    header('Location: my_organizations.php?error=org_not_found');
    exit('Organizația nu a fost găsită.');
}
$org_name = $organization->name;

// Verifică rolul utilizatorului curent în organizație
$user_role_in_org = $roleRepository->getUserRoleInOrganization($user_id, $org_id);
$is_org_member = $roleRepository->isUserMemberOfOrganization($user_id, $org_id);
$is_admin = $roleRepository->isUserAdminOrOwner($user_id, $org_id); // Acum include "owner" și "admin"

// !!! CRUCIAL: Redirecționează dacă utilizatorul curent NU este membru al organizației !!!
if (!$is_org_member) {
    // Redirecționează către dashboard sau o pagină de eroare/acces interzis
    header('Location: dashboard.php?error=access_denied_members');
    exit();
}

// --- GESTIONARE CERERI POST (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['error' => false, 'success' => false, 'warning' => false, 'message' => ''];
    header('Content-Type: application/json');

    try {
        if (isset($_POST['remove_user_id']) && $is_admin) {
            $remove_user_id = intval($_POST['remove_user_id']);
            $organizationService->removeMember($org_id, $remove_user_id, $user_id); // $user_id e cel care face acțiunea
            $response['success'] = true;
            $response['message'] = 'Membru șters cu succes!';
        } elseif (isset($_POST['edit_user_id']) && isset($_POST['new_role']) && $is_admin) {
            $edit_user_id = intval($_POST['edit_user_id']);
            $new_role = $_POST['new_role'];
            $organizationService->updateMemberRole($org_id, $edit_user_id, $new_role, $user_id); // $user_id e cel care face acțiunea
            $response['success'] = true;
            $response['message'] = 'Rolul membrului a fost actualizat cu succes!';
        } elseif (isset($_POST['invite_member_email']) && $is_admin) {
            $member_email = filter_var($_POST['invite_member_email'], FILTER_SANITIZE_EMAIL);
            $member_role = $_POST['memberRole'] ?? 'member';

            if (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
                $response = [
                    'error' => true,
                    'message' => 'Adresă de email invalidă.',
                    'field' => 'memberEmail',
                ];
                echo json_encode($response);
                exit();
            }

            // Check if user already exists
            $sql_check_user = 'SELECT id FROM users WHERE email = ?';
            $stmt_check_user = $pdo->prepare($sql_check_user);
            $stmt_check_user->execute([$member_email]);
            $user_data = $stmt_check_user->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                $receiver_user_id = $user_data['id'];
                // Check if already a member
                $sql_check_member = 'SELECT 1 FROM roles WHERE user_id = ? AND org_id = ?';
                $stmt_check_member = $pdo->prepare($sql_check_member);
                $stmt_check_member->execute([$receiver_user_id, $org_id]);
                if ($stmt_check_member->fetchColumn()) {
                    $response = [
                        'warning' => true,
                        'message' => 'Utilizatorul este deja membru al organizației.',
                        'field' => 'memberEmail',
                    ];
                    echo json_encode($response);
                    exit();
                }
                // Check if there is already a pending or accepted invite/request
                $sql_check_request =
                    "SELECT 1 FROM requests WHERE request_type = 'organization_invite_manual' AND sender_user_id = ? AND receiver_user_id = ? AND organization_id = ? AND status IN ('pending', 'accepted')";
                $stmt_check_request = $pdo->prepare($sql_check_request);
                $stmt_check_request->execute([$user_id, $receiver_user_id, $org_id]);
                if ($stmt_check_request->fetchColumn()) {
                    $response = [
                        'warning' => true,
                        'message' => 'O invitație a fost deja trimisă acestui utilizator sau a fost acceptată.',
                        'field' => 'memberEmail',
                    ];
                    echo json_encode($response);
                    exit();
                }
                // Send invite
                $sql_invite =
                    "INSERT INTO requests (request_type, sender_user_id, receiver_user_id, organization_id, status) VALUES (?, ?, ?, ?, 'pending')";
                $stmt_invite = $pdo->prepare($sql_invite);
                $invite_type = 'organization_invite_manual';
                if ($stmt_invite->execute([$invite_type, $user_id, $receiver_user_id, $org_id])) {
                    $response = [
                        'success' => true,
                        'message' => 'Invitația a fost trimisă cu succes!',
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'Eroare la trimiterea invitației.',
                    ];
                }
                echo json_encode($response);
                exit();
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Nu există un utilizator cu acest email.',
                    'field' => 'memberEmail',
                ];
                echo json_encode($response);
                exit();
            }
        } else {
            // Nu se potrivește nicio acțiune POST validă
            $response['error'] = true;
            $response['message'] = 'Acțiune POST invalidă sau permisiuni insuficiente.';
        }
    } catch (Exception $e) {
        $response['error'] = true;
        $response['message'] = $e->getMessage();
        // Poți adăuga și un log al erorilor aici
        error_log('Eroare în members_org.php (POST): ' . $e->getMessage());
    }

    echo json_encode($response);
    exit();
}

// --- Preluare membri pentru afișare ---
// Vom folosi o metodă din RoleRepository care returnează o listă de membri cu detalii
$members_data = $roleRepository->getMembersWithDetailsByOrganization($org_id);

// NOUĂ METODĂ ÎN RoleRepository!
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require '../includes/global.html'; ?>
    <title>Membrii organizației <?php echo htmlspecialchars($org_name); ?></title>
</head>
<body>
<div class="d-flex">
    <?php // Variabilele pentru sidebar sunt deja definite în acest script

include '../includes/sidebar.php'; ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Membrii organizației <?php echo htmlspecialchars($org_name); ?></h2>
            <?php if ($is_admin): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">Adaugă Membru</button>
            <?php endif; ?>
        </div>

        <?php if (!empty($members_data)):// Folosim direct $members_data
             ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nume</th>
                        <th>Prenume</th>
                        <th>Rol</th>
                        <th>De Când Este Membru</th>
                        <th>Evenimente Participate</th>
                        <?php if ($is_admin): ?>
                        <th>Acțiuni</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members_data as $member): ?>
                    <?php
                    // Calculul "De Când Este Membru"
                    $join_date = new DateTime($member['join_date']);
                    $now = new DateTime();
                    $interval = $join_date->diff($now);

                    $years = $interval->y;
                    $months = $interval->m;
                    $days = $interval->d;
                    $since_joined = '';

                    if ($years > 0) {
                        $since_joined .= $years . ' ani ';
                    }
                    if ($months > 0) {
                        $since_joined .= $months . ' luni ';
                    }
                    if ($years == 0 && $months == 0 && $days > 0) {
                        $since_joined .= $days . ' zile';
                    } elseif ($years == 0 && $months == 0 && $days <= 0) {
                        $since_joined = 'Mai puțin de o zi';
                    }
                    ?>
                    <tr>
                        <td><a href="public_profile.php?user_id=<?php echo $member[
                            'id'
                        ]; ?>" class="text-black text-decoration-none"><?php echo htmlspecialchars(
    $member['name']
); ?></a></td>
                        <td><a href="public_profile.php?user_id=<?php echo $member[
                            'id'
                        ]; ?>" class="text-black text-decoration-none"><?php echo htmlspecialchars(
    $member['prenume']
); ?></a></td>
                        <td><?php echo role_to_readable($member['role']); ?></td>
                        <td><?php echo trim($since_joined); ?></td>
                        <td><?php echo $member['events_participated']; ?></td>
                        <?php if ($is_admin): ?>
                        <td>
                            <?php if ($user_id !== $member['id']):// Nu te poți șterge sau edita singur
                                 ?>
                                <?php if ($member['role'] !== 'owner'):// Nu poți șterge sau edita owner-ul
                                     ?>
                                    <form method="POST" class="d-inline remove-member-form">
                                        <input type="hidden" name="remove_user_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Șterge</button>
                                    </form>
                                    <form method="POST" class="d-inline edit-member-form">
                                        <input type="hidden" name="edit_user_id" value="<?php echo $member['id']; ?>">
                                        <select name="new_role" class="form-select d-inline w-auto">
                                            <option value="member" <?php if ($member['role'] === 'member') {
                                                echo 'selected';
                                            } ?>><?= role_to_readable('member') ?></option>
                                            <option value="admin" <?php if ($member['role'] === 'admin') {
                                                echo 'selected';
                                            } ?>><?= role_to_readable('admin') ?></option>
                                            <?php
                                    // Oare vrei să poți schimba și în 'viewer'? Dacă da, adaugă aici
                                    ?>
                                        </select>
                                        <button type="submit" class="btn btn-warning btn-sm">Editează</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Owner</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Tu</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nu există membri în această organizație.</p>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adaugă Membru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addMemberForm">
                    <div class="mb-3">
                        <label for="memberEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="memberEmail" name="invite_member_email" required>
                        <div class="invalid-feedback">Te rugăm să introduci un email valid.</div>
                    </div>
                    <div class="mb-3">
                        <label for="memberRole" class="form-label">Rol</label>
                        <select class="form-select" id="memberRole" name="memberRole">
                            <option value="member">Membru</option>
                            <option value="admin">Admin</option>
                            </select>
                    </div>
                    <button type="submit" class="btn btn-success">Trimite Invitația</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const addMemberForm = document.getElementById('addMemberForm');
    const addMemberModal = new bootstrap.Modal(document.getElementById('addMemberModal'));

    addMemberForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(addMemberForm);

        fetch('', { // Trimite la aceeași pagină (members_org.php)
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Eroare!',
                    text: data.message,
                });

                addMemberForm.querySelectorAll('.form-control').forEach(input => {
                    input.classList.remove('is-invalid');
                });
                if (data.field) {
                    const errorField = addMemberForm.querySelector(`#${data.field}`);
                    if (errorField) {
                        errorField.classList.add('is-invalid');
                    }
                }
            } else if (data.success) {
                addMemberModal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Succes!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else if (data.warning) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenție!',
                    text: data.message,
                });
                addMemberForm.querySelectorAll('.form-control').forEach(input => {
                    input.classList.remove('is-invalid');
                });
                if (data.field) {
                    const errorField = addMemberForm.querySelector(`#${data.field}`);
                    if (errorField) {
                        errorField.classList.add('is-invalid');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Eroare!',
                text: 'A apărut o eroare neașteptată.',
            });
        });
    });

    const removeMemberForms = document.querySelectorAll('.remove-member-form');
    removeMemberForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const userIdToRemove = this.querySelector('input[name="remove_user_id"]').value;
            // orgId nu mai e necesar în formData pentru că e deja în sesiune pe server
            // const orgId = this.querySelector('input[name="org_id"]').value;

            Swal.fire({
                title: 'Sigur dorești să ștergi acest membru?',
                text: "Această acțiune este ireversibilă!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Da, șterge!',
                cancelButtonText: 'Anulează'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(form);
                    fetch('', { // Trimite la aceeași pagină (members_org.php)
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success)  {
                            Swal.fire({
                                title: 'Șters!',
                                text: data.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Eroare!',
                                data.message,
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Eroare!',
                            'A apărut o eroare neașteptată.',
                            'error'
                        );
                    });
                }
            });
        });
    });

    const editMemberForms = document.querySelectorAll('.edit-member-form');
    editMemberForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('', { // Trimite la aceeași pagină (members_org.php)
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success)  {
                    Swal.fire({
                        title: 'Actualizat!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire(
                        'Eroare!',
                        data.message,
                        'error'
                    );
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire(
                    'Eroare!',
                    'A apărut o eroare neașteptată.',
                    'error'
                );
            });
        });
    });
</script>
</body>
</html>