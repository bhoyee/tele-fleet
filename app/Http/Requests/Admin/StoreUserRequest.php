<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\TextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = TextNormalizer::personName($this->input('name'));
        $phone = TextNormalizer::phoneE164($this->input('phone'));

        $this->merge([
            'name' => $name,
            'phone' => is_string($phone) && trim($phone) === '' ? null : $phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^\\p{L}+(?:[\\s\'-]\\p{L}+)*$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_FLEET_MANAGER,
                User::ROLE_BRANCH_HEAD,
                User::ROLE_BRANCH_ADMIN,
            ])],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
