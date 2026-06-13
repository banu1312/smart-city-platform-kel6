<?php
namespace App\Validators;

class ReportValidator {
    private static array $allowedCategories = [
        'overflow', 'fire_hazard', 'missed_pickup', 'illegal_dumping'
    ];

    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['citizen_id'])) $errors[] = 'citizen_id is required';
        if (empty($data['category']))   $errors[] = 'category is required';
        if (!empty($data['category']) && !in_array($data['category'], self::$allowedCategories))
            $errors[] = 'category must be one of: ' . implode(', ', self::$allowedCategories);
        return $errors;
    }
}