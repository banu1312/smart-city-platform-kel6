<?php
namespace App\Validators;

class BinValidator {
    public static function validateCreate(array $data): array {
        $errors = [];
        if (empty($data['zone_id']))  $errors[] = 'zone_id is required';
        if (empty($data['location'])) $errors[] = 'location is required';
        return $errors;
    }

    public static function validateTelemetry(array $data): array {
        $errors = [];
        if (empty($data['bin_id']))      $errors[] = 'bin_id is required';
        if (empty($data['zone_id']))     $errors[] = 'zone_id is required';
        if (!isset($data['fill_level'])) $errors[] = 'fill_level is required';
        if (isset($data['fill_level'])) {
            $fl = (float)$data['fill_level'];
            if ($fl < 0 || $fl > 100)
                $errors[] = 'fill_level must be between 0 and 100';
        }
        return $errors;
    }
}