<?php
namespace App\Validators;

class ReportValidator {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['title'])) $errors[] = 'title is required';
        return $errors;
    }
}