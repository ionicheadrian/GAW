<?php
namespace Services;
class PasswordService {
    public function hash(string $pass): string {
        return password_hash($pass, PASSWORD_DEFAULT);
    }
    public function verify(string $pass, string $hash): bool {
        return password_verify($pass, $hash);
    }
}
// gestioneaza operatiile legate de parole, gen hashing si verificare a acestora
