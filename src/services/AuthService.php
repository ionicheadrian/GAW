<?php
namespace Services;
use Repositories\UserRepository;
use Services\PasswordService;

class AuthService {
    private UserRepository $repo;
    private PasswordService $pwd;
    public function __construct(\PDO $db) {
        $this->repo = new UserRepository($db);
        $this->pwd  = new PasswordService();
    }
    public function login(string $email, string $pass): array {
        $user = $this->repo->findByEmail($email);
        if (!$user || !$this->pwd->verify($pass, $user['password'])) {
            http_response_code(401);
            return ['error'=>'Email-ul sau parola incorecte'];
        }
        session_regenerate_id();
        $_SESSION['user'] = [
            'id'=>$user['id'],
            'role'=>$user['role'],
            'name'=>$user['full_name']
        ];
        return ['ok'=>true];
    }
    public function register(array $data): array {
        $hash = $this->pwd->hash($data['password']);
        return $this->repo->createUser($data['full_name'], $data['email'], $hash);
    }
    public function logout(): void {
        session_unset();
        session_destroy();
    }
}
