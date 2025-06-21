<?php
require __DIR__.'/../bootstrap.php';
use Controllers\Auth\RegisterController;
header('Content-Type: application/json');
echo json_encode((new RegisterController($db))->handle(jsonInput()));
