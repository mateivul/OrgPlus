<?php

require_once __DIR__ . '/../src/config.php';

$authService = getService(AuthService::class);
$organizationRepository = getService(OrganizationRepository::class);
$requestRepository = getService(RequestRepository::class);

$currentUser = $authService->getCurrentUser();
$user_id = $currentUser ? $currentUser->id : null;

$response_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_org_id'])) {
    if (!$currentUser) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'Trebuie să fii autentificat pentru a trimite o cerere de aderare.',
        ]);
        exit();
    }

    if (!isset($_POST['csrf_token']) || !CsrfToken::validateToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Eroare CSRF: Cerere invalidă!']);
        exit();
    }

    $org_id = intval($_POST['enroll_org_id']);
    $response = ['error' => false, 'success' => false, 'warning' => false, 'message' => ''];

    if ($requestRepository->hasPendingOrAcceptedJoinRequest($user_id, $org_id)) {
        $response['warning'] = true;
        $response['message'] = 'Ai deja o cerere în așteptare pentru această organizație sau ești deja membru.';
    } else {
        if ($requestRepository->createJoinRequest($user_id, $org_id)) {
            $response['success'] = true;
            $response['message'] = 'Cererea de aderare a fost trimisă cu succes!';
        } else {
            $response['error'] = true;
            $response['message'] = 'Eroare la trimiterea cererii de aderare.';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$organizations = [];

if ($currentUser) {
    $organizations = $organizationRepository->findAllOrganizationsWithUserStatus($user_id);
} else {
    $organizations = $organizationRepository->findAll();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizații</title>
    <?php require '../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/index-style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="d-flex">
<?php require '../includes/sidebar.php'; ?>
        <div class="my-content">
            <h3 class="mb-4">Organizații active</h3>
            <div class="my-row">
                <?php if (empty($organizations)) {
                    echo '<p class="text-muted">Nu există organizații disponibile în acest moment.</p>';
                } else {
                    foreach ($organizations as $org): ?>
                        <div class="my-card">
                            <a class="my-card-body" href="select_org.php?org_id=<?php echo $org->id; ?>">
                                <div class="my-card-body-text">
                                    <div>
                                        <span class="card-title"><?php echo htmlspecialchars($org->name); ?></span>
                                    </div>
                                    <p class="card-description"><?php echo htmlspecialchars($org->description); ?></p>
                                </div>
                                <div class="my-card-body-arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 330 330">
                                        <path d="M253.606 151.389l-150-149.996c-7.15-7.15-18.12-7.15-25.27 0-7.15 7.15-7.15 18.12 0 25.27l136.393 136.39-136.393 136.394c-7.15 7.15-7.15 18.12 0 25.27 3.575 3.575 8.26 5.363 12.945 5.363s9.37-1.788 12.945-5.363l149.999-150.004c3.85-3.85 5.775-8.975 5.775-14.1 0-5.125-1.925-10.25-5.775-14.1z" fill="var(--text-color)"/>
                                    </svg>
                                </div>
                            </a>

                            <div class="my-card-footer">
                                <button
                                    class="details-button footer-button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#orgModal<?php echo $org->id; ?>">
                                    Detalii
                                </button>
                                <?php if ($org->isEnrolled): ?>
                                    <span class="role">
                                        Rol:
                                        <b>
                                            <?php echo role_to_readable($org->userRole); ?>
                                        </b>
                                    </span>
                                <?php else: ?>
                                    <?php if ($org->isRequestPending): ?>
                                        <span class="request-status">
                                            <i class="fas fa-clock"></i>
                                            Cerere <br>în așteptare
                                        </span>
                                    <?php else: ?>
                                        <?php if ($currentUser): ?>
                                            <form method="POST" class="join-form footer-button">
                                                <input type="hidden" name="enroll_org_id" value="<?php echo $org->id; ?>">
                                                <?= CsrfToken::csrfField() ?>
                                                <button type="submit" class="join-form-button">
                                                    Alătură-te
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="request-status">Loghează-te pentru a adera</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal fade" id="orgModal<?php echo $org->id; ?>" tabindex="-1" aria-labelledby="orgModalLabel<?php echo $org->id; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="orgModalLabel<?php echo $org->id; ?>">
                                            <?php echo htmlspecialchars($org->name); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><?php echo htmlspecialchars($org->description); ?></p>
                                        <?php if ($org->website): ?>
                                            <p><a href="<?php echo htmlspecialchars(
                                                $org->website
                                            ); ?>" target="_blank">Vizitează site-ul</a></p>
                                        <?php endif; ?>
                                        <p>Membri: <?php echo $org->memberCount; ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php endforeach;
                } ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const enrollForms = document.querySelectorAll(".join-form");

            enrollForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();

                    const formData = new FormData(this);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            console.error("Răspunsul nu este JSON:", response);
                            return response.text().then(text => {
                                console.error("Detalii răspuns (text):", text);
                                throw new Error("Răspuns invalid de la server.");
                            });
                        }
                    })
                    .then(data => {
                        if (data.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Eroare!',
                                text: data.message,
                                confirmButtonText: 'OK'
                            });
                        } else if (data.success) {
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
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Eroare!',
                            text: 'A apărut o eroare neașteptată. Vezi consola pentru detalii.',
                        });
                    });
                });
            });
        });

        document.querySelectorAll('.hover-card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = card.getBoundingClientRect();
                this.style.setProperty('--x', `${e.clientX - rect.left}px`);
                this.style.setProperty('--y', `${e.clientY - rect.top}px`);
            });
        });
    </script>
</body>
</html>