<?php
namespace Controllers\Auth;
use Services\AuthService;
use Validators\UserValidator;

class RegisterController {
    private AuthService $auth;
    public function __construct(\PDO $db) {
        $this->auth = new AuthService($db);
    }
    public function handle(array $input): array {
        try {
            $data = UserValidator::signup($input);
            return $this->auth->register($data);
        } catch (\Exception $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }
}
