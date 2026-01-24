<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class LogTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_mileage' => ['required', 'integer', 'min:0'],
            'end_mileage' => ['required', 'integer', 'gte:start_mileage'],
            'fuel_before_trip' => ['nullable', 'numeric', 'min:0'],
            'fuel_after_trip' => ['nullable', 'numeric', 'min:0'],
            'actual_start_time' => ['nullable', 'date'],
            'actual_end_time' => ['nullable', 'date', 'after_or_equal:actual_start_time'],
            'driver_name' => ['required', 'string', 'max:255'],
            'driver_license_number' => ['required', 'string', 'max:255'],
            'paper_logbook_ref_number' => ['nullable', 'string', 'max:255'],
            'driver_notes' => ['nullable', 'string', 'max:1000'],
            'log_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
