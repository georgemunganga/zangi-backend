<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TicketValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticketCode' => ['required', 'string', 'max:120'],
            'eventSlug' => ['nullable', 'string', 'max:120'],
        ];
    }
}
