<?php
namespace Controllers\User;
use Services\UserService;

class ProfileController {
    private UserService $service;
    public function __construct(\PDO $db) {
        $this->service = new UserService($db);
    }
    public function getProfile(int $id): array {
        $me = $_SESSION['user']['id'] ?? null;
        if ($me !== $id) {
            http_response_code(403); // eroare la nivel de client - forbidden n-ai voie sa accesezi resursa
            return ['error'=>'Nu e voie!'];
        }
        return $this->service->get($id);
    }
    public function updateProfile(int $id, array $input): array {
        $me = $_SESSION['user']['id'] ?? null;
        if ($me !== $id) {
            http_response_code(403);
            return ['error'=>'Nu e voie!'];
        }
        return $this->service->update($id, $input);
    }
}
