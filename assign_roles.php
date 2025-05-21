<?php
require 'lib/conn_db.php';
$user_id = ensure_logged_in();

if (empty($_GET['event_id'])) {
    die('ID eveniment lipsă.');
}

$event_id = intval($_GET['event_id']);

$sql_event_info = "SELECT e.name AS event_name, o.name AS org_name, o.id AS org_id, e.available_roles, e.created_by AS owner_id
                     FROM events e
                     JOIN organizations o ON e.org_id = o.id
                     WHERE e.id = ?";
$stmt_event_info = $mysqli->prepare($sql_event_info);
$stmt_event_info->bind_param('i', $event_id);
$stmt_event_info->execute();
$result_event_info = $stmt_event_info->get_result()->fetch_assoc();

if (!$result_event_info) {
    die('Evenimentul nu a fost găsit sau nu aveți acces.');
}
$event_name = $result_event_info['event_name'];
$org_name = $result_event_info['org_name'];
$org_id = $result_event_info['org_id'];
$owner_id = $result_event_info['owner_id'];

$available_roles_string = $result_event_info['available_roles'] ?? '';
$roles = !empty($available_roles_string) ? array_map('trim', explode(',', $available_roles_string)) : [];
$roles = array_filter($roles);
$is_org_admin_or_owner = false;

if ($org_id > 0) {
    $sql_check_role = "SELECT 1 FROM roles WHERE user_id = ? AND org_id = ? AND role IN ('admin', 'owner')";
    $stmt_check_role = $mysqli->prepare($sql_check_role);
    $stmt_check_role->bind_param('ii', $user_id, $org_id);
    $stmt_check_role->execute();
    if ($stmt_check_role->get_result()->num_rows > 0) {
        $is_org_admin_or_owner = true;
    }
    $stmt_check_role->close();
}
$is_event_owner = $user_id === $owner_id;

$has_management_permission = $is_org_admin_or_owner || $is_event_owner;
$permission_error = null;
$result_members = null;

