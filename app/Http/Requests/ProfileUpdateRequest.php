<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => Rule::when(
                $user?->role === User::ROLE_SUPER_ADMIN,
                [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(User::class)->ignore($user?->id),
                ],
                [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::in([$user?->email]),
                ]
            ),
            'branch_id' => Rule::when(
                $user?->role === User::ROLE_SUPER_ADMIN,
                ['nullable', 'exists:branches,id'],
                ['prohibited']
            ),
        ];
    }
}
