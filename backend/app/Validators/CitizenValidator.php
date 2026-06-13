<?php
namespace App\Validators;

class CitizenValidator {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['nik']))      $errors[] = 'nik is required';
        if (empty($data['name']))     $errors[] = 'name is required';
        if (empty($data['email']))    $errors[] = 'email is required';
        if (empty($data['password'])) $errors[] = 'password is required';
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            $errors[] = 'email format is invalid';
        if (!empty($data['nik']) && strlen($data['nik']) !== 16)
            $errors[] = 'nik must be exactly 16 digits';
        if (!empty($data['password']) && strlen($data['password']) < 6)
            $errors[] = 'password must be at least 6 characters';
        return $errors;
    }
}