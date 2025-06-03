<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../utils/app_helpers.php';

$user_id = ensure_logged_in();

$update_success = null;
$update_message = '';
$user = [];
$organizations = [];
$hours_data = [];

function ensure_user_data(&$user_array)
{
    $defaults = ['name' => '', 'prenume' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')];
    foreach ($defaults as $key => $value) {
        if (!isset($user_array[$key])) {
            $user_array[$key] = $value;
        }
    }
}

// Fetch user data
$sql_user = 'SELECT name, prenume, email, created_at FROM users WHERE id = ?';
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC) ?: [];
ensure_user_data($user);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $prenume = trim(filter_input(INPUT_POST, 'prenume', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));

    if (empty($name) || empty($prenume)) {
        $update_success = false;
        $update_message = 'Numele și prenumele sunt obligatorii.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_success = false;
        $update_message = 'Adresa de email nu este validă.';
    } else {
        $stmt_check_email = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt_check_email->execute([$email, $user_id]);
        if ($stmt_check_email->fetchColumn()) {
            $update_success = false;
            $update_message = 'Această adresă de email este deja folosită de altcineva.';
        } else {
            $stmt_update = $pdo->prepare(
                'UPDATE users SET name = ?, prenume = ?, email = ?, updated_at = NOW() WHERE id = ?'
            );
            if ($stmt_update->execute([$name, $prenume, $email, $user_id])) {
                $update_success = true;
                $update_message = 'Profil actualizat cu succes!';
                $user['name'] = $name;
                $user['prenume'] = $prenume;
                $user['email'] = $email;
            } else {
                $update_success = false;
                $update_message = 'Eroare la actualizare. Încercați din nou.';
            }
        }
    }
}

// Fetch worked hours per organization
$sql_hours = "SELECT org_id, o.name AS org_name, SUM(hours) AS total_hours,
                     COUNT(DISTINCT DATE(work_date)) AS days_worked
              FROM worked_hours wh
              JOIN organizations o ON wh.org_id = o.id
              WHERE wh.user_id = ?
              GROUP BY org_id";
$stmt_hours = $pdo->prepare($sql_hours);
$stmt_hours->execute([$user_id]);
while ($row = $stmt_hours->fetch(PDO::FETCH_ASSOC)) {
    $hours_data[$row['org_id']] = $row;
}

// Fetch organizations and participation
$sql_organizations = "SELECT o.id, o.name AS org_name, o.description, r.join_date, r.role,
                             (SELECT COUNT(e.id) FROM events e 
                              LEFT JOIN event_roles er ON e.id = er.event_id AND er.user_id = r.user_id
                              WHERE e.org_id = o.id AND er.user_id = r.user_id) AS events_participated
                      FROM roles r
                      JOIN organizations o ON r.org_id = o.id
                      WHERE r.user_id = ?
                      ORDER BY r.join_date DESC";
$stmt_organizations = $pdo->prepare($sql_organizations);
$stmt_organizations->execute([$user_id]);
$organizations = $stmt_organizations->fetchAll(PDO::FETCH_ASSOC);

$total_events_participated = array_sum(array_column($organizations, 'events_participated'));
$total_admin_roles = count(
    array_filter($organizations, function ($org) {
        return in_array($org['role'], ['admin', 'owner']);
    })
);
$total_worked_hours_all_orgs = array_sum(array_column($hours_data, 'total_hours'));
$total_days_worked_all_orgs = array_sum(array_column($hours_data, 'days_worked'));
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilul Meu - <?= htmlspecialchars($user['name'] . ' ' . $user['prenume']) ?></title>
    <?php require __DIR__ . '/../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/profile-style.css">
