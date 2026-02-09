<?php

namespace App\Http\Requests\Trip;

use App\Models\TripRequest;
use Illuminate\Foundation\Http\FormRequest;

class AssignTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var TripRequest|null $tripRequest */
        $tripRequest = $this->route('tripRequest');
        $requestedVehicleId = (int) $this->input('assigned_vehicle_id');
        $requestedDriverId = (int) $this->input('assigned_driver_id');
        $currentVehicleId = (int) ($tripRequest?->assigned_vehicle_id ?? 0);
        $currentDriverId = (int) ($tripRequest?->assigned_driver_id ?? 0);

        $hasExistingAssignment = $tripRequest && ($currentVehicleId || $currentDriverId);
        $isChangingAssignment = $tripRequest && (
            ($requestedVehicleId && $requestedVehicleId !== $currentVehicleId)
            || ($requestedDriverId && $requestedDriverId !== $currentDriverId)
        );

        return [
            'assigned_vehicle_id' => ['required', 'exists:vehicles,id'],
            'assigned_driver_id' => ['required', 'exists:drivers,id'],
            'reason' => [$hasExistingAssignment && $isChangingAssignment ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
