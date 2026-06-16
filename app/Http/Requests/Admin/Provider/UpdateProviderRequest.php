<?php

namespace App\Http\Requests\Admin\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('provider');

        return [
            'name'                     => 'sometimes|string|max:100',
            'email'                    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone'                    => 'sometimes|string|max:20',
            'status'                   => 'sometimes|in:active,inactive,blocked',
            'organisation_name'        => 'nullable|string|max:150',
            'abn'                      => 'nullable|string|max:20',
            'is_ndis_registered'       => 'nullable|boolean',
            'ndis_registration_number' => 'nullable|string|max:100',
            'address'                  => 'nullable|string|max:255',
            'suburb'                   => 'nullable|string|max:100',
            'state'                    => 'nullable|string|max:50',
            'postcode'                 => 'nullable|string|max:10',
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
