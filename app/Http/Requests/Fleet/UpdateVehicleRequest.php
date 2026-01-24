<?php

namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;
        $currentYear = (int) date('Y') + 1;

        return [
            'registration_number' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'registration_number')->ignore($vehicleId)],
            'branch_id' => ['required', 'exists:branches,id'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.$currentYear],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['required', Rule::in(['petrol', 'diesel', 'hybrid', 'electric'])],
            'engine_capacity' => ['nullable', 'string', 'max:50'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'insurance_expiry' => ['nullable', 'date'],
            'registration_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['available', 'in_use', 'maintenance', 'offline'])],
        ];
    }
}
