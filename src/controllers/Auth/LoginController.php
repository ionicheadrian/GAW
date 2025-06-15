<?php
namespace Controllers\Auth;
use Services\AuthService;
class LoginController
{
    private AuthService $auth;
    public function __construct(\PDO $db)
    {
        $this->auth = new AuthService($db);
    }

    public function handle(array $input): array
    {
        // aicea doar validare minima + delegation
        if (empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            return ['error'=>'Date lipsă'];
        }
        return $this->auth->login($input['email'], $input['password']);
    }
}
