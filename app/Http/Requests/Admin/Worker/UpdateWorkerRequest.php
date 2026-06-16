<?php

namespace App\Http\Requests\Admin\Worker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('worker');

        return [
            'name'                  => 'sometimes|string|max:100',
            'email'                 => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone'                 => 'sometimes|string|max:20',
            'status'                => 'sometimes|in:active,inactive,blocked',
            'address'               => 'nullable|string|max:255',
            'suburb'                => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:50',
            'postcode'              => 'nullable|string|max:10',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'ndis_screening_number' => 'nullable|string|max:100',
            'ndis_expiry'           => 'nullable|date',
            'first_aid_cert'        => 'nullable|string|max:100',
            'first_aid_expiry'      => 'nullable|date',
            'is_online'             => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
