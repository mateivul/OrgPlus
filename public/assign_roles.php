<?php

require_once __DIR__ . '/../src/config.php';

// 2. Include fișierul cu funcții ajutătoare (ex: funcții de redirect, de verificare a sesiunii)
// require_once __DIR__ . '/../utils/app_helpers.php';

// Autoloading (dacă nu ai deja un autoloader PSR-4 configurat)
// Ideal, ar trebui să ai un autoloader Composer
require_once __DIR__ . '/../utils/app_helpers.php';

$eventService = getService('EventService');
$authService = getService(AuthService::class);
$currentUser = $authService->getCurrentUser();

$user_id = $currentUser ? $currentUser->getId() : null;

$event_id = intval($_GET['event_id'] ?? 0);

if ($event_id === 0) {
    die('ID eveniment lipsă.');
}

// Obține detaliile evenimentului și permisiunile
$eventDetails = $eventService->getEventManagementDetails($event_id, $user_id);

$event = $eventDetails['event'];
$event_name = $event ? $event->getName() : 'Eveniment necunoscut';
$org_name = $eventDetails['org_name'];
$org_id = $event ? $event->getOrgId() : 0;
$event_creator_id = $event ? $event->getCreatedBy() : 0; // Renamed from owner_id to event_creator_id for clarity with events
$available_roles = $eventDetails['roles'];
$has_management_permission = $eventDetails['has_management_permission'];
$permission_error = $eventDetails['permission_error'];

$members_array = [];
if ($has_management_permission) {
    $members_array = $eventService->getEligibleEventMembers($org_id, $event_id, $event_creator_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $has_management_permission) {
    header('Content-Type: application/json');
    $response = ['error' => true, 'message' => 'Acțiune nevalidă sau eroare neașteptată.'];

    if (isset($_POST['action']) && $_POST['action'] === 'assign_roles') {
        $user_roles_to_assign = $_POST['user_roles'] ?? [];

        if (empty($user_roles_to_assign)) {
            $response = ['info' => true, 'message' => 'Nu au fost trimise roluri pentru actualizare.'];
        } elseif (
            $eventService->updateEventRoles(
                $event_id,
                $org_id,
                $user_id, // actingUserId
                $event_creator_id, // eventCreatorId
                $user_roles_to_assign,
                $available_roles
            )
        ) {
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
        $new_role_name = $_POST['new_role'] ?? '';

        $addRoleResult = $eventService->addNewAvailableRole($event_id, $new_role_name, $available_roles);

        if ($addRoleResult['success']) {
            $response = ['success' => true, 'message' => $addRoleResult['message']];
            // Reîncarcă rolurile disponibile pentru a reflecta modificarea imediat
            $available_roles = $addRoleResult['newRoles'];
        } else {
            // Verifică tipurile de mesaje pentru a oferi feedback specific
            if (strpos($addRoleResult['message'], 'exista deja') !== false) {
                $response = ['info' => true, 'message' => $addRoleResult['message']];
            } elseif (strpos($addRoleResult['message'], 'nume pentru rol') !== false) {
                $response = ['warning' => true, 'message' => $addRoleResult['message']];
            } else {
                $response = ['error' => true, 'message' => $addRoleResult['message']];
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
    <?php require '../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/assign-roles-style.css">
    <title>Atribuire Roluri Eveniment - <?php echo htmlspecialchars($event_name); ?></title>
</head>
<body>
    <div class="d-flex">
    <?php require '../includes/sidebar.php'; ?>
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
                    <?php if (!empty($available_roles)): ?>
                    <div class="mt-3">
                        <p class="mb-1 small">Roluri disponibile existente:</p>
                        <div>
                            <?php foreach ($available_roles as $r): ?>
                                <span class="badge bg-secondary me-1 mb-1 fw-normal"><?php echo htmlspecialchars(
                                    $r
                                ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>


                <?php if (!empty($members_array)): ?>
                    <input type="text" id="searchBar" class="form-control mb-3" placeholder="🔍 Caută membru după nume...">

                    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                        <button class="btn btn-outline-secondary btn-sm p-1 selection-role" type="button" onclick="selectAllWithoutRole()">
                            <div class="selection-text bi-person-check m-1">Selectează toți participanții fără rol</div>
                        </button>
                        <div id="bulkAssignSection"  class="d-flex align-items-center gap-2 p-1 border border-secondary btn-outline-secondary selection-role">
                             <label for="bulkAssignRole" class="selection-text form-label small mb-0 me-1">Pentru selecție:</label>
                            <select id="bulkAssignRole" class="form-select form-select-sm" style="width: auto;">
                                <option value="">-- Alege rol --</option>
                                <?php if (!empty($available_roles)): ?>
                                    <?php foreach ($available_roles as $role): ?>
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
                                <?php if (empty($members_array)): ?>
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
                                                <?php if (!empty($available_roles)): ?>
                                                    <?php foreach ($available_roles as $role_option): ?>
                                                        <option value="<?= htmlspecialchars(
                                                            $role_option
                                                        ) ?>" <?= $member['role'] === $role_option ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($role_option) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <!-- <?php if (
                                                    !empty($member['role']) &&
                                                    !in_array($member['role'], $available_roles) &&
                                                    !empty($available_roles)
                                                ): ?>
                                                     <option value="<?= htmlspecialchars(
                                                         $member['role']
                                                     ) ?>" selected class="text-warning fst-italic">
                                                        <?= htmlspecialchars($member['role']) ?> (vechi/indisponibil)
                                                    </option>
                                                <?php endif; ?> -->
                                                 <?php if (empty($available_roles) && empty($member['role'])): ?>
                                                    <option value="" disabled class="fst-italic">Nu sunt roluri definite</option>
                                                <?php endif; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                        <?php if (empty($available_roles)): ?>
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
        // if (selectedCount > 0) {
        //      Swal.fire({ icon: 'success', title: 'Selectați!', text: `${selectedCount} membri fără rol (vizibili) au fost selectați.`, timer: 2000, showConfirmButton: false });
        // } else if (visibleWithNoRole === 0 && memberTableBody.querySelector('tr:not([style*="display: none"])')) {
        //     Swal.fire({ icon: 'info', title: 'Info', text: 'Toți membrii vizibili au deja un rol atribuit.' });
        // } else if (!memberTableBody.querySelector('tr:not([style*="display: none"])')) {
        //      Swal.fire({ icon: 'info', title: 'Info', text: 'Niciun membru vizibil pentru a selecta (verificați filtrul de căutare).' });
        // }
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

        // Swal.fire({
        //     icon: 'success',
        //     title: 'Roluri actualizate local!',
        //     html: `Rolul "<strong>${selectedRoleText}</strong>" a fost setat pentru <strong>${usersAssignedCount}</strong> utilizatori selectați.<br>Apasă "<strong>Salvează Toate Atribuirile</strong>" pentru a confirma.`,
        //     timer: 4000,
        //     showConfirmButton: true
        // });
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