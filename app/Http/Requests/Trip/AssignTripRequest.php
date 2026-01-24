<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class AssignTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_vehicle_id' => ['required', 'exists:vehicles,id'],
            'assigned_driver_id' => ['required', 'exists:drivers,id'],
        ];
    }
}
