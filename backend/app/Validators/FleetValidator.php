<?php
namespace App\Validators;

class FleetValidator {
    public static function validateDispatch(array $data): array {
        $errors = [];
        if (empty($data['truck_id'])) $errors[] = 'truck_id is required';
        if (empty($data['bin_id']))   $errors[] = 'bin_id is required';
        if (empty($data['zone_id']))  $errors[] = 'zone_id is required';
        return $errors;
    }

    public static function validateStatus(array $data): array {
        $allowed = ['assigned','on_the_way','arrived','completed','cancelled'];
        $errors  = [];
        if (empty($data['status']))
            $errors[] = 'status is required';
        if (!empty($data['status']) && !in_array($data['status'], $allowed))
            $errors[] = 'status must be one of: ' . implode(', ', $allowed);
        return $errors;
    }
}