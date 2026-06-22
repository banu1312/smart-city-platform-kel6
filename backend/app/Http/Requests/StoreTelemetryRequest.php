<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTelemetryRequest extends FormRequest {
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'bin_id'             => 'required|string|exists:trash_bins,bin_code',
            'fill_level'         => 'required|numeric|min:0|max:100',
            'gas_level'          => 'nullable|numeric|min:0',
            'temperature'        => 'nullable|numeric',
            'calibrated_height'  => 'nullable|numeric|min:1',
            'is_calibration'     => 'nullable|boolean',
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
        ];
    }

    protected function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'status'    => 'error',
            'code'      => 422,
            'errors'    => $validator->errors()->all(),
            'timestamp' => now()->toIso8601String(),
            'service'   => 'smart-bin-service',
        ], 422));
    }
}
