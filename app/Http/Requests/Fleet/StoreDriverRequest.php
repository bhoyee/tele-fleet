<?php

namespace App\Http\Requests\Fleet;

use App\Support\TextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        $note = TextNormalizer::collapseWhitespace($this->input('note'));
        if ($status === 'active') {
            $note = null;
        }

        $email = $this->input('email');
        if (is_string($email)) {
            $email = trim($email);
            $email = $email !== '' ? mb_strtolower($email, 'UTF-8') : null;
        }

        $this->merge([
            'full_name' => TextNormalizer::personName($this->input('full_name')),
            'phone' => TextNormalizer::phoneE164($this->input('phone')),
            'email' => $email,
            'note' => $note,
        ]);
    }

    public function rules(): array
    {
        $status = $this->input('status');
        $noteRules = in_array($status, ['inactive', 'suspended'], true)
            ? ['required', 'string', 'max:2000']
            : ['nullable', 'string', 'max:2000'];

        return [
            'full_name' => ['required', 'string', 'max:255', 'regex:/^\\p{L}+(?:[\\s\'-]\\p{L}+)*$/u'],
            'license_number' => ['required', 'string', 'max:100', 'unique:drivers,license_number'],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:drivers,email'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => $noteRules,
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.regex' => 'Full name must contain only letters and spaces (e.g. "Ibrahim Musa" or "O\'Connor").',
            'email.unique' => 'This email address is already registered for another driver.',
        ];
    }
}
