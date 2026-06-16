<?php

namespace App\Http\Requests\Admin\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:100',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|string|min:8',
            'phone'                => 'required|string|max:20',
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
