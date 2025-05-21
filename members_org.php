<?php
require 'lib/conn_db.php';
$add_member_message = '';

$user_id = ensure_logged_in();
$org_id = ensure_existing_org();

$sql_org_name = 'SELECT name FROM organizations WHERE id = ?';
$stmt_org_name = $mysqli->prepare($sql_org_name);
$stmt_org_name->bind_param('i', $org_id);
$stmt_org_name->execute();
$result_org_name = $stmt_org_name->get_result();
$org_name = $result_org_name->fetch_assoc()['name'] ?? 'Organizație';

$sql_check_membership = 'SELECT role FROM roles WHERE user_id = ? AND org_id = ?';
$stmt_check_membership = $mysqli->prepare($sql_check_membership);
$stmt_check_membership->bind_param('ii', $user_id, $org_id);
$stmt_check_membership->execute();
$result_check_membership = $stmt_check_membership->get_result();
$membership = $result_check_membership->fetch_assoc();
$is_member = $membership !== null;
$is_admin = $is_member && ($membership['role'] === 'admin' || $membership['role'] === 'owner');

if (!$is_member) {
    header('Location: index.php');
    exit();
}

$sql_members = "SELECT u.id, u.name, u.prenume, r.role, r.join_date,
                                 (SELECT COUNT(er.event_id)
                                  FROM event_roles er
                                  WHERE er.user_id = u.id AND er.event_id IN (SELECT id FROM events WHERE org_id = ?)) AS events_participated
                               FROM users u
                               JOIN roles r ON u.id = r.user_id
                               WHERE r.org_id = ?
                               ORDER BY
                                   CASE
                                    WHEN r.role = 'owner' THEN 1
                                    WHEN r.role = 'admin' THEN 2
                                    WHEN r.role = 'member' THEN 3
                                    ELSE 4
                                   END,
                                   u.name, u.prenume";
