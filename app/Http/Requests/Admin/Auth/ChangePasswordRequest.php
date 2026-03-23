<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', Password::min(8)],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ];
    }
}
