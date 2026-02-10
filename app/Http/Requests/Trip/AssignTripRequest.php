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
        $requestedVehicleRaw = $this->input('assigned_vehicle_id');
        $requestedDriverRaw = $this->input('assigned_driver_id');
        $requestedVehicleId = is_numeric($requestedVehicleRaw) ? (int) $requestedVehicleRaw : 0;
        $requestedDriverId = is_numeric($requestedDriverRaw) ? (int) $requestedDriverRaw : 0;
        $currentVehicleId = (int) ($tripRequest?->assigned_vehicle_id ?? 0);
        $currentDriverId = (int) ($tripRequest?->assigned_driver_id ?? 0);

        $hasExistingAssignment = $tripRequest && ($currentVehicleId || $currentDriverId);
        $isChangingAssignment = $tripRequest && (
            ($requestedVehicleId && $requestedVehicleId !== $currentVehicleId)
            || ($requestedDriverId && $requestedDriverId !== $currentDriverId)
        );

        $vehicleRule = $currentVehicleId
            ? ['nullable', 'exists:vehicles,id']
            : ['required', 'exists:vehicles,id'];
        $driverRule = $currentDriverId
            ? ['nullable', 'exists:drivers,id']
            : ['required', 'exists:drivers,id'];

        return [
            // If there is already an assignment, you can submit only the field you want to change.
            // Missing values will be treated as "keep current" in the controller.
            'assigned_vehicle_id' => $vehicleRule,
            'assigned_driver_id' => $driverRule,
            'reason' => [$hasExistingAssignment && $isChangingAssignment ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
