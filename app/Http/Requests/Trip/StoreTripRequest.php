<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'trip_date' => ['required', 'date'],
            'estimated_distance_km' => ['nullable', 'numeric', 'min:0'],
            'number_of_passengers' => ['nullable', 'integer', 'min:1'],
            'additional_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
