<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AutoDispatchRequest extends FormRequest {
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'trash_bin_id'     => 'required|integer|exists:trash_bins,id',
            'pickup_priority'  => 'required|in:Low,Medium,Urgent,Critical',
            'hours_until_full' => 'required|numeric|min:0',
        ];
    }

    protected function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'status'    => 'error',
            'code'      => 422,
            'errors'    => $validator->errors()->all(),
            'timestamp' => now()->toIso8601String(),
            'service'   => 'fleet-service',
        ], 422));
    }
}