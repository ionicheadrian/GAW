<?php
namespace Services;
use Repositories\UserRepository;

class UserService {
    private UserRepository $repo;
    public function __construct(\PDO $db) {
        $this->repo = new UserRepository($db);
    }
    public function list(): array {
        return $this->repo->listAll();
    }
    public function get(int $id): array {
        return $this->repo->findById($id);
    }
    public function update(int $id, array $input): array {
        return $this->repo->updateUser($id, $input);
    }
    public function delete(int $id): array {
        return $this->repo->deleteUser($id);
    }
}
