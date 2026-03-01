<?php

namespace App\Http\Requests\Fleet;

use App\Support\TextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
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

        $this->merge([
            'full_name' => TextNormalizer::personName($this->input('full_name')),
            'phone' => TextNormalizer::phoneE164($this->input('phone')),
            'note' => $note,
        ]);
    }

    public function rules(): array
    {
        $driverId = $this->route('driver')?->id;

        $status = $this->input('status');
        $noteRules = in_array($status, ['inactive', 'suspended'], true)
            ? ['required', 'string', 'max:2000']
            : ['nullable', 'string', 'max:2000'];

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:100', Rule::unique('drivers', 'license_number')->ignore($driverId)],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => $noteRules,
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
