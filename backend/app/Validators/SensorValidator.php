<?php
namespace App\Validators;

class SensorValidator {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['zone_id'])) $errors[] = 'zone_id is required';
        if (isset($data['humidity'])) {
            $h = (float)$data['humidity'];
            if ($h < 0 || $h > 100)
                $errors[] = 'humidity must be between 0 and 100';
        }
        return $errors;
    }
}