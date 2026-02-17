<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tripTime = $this->input('trip_time');
        if (is_string($tripTime)) {
            $tripTime = trim($tripTime);
            if (preg_match('/^\\d{2}:\\d{2}:\\d{2}$/', $tripTime) === 1) {
                $tripTime = substr($tripTime, 0, 5);
            }
        }

        $estimatedDays = $this->input('estimated_distance_km');
        if (is_string($estimatedDays)) {
            $estimatedDays = str_replace(',', '', trim($estimatedDays));
        }
        if (is_numeric($estimatedDays)) {
            $estimatedDays = (int) ceil((float) $estimatedDays);
        }

        $this->merge([
            'trip_time' => $tripTime !== '' ? $tripTime : null,
            'estimated_distance_km' => $estimatedDays,
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'trip_date' => ['required', 'date'],
            'trip_time' => ['nullable', 'date_format:H:i'],
            'estimated_distance_km' => ['required', 'integer', 'min:1', 'max:365'],
            'number_of_passengers' => ['nullable', 'integer', 'min:1'],
            'additional_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
