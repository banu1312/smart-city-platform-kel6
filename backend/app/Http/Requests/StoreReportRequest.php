<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReportRequest extends FormRequest {
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'reporter_name'   => 'required|string|max:100',
            'reporter_phone'  => 'nullable|string|max:20',
            'issue_description'=> 'nullable|string',
            'photo'           => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'geo_coordinate'  => 'required|string',
        ];
    }

    protected function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'status'    => 'error',
            'code'      => 422,
            'errors'    => $validator->errors()->all(),
            'timestamp' => now()->toIso8601String(),
            'service'   => 'citizen-report-service',
        ], 422));
    }
}