<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPortalUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(config('zangi_catalog.buyer_types', []))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'unique:portal_users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'organizationName' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('role'), ['corporate', 'wholesale'], true)),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
            'phone' => trim((string) $this->input('phone')),
            'organizationName' => trim((string) $this->input('organizationName')),
        ]);
    }
}
