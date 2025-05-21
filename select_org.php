<?php
require 'lib/conn_db.php';

$user_id = ensure_logged_in();

if (empty($_GET['org_id'])) {
    header('Location: index.php');
    exit();
}
$org_id = intval($_GET['org_id']);
$_SESSION['org_id'] = $org_id;

header('Location: dashboard.php');
