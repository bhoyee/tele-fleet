<?php

namespace App\Http\Requests\Trip;

use App\Models\User;
use App\Support\TextNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

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
            'purpose' => TextNormalizer::titleText($this->input('purpose')),
            'destination' => TextNormalizer::titleText($this->input('destination')),
            'trip_time' => $tripTime !== '' ? $tripTime : null,
            'estimated_distance_km' => $estimatedDays,
            'additional_notes' => TextNormalizer::collapseWhitespace($this->input('additional_notes')),
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
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            if (! in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
                return;
            }

            $tripDateValue = $this->input('trip_date');
            if (! is_string($tripDateValue) || trim($tripDateValue) === '') {
                return;
            }

            try {
                $tripDate = Carbon::parse($tripDateValue)->startOfDay();
            } catch (\Throwable) {
                return;
            }

            $now = Carbon::now();
            $today = $now->toDateString();
            $tripDay = $tripDate->toDateString();

            if ($tripDay < $today) {
                $validator->errors()->add('trip_date', 'Trip date cannot be in the past.');
                return;
            }

            if ($tripDay !== $today) {
                return;
            }

            $tripTime = $this->input('trip_time');
            if (! is_string($tripTime) || trim($tripTime) === '') {
                $validator->errors()->add('trip_time', 'Trip time is required for trips scheduled today.');
                return;
            }

            $tripTime = trim($tripTime);
            try {
                $tripStart = Carbon::createFromFormat('Y-m-d H:i', $today . ' ' . $tripTime);
            } catch (\Throwable) {
                return;
            }

            if ($tripStart->lt($now->copy()->startOfMinute())) {
                $validator->errors()->add('trip_time', 'Trip time cannot be earlier than the current time.');
            }
        });
    }
}
