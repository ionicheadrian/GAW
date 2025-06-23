<?php
require_once '../config/config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_info = get_user_info();
$user_id = intval($user_info['id']);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $delete_deposits = mysqli_prepare($connection, "DELETE FROM waste_deposits WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_deposits, "i", $user_id);
    mysqli_stmt_execute($delete_deposits);
    mysqli_stmt_close($delete_deposits);

    $delete_reports = mysqli_prepare($connection, "DELETE FROM reports WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_reports, "i", $user_id);
    mysqli_stmt_execute($delete_reports);
    mysqli_stmt_close($delete_reports);

    $delete_user = mysqli_prepare($connection, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($delete_user, "i", $user_id);
    mysqli_stmt_execute($delete_user);
    mysqli_stmt_close($delete_user);

    session_destroy();
    header('Location: ../templates/home.php?deleted=1');
    exit;
}
?>
