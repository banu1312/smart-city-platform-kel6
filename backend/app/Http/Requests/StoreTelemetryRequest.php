<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTelemetryRequest extends FormRequest {
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'trash_bin_id' => 'required|integer|exists:trash_bins,id',
            'distance_cm'  => 'required|numeric|min:0|max:400',
            'methane_ppm'  => 'nullable|numeric|min:0',
            'temperature_c'=> 'nullable|numeric',
            'raw_payload'  => 'nullable|array',
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