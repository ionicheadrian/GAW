<?php
require_once '../config/config.php';
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Neautorizat']);
    exit;
}
$user_info = get_user_info();
$user_id = intval($user_info['id']);
header('Content-Type: application/json');


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
echo json_encode(['success' => true]);
exit;
