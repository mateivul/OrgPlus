<?php
require_once __DIR__ . '/../src/config.php';

$logged_in_user_id = ensure_logged_in();

if (empty($_GET['user_id'])) {
    header('Location: index.php');
    exit();
}

$profile_user_id = intval($_GET['user_id']);

if ($profile_user_id === $logged_in_user_id) {
    header('Location: profile.php');
    exit();
}

$sql_public_user = 'SELECT id, name, prenume, email, created_at FROM users WHERE id = ?';
$stmt_public_user = $pdo->prepare($sql_public_user);
$stmt_public_user->execute([$profile_user_id]);
$public_user = $stmt_public_user->fetch(PDO::FETCH_ASSOC);

if (!$public_user) {
    $_SESSION['error_message'] = 'Utilizatorul solicitat nu a fost găsit.';
    header('Location: index.php');
    exit();
}

$sql_common_orgs = "SELECT o.id AS org_id, o.name AS org_name, o.description AS org_description
                    FROM roles r1
                    JOIN roles r2 ON r1.org_id = r2.org_id
                    JOIN organizations o ON r1.org_id = o.id
                    WHERE r1.user_id = ? AND r2.user_id = ?
                    ORDER BY o.name ASC";
$stmt_common_orgs = $pdo->prepare($sql_common_orgs);
$stmt_common_orgs->execute([$logged_in_user_id, $profile_user_id]);
$common_organizations_data = $stmt_common_orgs->fetchAll(PDO::FETCH_ASSOC);

$hours_in_common_orgs = [];
if (!empty($common_organizations_data)) {
    $common_org_ids = array_column($common_organizations_data, 'org_id');
    $placeholders = implode(',', array_fill(0, count($common_org_ids), '?'));
    if (!empty($placeholders)) {
        $sql_hours = "SELECT wh.org_id, SUM(wh.hours) AS total_hours
                      FROM worked_hours wh
                      WHERE wh.user_id = ? AND wh.org_id IN ($placeholders)
                      GROUP BY wh.org_id";
        $params = array_merge([$profile_user_id], $common_org_ids);
        $stmt_hours = $pdo->prepare($sql_hours);
        $stmt_hours->execute($params);
        while ($row = $stmt_hours->fetch(PDO::FETCH_ASSOC)) {
            $hours_in_common_orgs[$row['org_id']] = $row['total_hours'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Public - <?= htmlspecialchars($public_user['name'] . ' ' . $public_user['prenume']) ?></title>
    <?php require __DIR__ . '/../includes/global.html'; ?>
    <link rel="stylesheet" href="styles/profile-style.css">
</head>
<body>
    <div class="d-flex">
        <?php
        $authService = getService('AuthService');
        include __DIR__ . '/../includes/sidebar.php';
        ?>
        <div class="content-wrapper p-3 p-md-4">
            <div class="profile-banner d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                <div class="me-md-3 mb-3 mb-md-0 flex-shrink-0">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode(
                        $public_user['name'] . '+' . $public_user['prenume']
                    ) ?>&background=random&color=fff&size=90&font-size=0.40&bold=true" 
                         class="profile-avatar" 
                         alt="Avatar <?= htmlspecialchars($public_user['name']) ?>">
                </div>
                <div class="flex-grow-1">
                    <h2 class="mb-1"><?= htmlspecialchars($public_user['name'] . ' ' . $public_user['prenume']) ?></h2>
                    <p class="mb-1"><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars(
                        $public_user['email']
                    ) ?></p>
                    <?php if (isset($public_user['created_at'])): ?>
                    <p class="mb-0 small"><i class="bi bi-person-plus-fill"></i> Membru din <?= date(
                        'F Y',
                        strtotime($public_user['created_at'])
                    ) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-custom">
                <div class="card-header section-header">
                    <h3 class="section-title"><i class="bi bi-diagram-3-fill"></i>Organizații Comune</h3>
                     <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary btn-back">
                        <i class="bi bi-arrow-left-circle me-1"></i>Înapoi
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($common_organizations_data)): ?>
                        <div class="row g-3">
                            <?php foreach ($common_organizations_data as $org):
                                $hours_in_this_org = $hours_in_common_orgs[$org['org_id']] ?? 0; ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card org-card-public">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?= htmlspecialchars($org['org_name']) ?></h5>
                                            <p class="org-description text-muted">
                                                <?= htmlspecialchars(
                                                    $org['org_description'] ??
                                                        'Nicio descriere disponibilă pentru această organizație.'
                                                ) ?>
                                            </p>
                                            <div class="mt-auto">
                                                <?php if ($hours_in_this_org > 0): ?>
                                                    <span class="badge rounded-pill info-badge">
                                                        <i class="bi bi-clock-history me-1"></i>
                                                        <?= number_format(
                                                            $hours_in_this_org,
                                                            1
                                                        ) ?> ore lucrate de <?= htmlspecialchars(
     $public_user['prenume']
 ) ?> aici
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill info-badge">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        Nu sunt ore înregistrate de <?= htmlspecialchars(
                                                            $public_user['prenume']
                                                        ) ?> în această org. comună
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-container p-3">
                             <i class="bi bi-person-x-fill display-icon"></i>
                            <h5 class="mt-3">Nicio organizație în comun</h5>
                            <p class="text-muted">Tu și <?= htmlspecialchars(
                                $public_user['name'] . ' ' . $public_user['prenume']
                            ) ?> nu sunteți membri în aceleași organizații.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>