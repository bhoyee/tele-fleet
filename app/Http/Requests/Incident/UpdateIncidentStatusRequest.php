<?php

namespace App\Http\Requests\Incident;

use App\Support\TextNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'resolution_notes' => TextNormalizer::collapseWhitespace($this->input('resolution_notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:open,under_review,resolved'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