</head>
<body>
    <div class="d-flex">
        <?php
        $authService = getService('AuthService');
        include __DIR__ . '/../includes/sidebar.php';
        ?>
        <div class="content-wrapper p-4">
            <div class="profile-banner d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                <div class="me-md-4 mb-3 mb-md-0">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode(
                        $user['name'] . '+' . $user['prenume']
                    ) ?>&background=random&color=fff&size=100&font-size=0.40&bold=true" 
                         class="profile-avatar" 
                         alt="Profile Picture">
                </div>
                <div class="flex-grow-1">
                    <h1 class="mb-1"><?= htmlspecialchars($user['name'] . ' ' . $user['prenume']) ?></h1>
                    <p class="mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="mb-0 small"><i class="bi bi-calendar-check"></i> Membru din <?= date(
                        'd F Y',
                        strtotime($user['created_at'])
                    ) ?></p>
                </div>
                <div class="mt-3 mt-md-0 ms-md-auto">
                     <button class="btn btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="bi bi-pencil-square me-1"></i>Editează
                    </button>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card organizations h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bg me-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 stat-number" data-target="<?= count($organizations) ?>">0</h5>
                                    <p class="text-muted mb-0">Organizații</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card events h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bg me-3">
                                    <i class="bi bi-calendar-event-fill"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 stat-number" data-target="<?= $total_events_participated ?>">0</h5>
                                    <p class="text-muted mb-0">Evenimente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card admin-roles h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bg me-3">
                                    <i class="bi bi-person-check-fill"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 stat-number" data-target="<?= $total_admin_roles ?>">0</h5>
                                    <p class="text-muted mb-0">Roluri de conducere</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card worked-hours h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bg me-3">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 stat-number" data-target="<?= round(
                                        $total_worked_hours_all_orgs,
                                        1
                                    ) ?>">0</h5>
                                    <p class="text-muted mb-0" title="Numărul de ore lucrate în total la toate organizațiile">Ore lucrate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card-custom mb-4">
                <div class="card-header section-header">
                    <h3 class="section-title"><i class="bi bi-hourglass-split"></i>Istoric Ore Lucrate</h3>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($hours_data)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 hours-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Organizație</th>
                                        <th class="text-center">Ore Totale</th>
                                        <th class="text-center">Zile Lucrate</th>
                                        <th class="text-end pe-4">Medie Ore/Zi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hours_data as $org_id => $data): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium"><?= htmlspecialchars($data['org_name']) ?></td>
                                            <td class="text-center"><?= number_format($data['total_hours'], 1) ?></td>
                                            <td class="text-center"><?= $data['days_worked'] ?></td>
                                            <td class="text-end pe-4"><?= $data['days_worked'] > 0
                                                ? number_format($data['total_hours'] / $data['days_worked'], 1)
                                                : '0.0' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td class="ps-4">Total General</td>
                                        <td class="text-center"><?= number_format(
                                            $total_worked_hours_all_orgs,
                                            1
                                        ) ?></td>
                                        <td class="text-center"><?= $total_days_worked_all_orgs ?></td>
                                        <td class="text-end pe-4"><?= $total_days_worked_all_orgs > 0
                                            ? number_format(
                                                $total_worked_hours_all_orgs / $total_days_worked_all_orgs,
                                                1
                                            )
                                            : '0.0' ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-container p-4 p-md-5">
                            <i class="bi bi-emoji-frown display-icon"></i>
                            <h5 class="mt-3">Nu ai înregistrat ore lucrate încă</h5>
                            <p class="text-muted">Activitatea ta va apărea aici odată ce vei începe să înregistrezi ore.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-custom">
                 <div class="card-header section-header">
                    <h3 class="section-title"><i class="bi bi-buildings-fill"></i>Organizațiile Mele</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($organizations)): ?>
                        <div class="row g-3">
                            <?php foreach ($organizations as $org):
                                $org_hours_info = $hours_data[$org['id']] ?? ['total_hours' => 0]; ?>
                                <div class="col-lg-6">
                                    <div class="card org-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title"><?= htmlspecialchars($org['org_name']) ?></h5>
                                                <span class="badge <?= $org['role'] === 'owner'
                                                    ? 'bg-danger-subtle text-danger-emphasis'
                                                    : ($org['role'] === 'admin'
                                                        ? 'bg-info-subtle text-info-emphasis'
                                                        : 'bg-secondary-subtle text-secondary-emphasis') ?> role-badge text-uppercase">
                                                    <i class="bi <?= $org['role'] === 'owner'
                                                        ? 'bi-gem'
                                                        : ($org['role'] === 'admin'
                                                            ? 'bi-shield-check'
                                                            : 'bi-person') ?>"></i> <?= role_to_readable(
    $org['role']
) ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small"><?= htmlspecialchars(
                                                $org['description'] ?? 'Nicio descriere.'
                                            ) ?></p>
                                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-auto pt-2 border-top border-opacity-10">
                                                <div class="d-flex gap-2">
                                                    <span class="badge rounded-pill info-badge">
                                                        <i class="bi bi-calendar-week me-1"></i> <?= $org[
                                                            'events_participated'
                                                        ] ?> Evenimente
                                                    </span>
                                                    <?php if ($org_hours_info['total_hours'] > 0): ?>
                                                    <span class="badge rounded-pill info-badge hours">
                                                        <i class="bi bi-stopwatch me-1"></i> <?= number_format(
                                                            $org_hours_info['total_hours'],
                                                            1
                                                        ) ?> Ore
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted mt-2 mt-sm-0">Din: <?= date(
                                                    'd M. Y',
                                                    strtotime($org['join_date'])
                                                ) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-container p-4 p-md-5">
                             <i class="bi bi-compass display-icon"></i>
                            <h5 class="mt-3">Nu ești membru în nicio organizație</h5>
                            <p class="text-muted">Este timpul să explorezi și să te alături unei comunități!</p>
                            <a href="organizations.php" class="btn btn-primary mt-2"><i class="bi bi-search me-2"></i>Explorează Organizații</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div> </div> <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel"><i class="bi bi-person-lines-fill me-2"></i>Editează Detaliile Profilului</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProfileForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Nume</label>
                            <input type="text" class="form-control" id="editName" name="name" value="<?= htmlspecialchars(
                                $user['name']
                            ) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPrenume" class="form-label">Prenume</label>
                            <input type="text" class="form-control" id="editPrenume" name="prenume" value="<?= htmlspecialchars(
                                $user['prenume']
                            ) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" value="<?= htmlspecialchars(
                                $user['email']
                            ) ?>" required>
                        </div>
                         <div id="formErrorAlert" class="alert alert-danger d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anulează</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Salvează Modificările</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($update_success !== null): ?>
        Swal.fire({
            icon: <?= $update_success ? "'success'" : "'error'" ?>,
            title: <?= $update_success ? "'Profil Actualizat!'" : "'Oops... Ceva nu a mers bine!'" ?>,
            text: '<?= addslashes($update_message) ?>',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Am înțeles'
        });
        <?php endif; ?>

        const editProfileForm = document.getElementById('editProfileForm');
        const formErrorAlert = document.getElementById('formErrorAlert');

        if (editProfileForm) {
            editProfileForm.addEventListener('submit', function(event) {
                const name = editProfileForm.elements['name'].value.trim();
                const prenume = editProfileForm.elements['prenume'].value.trim();
                const email = editProfileForm.elements['email'].value.trim();
                let errorMessage = '';

                if (!name || !prenume) {
                    errorMessage = 'Numele și prenumele sunt câmpuri obligatorii.';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { 
                    errorMessage = 'Adresa de email introdusă nu este validă.';
                }

                if (errorMessage) {
                    event.preventDefault(); 
                    if(formErrorAlert) {
                        formErrorAlert.textContent = errorMessage;
                        formErrorAlert.classList.remove('d-none');
                    } else { 
                        Swal.fire('Eroare de Validare', errorMessage, 'error');
                    }
                } else {
                    if(formErrorAlert) formErrorAlert.classList.add('d-none');
                }
            });
        }

        const animateStatNumbers = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const targetValue = parseInt(el.dataset.target);
                    const duration = 1500; 
                    let start = 0;
                    let startTime = null;

                    function step(currentTime) {
                        if (!startTime) startTime = currentTime;
                        const progress = currentTime - startTime;
                        const currentVal = Math.min(Math.floor((progress / duration) * targetValue), targetValue);
                        el.textContent = currentVal;
                        if (progress < duration) {
                            requestAnimationFrame(step);
                        } else {
                            el.textContent = targetValue; 
                        }
                    }
                    requestAnimationFrame(step);
                    observer.unobserve(el); 
                }
            });
        };

        const statObserver = new IntersectionObserver(animateStatNumbers, {
            root: null, 
            threshold: 0.5 
        });

        document.querySelectorAll('.stat-number').forEach(el => {
            statObserver.observe(el);
        });
    });
    </script>
</body>
</html>