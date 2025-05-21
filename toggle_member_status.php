<?php
require 'lib/conn_db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Cerere invalidă']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input)) {
    echo json_encode(['error' => true, 'message' => 'Date invalide']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$member_id = intval($input['member_id'] ?? 0);
$org_id = intval($input['org_id'] ?? 0);
$new_status = $input['new_status'] === '1' ? 1 : 0;

$user_role = user_org_role($user_id, $org_id);
if (!in_array($user_role, ['admin', 'owner'])) {
    echo json_encode(['error' => true, 'message' => 'Permisiuni insuficiente']);
    exit();
}

$stmt = $mysqli->prepare('UPDATE roles SET is_active = ? WHERE user_id = ? AND org_id = ?');
$stmt->bind_param('iii', $new_status, $member_id, $org_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Statusul membru a fost actualizat cu succes!']);
} else {
    echo json_encode(['error' => true, 'message' => 'Eroare la actualizarea statusului: ' . $mysqli->error]);
}
?>
