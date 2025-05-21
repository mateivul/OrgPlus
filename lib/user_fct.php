<?php

function ensure_logged_in()
{
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id === 0) {
        header('Location: login.php');
        exit();
    }
    return $user_id;
}

function get_user_id()
{
    $user_id = $_SESSION['user_id'] ?? 0;
    return $user_id;
}

function ensure_existing_org()
{
    $org_id = $_SESSION['org_id'] ?? 0;
    if ($org_id === 0) {
        header('Location: login.php');
        exit();
    }
    return $org_id;
}

function user_org_role($user_id, $org_id)
{
    global $mysqli;

    $sql_role = 'SELECT role FROM roles WHERE user_id = ? AND org_id = ?';

    $stmt_role = $mysqli->prepare($sql_role);
    $stmt_role->bind_param('ii', $user_id, $org_id);
    $stmt_role->execute();

    $result_role = $stmt_role->get_result();
    $user_role_row = $result_role->fetch_assoc();

    if ($user_role_row) {
        $user_role = $user_role_row['role'];
    } else {
        $user_role = null;
    }
    $stmt_role->close();

    return $user_role;
}

function get_user_name($user_id)
{
    global $mysqli;

    $sql_user = 'SELECT name FROM users WHERE id = ?';
    $stmt_user = $mysqli->prepare($sql_user);
    $stmt_user->bind_param('i', $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $name = $user['name'] ?? 'Utilizator';
    return $name;
}

function role_to_readable($user_role)
{
    if ($user_role == 'owner') {
        return 'Președinte';
    }
    if ($user_role == 'admin') {
        return 'Administrator';
    }
    return 'Membru';
}
