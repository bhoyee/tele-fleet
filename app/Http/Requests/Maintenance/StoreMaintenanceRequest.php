<?php

namespace App\Http\Requests\Maintenance;

use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'status' => ['required', Rule::in([
                VehicleMaintenance::STATUS_SCHEDULED,
                VehicleMaintenance::STATUS_IN_PROGRESS,
                VehicleMaintenance::STATUS_COMPLETED,
                VehicleMaintenance::STATUS_CANCELLED,
            ])],
            'scheduled_for' => ['required', 'date'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:190'],
            'notes' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'odometer' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
