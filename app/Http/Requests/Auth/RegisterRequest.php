<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8|confirmed', // password_confirmation bhi chahiye
            'phone'     => 'required|string|max:20',
            'user_type' => 'nullable|in:worker,provider,participant',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Naam zaroori hai',
            'email.required'     => 'Email zaroori hai',
            'email.unique'       => 'Ye email pehle se registered hai',
            'password.min'       => 'Password kam se kam 8 characters ka hona chahiye',
            'password.confirmed' => 'Password aur Confirm Password match nahi kar rahe',
            'phone.required'     => 'Phone number zaroori hai',
            'user_type.required' => 'User type zaroori hai',
            'user_type.in'       => 'User type sirf worker, provider ya participant ho sakta hai',
        ];
    }

    // Validation fail hone par JSON response
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
