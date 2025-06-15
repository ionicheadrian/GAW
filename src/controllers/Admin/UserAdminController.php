<?php
namespace Controllers\Admin;
use Services\UserService;

class UserAdminController {
    private UserService $service;
    public function __construct(\PDO $db) {
        $this->service = new UserService($db);
    }
    private function requireAdmin() {
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit(json_encode(['error'=>'Acces interzis']));
        }
    }
    public function list(): array {
        $this->requireAdmin();
        return $this->service->list();
    }
    public function get(int $id): array {
        $this->requireAdmin();
        return $this->service->get($id);
    }
    public function update(int $id, array $input): array {
        $this->requireAdmin();
        return $this->service->update($id, $input);
    }
    public function delete(int $id): array {
        $this->requireAdmin();
        return $this->service->delete($id);
    }
}