if (!$has_management_permission) {
    $permission_error = 'Nu aveți permisiunea de a atribui roluri pentru acest eveniment.';
} else {
    $sql_members = "SELECT
                        u.id,
                        u.name,
                        u.prenume,
                        COALESCE(er.role, '') AS role,
                        (u.id = ?) AS is_creator 
                    FROM users u
                    INNER JOIN roles r ON u.id = r.user_id AND r.org_id = ?
                    LEFT JOIN event_roles er ON u.id = er.user_id AND er.event_id = ?
                    WHERE u.id != ? 
                    UNION
                    SELECT 
                        u.id,
                        u.name,
                        u.prenume,
                        COALESCE(er.role, '') AS role,
                        1 AS is_creator 
                    FROM users u
                    LEFT JOIN event_roles er ON u.id = er.user_id AND er.event_id = ?
                    WHERE u.id = ?
                    ORDER BY is_creator DESC, name, prenume";

    $stmt_members = $mysqli->prepare($sql_members);
    $stmt_members->bind_param('iiiiii', $owner_id, $org_id, $event_id, $owner_id, $event_id, $owner_id);
    $stmt_members->execute();
    $result_members = $stmt_members->get_result();
}
$stmt_event_info->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $has_management_permission) {
    header('Content-Type: application/json');
    $response = ['error' => true, 'message' => 'Acțiune nevalidă sau eroare neașteptată.'];

    if (isset($_POST['action']) && $_POST['action'] === 'assign_roles') {
        $all_successful = true;
        if (isset($_POST['user_roles']) && is_array($_POST['user_roles'])) {
            foreach ($_POST['user_roles'] as $user_id_to_assign => $role_assigned) {
                $user_id_to_assign = intval($user_id_to_assign);
                $role_assigned = trim($role_assigned);

                $is_valid_user_for_role = false;
                if ($user_id_to_assign === $owner_id) {
                    $is_valid_user_for_role = true;
                } else {
                    $sql_check_member_org = 'SELECT 1 FROM roles WHERE user_id = ? AND org_id = ?';
                    $stmt_check_member_org = $mysqli->prepare($sql_check_member_org);
                    $stmt_check_member_org->bind_param('ii', $user_id_to_assign, $org_id);
                    $stmt_check_member_org->execute();
                    if ($stmt_check_member_org->get_result()->num_rows > 0) {
                        $is_valid_user_for_role = true;
                    }
                    $stmt_check_member_org->close();
                }

                if (!$is_valid_user_for_role) {
                    error_log(
                        "Încercare de atribuire rol către non-membru/non-creator: User {$user_id_to_assign}, Event {$event_id}, Org {$org_id}"
                    );
                    $all_successful = false;
                    continue;
                }

                if (!empty($role_assigned)) {
                    if (!empty($roles) && !in_array($role_assigned, $roles)) {
                        error_log(
                            "Încercare de atribuire rol invalid '{$role_assigned}' pentru Eveniment {$event_id}. Roluri disponibile: " .
                                implode(',', $roles)
                        );
                        $all_successful = false;
                        continue;
                    }
                    $sql_assign = "INSERT INTO event_roles (event_id, user_id, role) VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE role = ?";
                    $stmt_assign = $mysqli->prepare($sql_assign);
                    $stmt_assign->bind_param('iiss', $event_id, $user_id_to_assign, $role_assigned, $role_assigned);
                } else {
                    $sql_assign = 'DELETE FROM event_roles WHERE event_id = ? AND user_id = ?';
                    $stmt_assign = $mysqli->prepare($sql_assign);
                    $stmt_assign->bind_param('ii', $event_id, $user_id_to_assign);
                }

                if (!$stmt_assign->execute()) {
                    $all_successful = false;
                    error_log(
                        "Eroare la salvarea/ștergerea rolului pentru user {$user_id_to_assign}, event {$event_id}: " .
                            $stmt_assign->error
                    );
                }
                $stmt_assign->close();
            }
        }
        if ($all_successful) {
            $response = ['success' => true, 'message' => 'Rolurile au fost actualizate cu succes!'];
        } else {
            $response = [
                'error' => true,
                'message' =>
                    'Unele roluri nu au putut fi salvate. Verificați logurile sau validitatea utilizatorilor/rolurilor.',
            ];
        }
        echo json_encode($response);
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_new_role') {
        $new_role_name = trim($_POST['new_role'] ?? '');

        if (empty($new_role_name)) {
            $response = ['warning' => true, 'message' => 'Te rugăm să introduci un nume pentru rol.'];
        } elseif (in_array($new_role_name, $roles)) {
            $response = ['info' => true, 'message' => 'Rolul "' . htmlspecialchars($new_role_name) . '" există deja.'];
        } else {
            $current_available_roles = $roles;
            $current_available_roles[] = $new_role_name;
            $updated_available_roles = array_map('trim', $current_available_roles);
            $updated_available_roles = array_filter($updated_available_roles);
            $updated_available_roles = array_unique($updated_available_roles);
            $updated_available_roles_string = implode(',', $updated_available_roles);

            $stmt_update_available_roles = $mysqli->prepare('UPDATE events SET available_roles = ? WHERE id = ?');
            if ($stmt_update_available_roles) {
                $stmt_update_available_roles->bind_param('si', $updated_available_roles_string, $event_id);
                if ($stmt_update_available_roles->execute()) {
                    $response = [
                        'success' => true,
                        'message' => 'Rolul "' . htmlspecialchars($new_role_name) . '" a fost adăugat cu succes!',
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'Eroare la adăugarea rolului: ' . $stmt_update_available_roles->error,
                    ];
                    error_log('Nu s-a putut adăuga rolul nou: ' . $stmt_update_available_roles->error);
                }
                $stmt_update_available_roles->close();
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Eroare la pregătirea interogării pentru actualizarea rolurilor: ' . $mysqli->error,
                ];
                error_log(
                    'Nu s-a putut pregăti interogarea pentru actualizarea rolurilor disponibile: ' . $mysqli->error
                );
            }
        }
        echo json_encode($response);
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_management_permission) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Nu aveți permisiunea de a efectua această acțiune.',
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'utils/global.html'; ?>
    <link rel="stylesheet" href="styles/assign-roles-style.css">
    <title>Atribuire Roluri Eveniment - <?php echo htmlspecialchars($event_name); ?></title>
