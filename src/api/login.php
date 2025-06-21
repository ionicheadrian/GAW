<?php
require __DIR__.'/../bootstrap.php';
use Controllers\Auth\LoginController;
header('Content-Type: application/json');
echo json_encode((new LoginController($db))->handle(jsonInput()));
