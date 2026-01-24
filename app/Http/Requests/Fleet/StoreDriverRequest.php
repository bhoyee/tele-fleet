<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:100', 'unique:drivers,license_number'],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
