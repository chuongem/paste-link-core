<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AiActionRequest extends ApiRequest
{
    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language' => ['sometimes', 'nullable', 'string', 'max:16'],
            'target_language' => ['sometimes', 'nullable', 'string', 'max:16'],
            'extraction_type' => ['sometimes', 'nullable', 'string', Rule::in(['invoice', 'resume', 'meeting', 'custom'])],
            'prompt' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ];
    }
}
