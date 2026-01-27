<?php

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_request_id' => ['nullable', 'exists:trip_requests,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'in:minor,major,critical'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
        ];
    }
}
