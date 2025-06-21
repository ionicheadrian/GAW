<?php
require __DIR__.'/../bootstrap.php';
use Controllers\Auth\LogoutController;
header('Content-Type: application/json');
echo json_encode((new LogoutController($db))->handle());
