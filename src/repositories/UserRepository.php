<?php
namespace Repositories;
class UserRepository {
    private \PDO $db;
    public function __construct(\PDO $db) { $this->db = $db; } // primeste obiectul PDO generat in database.php

    public function findByEmail(string $email): ?array {
        $st = $this->db->prepare('SELECT * FROM users WHERE email=?');
        $st->execute([$email]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    public function createUser(string $name, string $email, string $hash): array {
        $st = $this->db->prepare(
            'INSERT INTO users(full_name, email, password) VALUES(?,?,?)'
        );
        $st->execute([$name, $email, $hash]);
        return ['ok'=>true, 'id'=>$this->db->lastInsertId()];
    }
    public function listAll(): array {
        $st = $this->db->query('SELECT id,full_name,email,role,created_at FROM users');
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function findById(int $id): ?array {
        $st = $this->db->prepare('SELECT id,full_name,email,role,created_at FROM users WHERE id=?');
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    public function updateUser(int $id, array $input): array {
        // Ex: update doar nume/email, etc
        $fields = [];
        $vals = [];
        foreach (['full_name','email','phone','address','password'] as $k) {
            if (isset($input[$k])) { $fields[] = "$k=?"; $vals[] = $input[$k]; }
        }
        if (!$fields) {
            return ['ok'=>false,'msg'=>'Nimic de actualizat'];
        }
        $vals[] = $id;
        $sql = 'UPDATE users SET '.implode(',', $fields).' WHERE id=?';
        $st = $this->db->prepare($sql);
        $st->execute($vals);
        return ['ok'=>true];
    }
    public function deleteUser(int $id): array {
        $st = $this->db->prepare('DELETE FROM users WHERE id=?');
        $st->execute([$id]);
        return ['ok'=>true];
    }
}
