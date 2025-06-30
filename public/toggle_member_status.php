<?php
require_once __DIR__ . '/../src/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Cerere invalidă']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (
    empty($input) ||
    !isset($input['action']) ||
    $input['action'] !== 'toggle_member_status' ||
    !isset($input['member_id'], $input['org_id'], $input['new_status'])
) {
    echo json_encode(['error' => true, 'message' => 'Date invalide']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$member_id = intval($input['member_id']);
$org_id = intval($input['org_id']);
$new_status = $input['new_status'] === '1' ? 1 : 0;

$user_role = user_org_role($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    echo json_encode(['error' => true, 'message' => 'Permisiuni insuficiente']);
    exit();
}

$roleRepo = getService('RoleRepository');
$updated = $roleRepo->updateMemberStatus($member_id, $org_id, $new_status);

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Statusul membru a fost actualizat cu succes!']);
} else {
    echo json_encode(['error' => true, 'message' => 'Eroare la actualizarea statusului.']);
}
