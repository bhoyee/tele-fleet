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
        $rawPhone = $this->input('phone');
        $rawPhoneText = is_string($rawPhone) ? $rawPhone : '';
        $rawPhoneTrimmed = trim($rawPhoneText);

        if ($rawPhoneTrimmed === '') {
            $phone = null;
        } elseif (preg_match('/[A-Za-z]/', $rawPhoneText) === 1) {
            $phone = '__invalid__';
        } else {
            $phone = preg_replace('/\D+/', '', $rawPhoneText) ?? '';
        }

        $this->merge([
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    public function rules(): array
    {
        $role = (string) $this->input('role', '');
        $requiresBranch = in_array($role, [User::ROLE_BRANCH_HEAD, User::ROLE_BRANCH_ADMIN], true);

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^\\p{L}+(?:[\\s\'-]\\p{L}+)*$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\\d{10,15}$/'],
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_FLEET_MANAGER,
                User::ROLE_BRANCH_HEAD,
                User::ROLE_BRANCH_ADMIN,
            ])],
            'branch_id' => $requiresBranch
                ? ['required', 'integer', 'exists:branches,id']
                : ['nullable', 'integer', 'exists:branches,id'],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name must contain only letters and spaces (e.g. "Ade Boye" or "O\'Connor").',
            'phone.regex' => 'Phone number must contain only digits (10–15 digits). Example: 08065428869.',
        ];
    }
}
