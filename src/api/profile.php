<?php
require __DIR__.'/../bootstrap.php';
use Controllers\User\ProfileController;
session_start();
header('Content-Type: application/json');
$ctrl=new ProfileController($db);
if($_SERVER['REQUEST_METHOD']==='GET')    echo json_encode($ctrl->getProfile($_SESSION['user']['id']));
elseif($_SERVER['REQUEST_METHOD']==='PUT') echo json_encode($ctrl->updateProfile($_SESSION['user']['id'],jsonInput()));
else http_response_code(405);
