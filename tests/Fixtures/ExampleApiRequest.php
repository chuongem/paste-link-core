<?php

namespace Tests\Fixtures;

use App\Http\Requests\ApiRequest;

class ExampleApiRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:10'],
        ];
    }
}
