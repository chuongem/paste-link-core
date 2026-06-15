<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class LoginRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
