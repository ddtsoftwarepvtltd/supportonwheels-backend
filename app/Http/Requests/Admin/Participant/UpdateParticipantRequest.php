<?php

namespace App\Http\Requests\Admin\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('participant');

        return [
            'name'                 => 'sometimes|string|max:100',
            'email'                => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone'                => 'sometimes|string|max:20',
            'status'               => 'sometimes|in:active,inactive,blocked',
            'ndis_number'          => 'nullable|string|max:100',
            'plan_management_type' => 'nullable|in:self,plan_managed,ndia_managed',
            'address'              => 'nullable|string|max:255',
            'suburb'               => 'nullable|string|max:100',
            'state'                => 'nullable|string|max:50',
            'postcode'             => 'nullable|string|max:10',
            'latitude'             => 'nullable|numeric|between:-90,90',
            'longitude'            => 'nullable|numeric|between:-180,180',
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
