<?php

namespace App\Http\Requests\Branch;

use App\Support\TextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => TextNormalizer::titleText($this->input('name')),
            'code' => TextNormalizer::branchCode($this->input('code')),
            'address' => TextNormalizer::collapseWhitespace($this->input('address')),
            'city' => TextNormalizer::titlePreserveAcronyms($this->input('city'), 3),
            'state' => TextNormalizer::titlePreserveAcronyms($this->input('state'), 3),
        ]);
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branchId)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_head_office' => ['sometimes', 'boolean'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
