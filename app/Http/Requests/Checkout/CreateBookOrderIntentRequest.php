<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateBookOrderIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productSlug' => ['required', 'string'],
            'formatType' => ['required', Rule::in(['digital', 'hardcopy'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'buyerType' => ['required', Rule::in(config('zangi_catalog.buyer_types', []))],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['required', 'string', 'max:32'],
            'organizationName' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('buyerType'), ['corporate', 'wholesale'], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'currency' => ['required', Rule::in(['ZMW', 'USD'])],
            'paymentMethod' => ['required', Rule::in(['mobile-money', 'card'])],
            'returnPath' => ['required', 'string', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'organizationName' => trim((string) $this->input('organizationName')),
            'currency' => strtoupper(trim((string) $this->input('currency'))),
        ]);
    }
}