</head>
<body>
    <div class="d-flex">
        <?php include 'utils/sidebar.php'; ?>
        <div class="my-content p-4">
            <header class="mb-4">
                <h2>Atribuie Roluri: <strong><?php echo htmlspecialchars($event_name); ?></strong></h2>
                <h4> Organizație: <?php echo htmlspecialchars($org_name); ?></h4>
            </header>

            <?php if ($permission_error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars(
                    $permission_error
                ); ?></div>
            <?php else: ?>
                <div class="card bg-dark text-light p-3 shadow-sm mb-4">
                    <h5 class="card-title mb-3">Gestionează Roluri Disponibile</h5>
                    <form method="POST" id="addNewRoleForm" class="row gx-2 gy-2 align-items-end">
                        <input type="hidden" name="action" value="add_new_role">
                        <div class="col-sm flex-grow-1">
                            <label for="new_role_input" class="form-label small">Nume Rol Nou</label>
                            <input type="text" id="new_role_input" name="new_role" class="form-control form-control-sm" placeholder="Ex: Fotograf, Coordonator..." required>
                        </div>
                        <div class="col-sm-auto">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-plus-circle-fill"></i> Adaugă Rol
                            </button>
                        </div>
                    </form>
                    <?php if (!empty($roles)): ?>
                    <div class="mt-3">
                        <p class="mb-1 small">Roluri disponibile existente:</p>
                        <div>
                            <?php foreach ($roles as $r): ?>
                                <span class="badge bg-secondary me-1 mb-1 fw-normal"><?php echo htmlspecialchars(
                                    $r
                                ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>


                <?php if ($result_members && $result_members->num_rows > 0): ?>
                    <input type="text" id="searchBar" class="form-control mb-3" placeholder="🔍 Caută membru după nume...">

                    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                        <button class="btn btn-outline-secondary btn-sm p-1 selection-role" type="button" onclick="selectAllWithoutRole()">
                            <div class="selection-text bi-person-check m-1">Selectează toți participanții fără rol</div> 
                        </button>
                        <div id="bulkAssignSection"  class="d-flex align-items-center gap-2 p-1 border border-secondary btn-outline-secondary selection-role">
                             <label for="bulkAssignRole" class="selection-text form-label small mb-0 me-1">Pentru selecție:</label>
                            <select id="bulkAssignRole" class="form-select form-select-sm" style="width: auto;">
                                <option value="">-- Alege rol --</option>
                                <?php if (!empty($roles)): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars(
    $role
) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Nu sunt roluri definite</option>
                                <?php endif; ?>
                            </select>
                            <button class="btn btn-info btn-sm" type="button" onclick="assignRoleToSelected()">
                                <i class="bi bi-check2-square"></i> Atribuie
                            </button>
                        </div>
                    </div>

                    <form method="POST" id="assignRolesForm">
                        <input type="hidden" name="action" value="assign_roles">
                        <div class="table-responsive">
                            <table class="table table-dark table-striped table-hover align-middle">
                                <thead class="table-light"> <tr>
                                        <th scope="col" style="width: 3.5rem; text-align: center;">
                                            <input class="form-check-input" type="checkbox" id="selectAllCheckbox" title="Selectează/Deselectează Toți Vizibilii">
                                        </th>
                                        <th scope="col">Nume Prenume</th>
                                        <th scope="col" style="min-width: 220px;">Rol Atribuit</th>
                                    </tr>
                                </thead>
                                <tbody id="memberTable">
                                <?php
                                $members_array = [];
                                if ($result_members) {
                                    while ($member_row = $result_members->fetch_assoc()) {
                                        $members_array[] = $member_row;
                                    }
                                    if ($stmt_members) {
                                        $stmt_members->close();
                                    }
                                }

                                if (empty($members_array)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center fst-italic text-muted py-3">Niciun membru eligibil găsit.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($members_array as $member): ?>
                                    <tr data-user-id="<?= $member['id'] ?>" class="<?php echo $member['is_creator']
    ? 'table-info-custom'
    : ''; ?>">
                                        <td style="text-align: center;">
                                            <input type="checkbox" class="form-check-input memberCheckbox" data-user-id="<?= $member[
                                                'id'
                                            ] ?>">
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($member['name'] . ' ' . $member['prenume']) ?>
                                            <?php if ($member['is_creator']): ?>
                                                <span class="badge bg-primary rounded-pill ms-2" title="Creatorul Evenimentului">
                                                    <i class="bi bi-star-fill"></i> Creator
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select name="user_roles[<?= $member[
                                                'id'
                                            ] ?>]" class="form-select form-select-sm member-role-select" data-user-id="<?= $member[
    'id'
] ?>" aria-label="Selectează rol pentru <?= htmlspecialchars($member['name'] . ' ' . $member['prenume']) ?>">
                                                <option value="" <?= empty($member['role'])
                                                    ? 'selected'
                                                    : '' ?>>-- Fără rol atribuit --</option>
                                                <?php if (!empty($roles)): ?>
                                                    <?php foreach ($roles as $role_option): ?>
                                                        <option value="<?= htmlspecialchars(
                                                            $role_option
                                                        ) ?>" <?= $member['role'] === $role_option ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($role_option) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <?php if (
                                                    !empty($member['role']) &&
                                                    !in_array($member['role'], $roles) &&
                                                    !empty($roles)
                                                ): ?>
                                                     <option value="<?= htmlspecialchars(
                                                         $member['role']
                                                     ) ?>" selected class="text-warning fst-italic">
                                                        <?= htmlspecialchars($member['role']) ?> (vechi/indisponibil)
                                                    </option>
                                                <?php endif; ?>
                                                 <?php if (empty($roles) && empty($member['role'])): ?>
                                                    <option value="" disabled class="fst-italic">Nu sunt roluri definite</option>
                                                <?php endif; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif;
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($members_array)): ?>
                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="bi bi-save-fill"></i> Salvează Toate Atribuirile
                        </button>
                        <?php endif; ?>
                    </form>
                <?php elseif ($event_id !== 0 && !$permission_error): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        Nu există membri eligibili în organizație pentru acest eveniment sau nu au fost definite roluri.
                        <?php if (empty($roles)): ?>
                            Puteți adăuga roluri noi folosind formularul de mai sus.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div></div><script>
