<?php
namespace Controllers\Auth;

use Services\AuthService;

class LogoutController
{
    private $auth;

    public function __construct(\PDO $db)
    {
        $this->auth = new AuthService($db);
    }

    public function handle()
    {
        $this->auth->logout();
        return ['ok' => true];
    }
}
