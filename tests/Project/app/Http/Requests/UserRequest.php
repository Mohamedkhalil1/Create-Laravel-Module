<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['string', 'required', 'max:255'],
            'password' => ['string', 'required', 'max:255'],
            'name' => ['string', 'required', 'max:255'],
            'username' => ['string', 'required', 'max:255'],
        ];
    }
}
