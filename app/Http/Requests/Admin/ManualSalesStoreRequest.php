<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ManualSalesStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customerMode' => ['required', Rule::in(['existing', 'walk_in'])],
            'existingCustomerId' => ['nullable', 'string', 'max:120'],
            'saleType' => ['required', Rule::in(['ticket', 'book'])],
            'eventSlug' => ['nullable', 'string', 'max:120', 'required_if:saleType,ticket'],
            'ticketType' => ['nullable', 'string', 'max:120', 'required_if:saleType,ticket'],
            'bookSlug' => ['nullable', 'string', 'max:120', 'required_if:saleType,book'],
            'bookFormat' => ['nullable', 'string', 'max:120', 'required_if:saleType,book'],
            'buyerName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'priceMode' => ['required', Rule::in(['standard', 'custom'])],
            'customUnitPrice' => ['nullable', 'numeric', 'min:0', 'required_if:priceMode,custom'],
            'paymentMethod' => ['required', Rule::in(['Cash', 'Mobile Money', 'Card', 'Complimentary'])],
            'issueStatus' => ['required', Rule::in(['paid', 'unpaid', 'reserved'])],
            'customerType' => ['nullable', Rule::in(['Walk-in', 'Individual', 'Corporate', 'Wholesale'])],
            'relationshipType' => ['nullable', Rule::in(['Walk-in', 'Existing'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $email = trim((string) $this->input('email', ''));
                $phone = trim((string) $this->input('phone', ''));

                if ($email === '' && $phone === '') {
                    $validator->errors()->add('email', 'Provide at least an email address or a phone number.');
                }
            },
        ];
    }
}
