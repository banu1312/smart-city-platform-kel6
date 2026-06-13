<?php
namespace App\Validators;

class WasteValidator {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['zone_id']))     $errors[] = 'zone_id is required';
        if (!isset($data['fill_level'])) $errors[] = 'fill_level is required';
        if (isset($data['fill_level'])) {
            $fl = (float)$data['fill_level'];
            if ($fl < 0 || $fl > 100)
                $errors[] = 'fill_level must be between 0 and 100';
        }
        if (isset($data['gas_level']) && (float)$data['gas_level'] < 0)
            $errors[] = 'gas_level cannot be negative';
        return $errors;
    }
}