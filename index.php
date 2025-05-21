<?php
require 'lib/conn_db.php';

$user_id = get_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_org_id'])) {
    $org_id = intval($_POST['enroll_org_id']);
    $response = ['error' => false, 'success' => false, 'warning' => false, 'message' => ''];

    $sql_check =
        "SELECT 1 FROM requests WHERE sender_user_id = ? AND organization_id = ? AND request_type = 'organization_join_request' AND status IN ('pending', 'accepted')";
    $stmt_check = $mysqli->prepare($sql_check);
    $uid = $user_id;
    $oid = $org_id;
    $stmt_check->bind_param('ii', $uid, $oid);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        $sql_request =
            "INSERT INTO requests (request_type, sender_user_id, organization_id, status) VALUES (?, ?, ?, 'pending')";
        $stmt_request = $mysqli->prepare($sql_request);
        $request_type = 'organization_join_request';
        $suid = $user_id;
        $soid = $org_id;
        $stmt_request->bind_param('sii', $request_type, $suid, $soid);
        if ($stmt_request->execute()) {
            $response['success'] = true;
            $response['message'] = 'Cererea de aderare a fost trimisă cu succes!';
        } else {
            $response['error'] = true;
            $response['message'] = 'Eroare la trimiterea cererii de aderare.';
        }
    } else {
        $response['warning'] = true;
        $response['message'] = 'Ai deja o cerere în așteptare pentru această organizație sau ești deja membru.';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$sql = "SELECT o.id, o.name, o.description, o.website, r.role,
            CASE WHEN r.user_id IS NOT NULL THEN 1 ELSE 0 END AS enrolled,
            (SELECT 1 FROM requests WHERE sender_user_id = ? AND organization_id = o.id AND request_type = 'organization_join_request' AND status = 'pending') AS request_pending,
            (SELECT COUNT(*) FROM roles WHERE org_id = o.id) AS member_count
        FROM organizations o
        LEFT JOIN roles r ON o.id = r.org_id AND r.user_id = ?
        ORDER BY 
            enrolled DESC, 
            request_pending DESC,
            CASE WHEN enrolled = 1 OR request_pending = 1 THEN o.created_at ELSE NULL END DESC,
            member_count DESC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizații</title>
    <?php require 'utils/global.html'; ?>
    <link rel="stylesheet" href="styles/index-style.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'utils/sidebar.php'; ?>

        <div class="my-content">
            <h3 class="mb-4">Organizații active</h3>
            <div class="my-row">
                <?php while ($org = $result->fetch_assoc()): ?>
                    <div class="my-card">
                        
                        <a class="my-card-body" href="select_org.php?org_id=<?php echo $org['id']; ?>" >
                            <div class="my-card-body-text">
                                <div>
                                    <span class="card-title"><?php echo htmlspecialchars($org['name']); ?></span>
                                </div>
                                <p class="card-description"><?php echo htmlspecialchars($org['description']); ?></p>
                            </div>
                            <div class="my-card-body-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 330 330">
                                    <path d="M253.606 151.389l-150-149.996c-7.15-7.15-18.12-7.15-25.27 0-7.15 7.15-7.15 18.12 0 25.27l136.393 136.39-136.393 136.394c-7.15 7.15-7.15 18.12 0 25.27 3.575 3.575 8.26 5.363 12.945 5.363s9.37-1.788 12.945-5.363l149.999-150.004c3.85-3.85 5.775-8.975 5.775-14.1 0-5.125-1.925-10.25-5.775-14.1z" fill="var(--text-color)"/>
                                </svg>
                            </div>
                            <!-- <?php echo parse_url($org['website'], PHP_URL_HOST); ?> -->

                </a>
                        
                        <div class="my-card-footer">
                            <button
                                class="details-button footer-button" 
                                data-bs-toggle="modal" 
                                data-bs-target="#orgModal<?php echo $org['id']; ?>">
                                Detalii
                            </button>
                            <?php if ($org['enrolled']): ?>
                                <span class="role">
                                    Rol:
                                    <b>
                                        <?php echo role_to_readable($org['role']); ?>
                                    </b>
                                </span>
                            <?php else: ?>
                                <?php if ($org['request_pending']): ?>
                                    <span class="request-status">
                                        <i class="fas fa-clock"></i>
                                        Cerere <br>în așteptare
                                    </span>
                                <?php else: ?>
                                    <form method="POST" class="join-form footer-button">
                                        <input type="hidden" name="enroll_org_id" value="<?php echo $org['id']; ?>">
                                        <button type="submit" class="join-form-button">
                                            Alătură-te
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="modal fade" id="orgModal<?php echo $org[
                        'id'
                    ]; ?>" tabindex="-1" aria-labelledby="orgModalLabel<?php echo $org['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="orgModalLabel<?php echo $org['id']; ?>">
                                        <?php echo htmlspecialchars($org['name']); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><?php echo htmlspecialchars($org['description']); ?></p>
                                    <p><a href="<?php echo htmlspecialchars(
                                        $org['website']
                                    ); ?>" target="_blank">Vizitează site-ul</a></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
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
                    .then(response => response.json())
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
                            text: 'A apărut o eroare neașteptată.',
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