document.addEventListener('DOMContentLoaded', () => {
    const searchBar = document.getElementById('searchBar');
    const memberTableBody = document.getElementById('memberTable');
    const bulkAssignSection = document.getElementById('bulkAssignSection');
    const bulkAssignRoleSelect = document.getElementById('bulkAssignRole');
    const selectAllHeaderCheckbox = document.getElementById('selectAllCheckbox');
    const assignRolesForm = document.getElementById('assignRolesForm');
    const addNewRoleForm = document.getElementById('addNewRoleForm');

    function updateBulkAssignVisibility() {
        if (!memberTableBody || !bulkAssignSection) return;
        const anyChecked = memberTableBody.querySelector('.memberCheckbox:checked');
        bulkAssignSection.style.display = anyChecked ? 'flex' : 'none';
        if (!anyChecked && bulkAssignRoleSelect) {
            bulkAssignRoleSelect.value = ''; 
        }
    }

    function areAllVisibleChecked() {
        if (!memberTableBody) return false;
        const visibleCheckboxes = Array.from(memberTableBody.querySelectorAll('tr'))
                                     .filter(row => row.style.display !== 'none')
                                     .map(row => row.querySelector('.memberCheckbox'))
                                     .filter(cb => cb); 
        if (visibleCheckboxes.length === 0 && searchBar && searchBar.value.trim() !== '') return false; 
        if (visibleCheckboxes.length === 0) return selectAllHeaderCheckbox ? selectAllHeaderCheckbox.checked : false;
        return visibleCheckboxes.every(cb => cb.checked);
    }
    
    function updateSelectAllHeaderCheckboxState() {
        if (selectAllHeaderCheckbox) {
            selectAllHeaderCheckbox.checked = areAllVisibleChecked();
        }
    }

    if (searchBar && memberTableBody) {
        searchBar.addEventListener('input', function() {
            let searchQuery = this.value.toLowerCase().trim();
            memberTableBody.querySelectorAll('tr').forEach(row => {
                let nameCell = row.cells[1];
                if (nameCell) {
                    row.style.display = nameCell.textContent.toLowerCase().includes(searchQuery) ? '' : 'none';
                }
            });
            updateSelectAllHeaderCheckboxState(); 
            updateBulkAssignVisibility(); 
        });
    }

    window.selectAllWithoutRole = function() {
        if (!memberTableBody) return;
        let selectedCount = 0;
        let visibleWithNoRole = 0;
        memberTableBody.querySelectorAll('tr').forEach(row => {
            if (row.style.display === 'none') return;
            const checkbox = row.querySelector('.memberCheckbox');
            const selectElement = row.querySelector('select.member-role-select');
            if (checkbox && selectElement) {
                if (selectElement.value === '') {
                    visibleWithNoRole++;
                    checkbox.checked = true;
                    selectedCount++;
                } else {
                    checkbox.checked = false; 
                }
            }
        });
        updateSelectAllHeaderCheckboxState();
        updateBulkAssignVisibility();
        if (selectedCount > 0) {
             Swal.fire({ icon: 'success', title: 'Selectați!', text: `${selectedCount} membri fără rol (vizibili) au fost selectați.`, timer: 2000, showConfirmButton: false });
        } else if (visibleWithNoRole === 0 && memberTableBody.querySelector('tr:not([style*="display: none"])')) { 
            Swal.fire({ icon: 'info', title: 'Info', text: 'Toți membrii vizibili au deja un rol atribuit.' });
        } else if (!memberTableBody.querySelector('tr:not([style*="display: none"])')) {
             Swal.fire({ icon: 'info', title: 'Info', text: 'Niciun membru vizibil pentru a selecta (verificați filtrul de căutare).' });
        }
    }

    if (selectAllHeaderCheckbox && memberTableBody) {
        selectAllHeaderCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            memberTableBody.querySelectorAll('tr').forEach(row => {
                if (row.style.display !== 'none') {
                    const checkbox = row.querySelector('.memberCheckbox');
                    if (checkbox) checkbox.checked = isChecked;
                }
            });
            updateBulkAssignVisibility();
        });
    }

    if (memberTableBody) {
        memberTableBody.addEventListener('change', function(event) {
            if (event.target.classList.contains('memberCheckbox')) {
                updateSelectAllHeaderCheckboxState();
                updateBulkAssignVisibility();
            }
        });
    }

    window.assignRoleToSelected = function() {
        if (!memberTableBody || !bulkAssignRoleSelect) return;
        const selectedRoleValue = bulkAssignRoleSelect.value;
        const selectedRoleText = bulkAssignRoleSelect.options[bulkAssignRoleSelect.selectedIndex]?.text || selectedRoleValue;

        if (!selectedRoleValue) {
            Swal.fire({ icon: 'warning', title: 'Atenție!', html: 'Te rugăm să alegi un rol din lista <strong>"Pentru selecție"</strong>.' });
            return;
        }

        const checkedUserRows = Array.from(memberTableBody.querySelectorAll('.memberCheckbox:checked'))
                                   .map(cb => cb.closest('tr'));

        if (checkedUserRows.length === 0) {
            Swal.fire({ icon: 'info', title: 'Info', text: 'Niciun utilizator nu este selectat.' });
            return;
        }

        let usersAssignedCount = 0;
        checkedUserRows.forEach(userRow => {
            const roleSelectInRow = userRow.querySelector('select.member-role-select');
            if (roleSelectInRow) {
                roleSelectInRow.value = selectedRoleValue;
                usersAssignedCount++;
            }
        });

        checkedUserRows.forEach(row => {
            const cb = row.querySelector('.memberCheckbox');
            if(cb) cb.checked = false;
        });
        updateSelectAllHeaderCheckboxState(); 
        updateBulkAssignVisibility();     

        Swal.fire({
            icon: 'success',
            title: 'Roluri actualizate local!',
            html: `Rolul "<strong>${selectedRoleText}</strong>" a fost setat pentru <strong>${usersAssignedCount}</strong> utilizatori selectați.<br>Apasă "<strong>Salvează Toate Atribuirile</strong>" pentru a confirma.`,
            timer: 4000,
            showConfirmButton: true
        });
    }

    function handleSubmit(form, buttonTextDefault) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${buttonTextDefault}...`;
        submitButton.disabled = true;

        fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            let iconType = 'info', titleText = 'Notificare';
            if (data.error) { iconType = 'error'; titleText = 'Eroare!'; }
            else if (data.success) { iconType = 'success'; titleText = 'Succes!'; }
            else if (data.warning) { iconType = 'warning'; titleText = 'Atenție!'; }

            Swal.fire({ icon: iconType, title: titleText, text: data.message, timer: data.success ? 2500 : 3500, showConfirmButton: !data.success })
            .then(() => { if (data.success) window.location.reload(); });

            if (form === addNewRoleForm && (data.success || data.info || data.warning)) {
                 const newRoleInput = form.querySelector('input[name="new_role"]');
                 if(newRoleInput) newRoleInput.value = '';
            }
            if (form === addNewRoleForm && data.warning && data.message.includes('nume pentru rol')) {
                const newRoleInput = form.querySelector('input[name="new_role"]');
                if(newRoleInput) newRoleInput.classList.add('is-invalid');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            Swal.fire({ icon: 'error', title: 'Eroare de Comunicație!', text: 'A apărut o eroare la trimiterea datelor. Verifică consola.' });
        })
        .finally(() => {
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        });
    }

    if (assignRolesForm) {
        assignRolesForm.addEventListener('submit', function(event) {
            event.preventDefault();
            handleSubmit(this, 'Salvare');
        });
    }

    if (addNewRoleForm) {
        const newRoleInput = addNewRoleForm.querySelector('input[name="new_role"]');
        addNewRoleForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (newRoleInput && newRoleInput.value.trim() === '') {
                Swal.fire({ icon: 'warning', title: 'Atenție!', text: 'Te rugăm să introduci un nume pentru rol.' });
                newRoleInput.classList.add('is-invalid');
                return;
            }
            if(newRoleInput) newRoleInput.classList.remove('is-invalid');
            handleSubmit(this, 'Adăugare');
        });
        if(newRoleInput){
            newRoleInput.addEventListener('input', function() {
                if (this.value.trim() !== '') this.classList.remove('is-invalid');
            });
        }
    }
    updateSelectAllHeaderCheckboxState();
    updateBulkAssignVisibility();
});
</script>
</body>
</html>