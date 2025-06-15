<?php
namespace Validators;

class InvalidEmailException extends \Exception {}

class UserValidator {
    public static function signup(array $d): array {
        if (!filter_var($d['email']??'', FILTER_VALIDATE_EMAIL))
            throw new InvalidEmailException('Email invalid');
        if (strlen($d['password']??'')<6)
            throw new \Exception('Parola prea scurta');
        if (strlen($d['full_name']??'')<2)
            throw new \Exception(message: 'Nume invalid');
        return $d;
    }
}