$stmt_members = $mysqli->prepare($sql_members);
$stmt_members->bind_param('ii', $org_id, $org_id);
$stmt_members->execute();
$result_members = $stmt_members->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    if (isset($_POST['remove_user_id'])) {
        $remove_user_id = intval($_POST['remove_user_id']);
        $sql_check_owner = 'SELECT 1 FROM roles WHERE user_id = ? AND org_id = ? AND role = "owner"';
        $stmt_check_owner = $mysqli->prepare($sql_check_owner);
        $stmt_check_owner->bind_param('ii', $remove_user_id, $org_id);
        $stmt_check_owner->execute();
        $is_owner_to_remove = $stmt_check_owner->get_result()->num_rows > 0;

        if ($is_owner_to_remove) {
            $response = ['error' => true, 'message' => 'Nu poți șterge owner-ul organizației.'];
        } else {
            $sql_remove = 'DELETE FROM roles WHERE user_id = ? AND org_id = ?';
            $stmt_remove = $mysqli->prepare($sql_remove);
            $stmt_remove->bind_param('ii', $remove_user_id, $org_id);
            if ($stmt_remove->execute()) {
                $sql_delete_requests = "DELETE FROM requests
                                             WHERE organization_id = ?
                                               AND (
                                                    (request_type = 'organization_join_request' AND sender_user_id = ?) OR
                                                    (request_type = 'organization_invite_manual' AND receiver_user_id = ?) OR
                                                    (request_type = 'organization_join_request_response' AND receiver_user_id = ?)
                                                )";
                $stmt_delete_requests = $mysqli->prepare($sql_delete_requests);
                $stmt_delete_requests->bind_param('iiii', $org_id, $remove_user_id, $remove_user_id, $remove_user_id);
                $stmt_delete_requests->execute();

                $response = ['success' => true, 'message' => 'Membru șters cu succes!'];
            } else {
                $response = ['error' => true, 'message' => 'Eroare la ștergerea membrului.'];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    if (isset($_POST['edit_user_id']) && isset($_POST['new_role'])) {
        $edit_user_id = intval($_POST['edit_user_id']);
        $new_role = $_POST['new_role'];

        $sql_check_owner = 'SELECT 1 FROM roles WHERE user_id = ? AND org_id = ? AND role = "owner"';
        $stmt_check_owner = $mysqli->prepare($sql_check_owner);
        $stmt_check_owner->bind_param('ii', $edit_user_id, $org_id);
        $stmt_check_owner->execute();
        $is_owner_to_edit = $stmt_check_owner->get_result()->num_rows > 0;

        if ($is_owner_to_edit) {
            $response = ['error' => true, 'message' => 'Nu poți modifica rolul owner-ului organizației.'];
        } else {
            $sql_update = 'UPDATE roles SET role = ? WHERE user_id = ? AND org_id = ?';
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param('sii', $new_role, $edit_user_id, $org_id);
            if ($stmt_update->execute()) {
                $response = ['success' => true, 'message' => 'Rolul membrului a fost actualizat cu succes!'];
            } else {
                $response = ['error' => true, 'message' => 'Eroare la actualizarea rolului membrului.'];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_member_email']) && $is_admin) {
    $member_email = filter_var($_POST['invite_member_email'], FILTER_SANITIZE_EMAIL);
    $member_role = $_POST['memberRole'] ?? 'member';

    $response = ['error' => false, 'success' => false, 'warning' => false, 'message' => '', 'field' => null];

    header('Content-Type: application/json');

    if (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = true;
        $response['message'] = 'Adresă de email invalidă.';
        $response['field'] = 'memberEmail';
        echo json_encode($response);
        exit();
    }

    $sql_check_user = 'SELECT id FROM users WHERE email = ?';
    $stmt_check_user = $mysqli->prepare($sql_check_user);
    $stmt_check_user->bind_param('s', $member_email);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();

    if ($result_check_user->num_rows > 0) {
        $user_data = $result_check_user->fetch_assoc();
        $receiver_user_id = $user_data['id'];

        $sql_check_member = 'SELECT 1 FROM roles WHERE user_id = ? AND org_id = ?';
        $stmt_check_member = $mysqli->prepare($sql_check_member);
        $stmt_check_member->bind_param('ii', $receiver_user_id, $org_id);
        $stmt_check_member->execute();
        $result_check_member = $stmt_check_member->get_result();

        if ($result_check_member->num_rows > 0) {
            $response['warning'] = true;
            $response['message'] = 'Utilizatorul este deja membru al organizației.';
            $response['field'] = 'memberEmail';
        } else {
            $sql_check_request =
                "SELECT 1 FROM requests WHERE request_type = 'organization_invite_manual' AND sender_user_id = ? AND receiver_user_id = ? AND organization_id = ? AND status IN ('pending', 'accepted')";
            $stmt_check_request = $mysqli->prepare($sql_check_request);
            $stmt_check_request->bind_param('iii', $user_id, $receiver_user_id, $org_id);
            $stmt_check_request->execute();
            $result_check_request = $stmt_check_request->get_result();

            if ($result_check_request->num_rows > 0) {
                $response['warning'] = true;
                $response['message'] = 'O invitație a fost deja trimisă acestui utilizator sau a fost acceptată.';
                $response['field'] = 'memberEmail';
            } else {
                $sql_invite =
                    "INSERT INTO requests (request_type, sender_user_id, receiver_user_id, organization_id, status) VALUES (?, ?, ?, ?, 'pending')";
                $stmt_invite = $mysqli->prepare($sql_invite);
                $invite_type = 'organization_invite_manual';
                $stmt_invite->bind_param('siii', $invite_type, $user_id, $receiver_user_id, $org_id);

                if ($stmt_invite->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Invitația a fost trimisă cu succes!';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Eroare la trimiterea invitației.';
                }
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Nu există un utilizator cu acest email.';
        $response['field'] = 'memberEmail';
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
    <?php require 'utils/global.html'; ?>
    <title>Membrii organizației <?php echo htmlspecialchars($org_name); ?></title>
</head>
<body>
<div class="d-flex">
    <?php include 'utils/sidebar.php'; ?>

    <div class="my-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Membrii organizației <?php echo htmlspecialchars($org_name); ?></h2>
            <?php if ($is_admin): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">Adaugă Membru</button>
            <?php endif; ?>
        </div>

        <?php if ($result_members->num_rows > 0): ?>
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
                    <?php while ($member = $result_members->fetch_assoc()): ?>
                    <?php
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
                        <td><?php echo $since_joined; ?></td>
                        <td><?php echo $member['events_participated']; ?></td>
                        <?php if ($is_admin): ?>
                        <td>
                            <?php if ($user_id !== $member['id']): ?>
                                <?php if ($member['role'] !== 'owner'): ?>
                                    <form method="POST" class="d-inline remove-member-form">
                                        <input type="hidden" name="remove_user_id" value="<?php echo $member['id']; ?>">
                                        <input type="hidden" name="org_id" value="<?php echo $org_id; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Șterge</button>
                                    </form>
                                    <form method="POST" class="d-inline edit-member-form">
                                        <input type="hidden" name="edit_user_id" value="<?php echo $member['id']; ?>">
                                        <input type="hidden" name="org_id" value="<?php echo $org_id; ?>">
                                        <select name="new_role" class="form-select d-inline w-auto">
                                            <option value="member" <?php if ($member['role'] === 'member') {
                                                echo 'selected';
                                            } ?>><?= role_to_readable('member') ?></option>
                                            <option value="admin" <?php if ($member['role'] === 'admin') {
                                                echo 'selected';
                                            } ?>><?= role_to_readable('admin') ?></option>
                                        </select>
                                        <button type="submit" class="btn btn-warning btn-sm">Editează</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
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
<script>
    const addMemberForm = document.getElementById('addMemberForm');
    const addMemberModal = new bootstrap.Modal(document.getElementById('addMemberModal'));

    addMemberForm.addEventListener('submit', function(event) {
        event.preventDefault(); 

        const formData = new FormData(addMemberForm);

        fetch('', { 
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
            const orgId = this.querySelector('input[name="org_id"]').value;

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
                    fetch('', {
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
            })
        });
    });

    const editMemberForms = document.querySelectorAll('.edit-member-form');
    editMemberForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('', {
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