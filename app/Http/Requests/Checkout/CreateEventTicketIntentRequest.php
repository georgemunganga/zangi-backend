<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateEventTicketIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eventSlug' => ['required', 'string'],
            'ticketTypeId' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'buyerType' => ['required', Rule::in(['individual', 'corporate'])],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['required', 'string', 'max:32'],
            'organizationName' => [
                Rule::requiredIf(fn (): bool => $this->input('buyerType') === 'corporate'),
                'nullable',
                'string',
                'max:255',
            ],
            'currency' => ['required', Rule::in(['ZMW', 'USD'])],
            'paymentMethod' => ['required', Rule::in(['mobile-money', 'card'])],
            'returnPath' => ['required', 'string', 'max:2048', 'regex:/^\/(?!\/)[^\s]*$/'],
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
