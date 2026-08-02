<?php

namespace App\Ai\Providers;

class AiProviderManager
{
    public function provider(?string $name = null): AiProvider
    {
        return match ($name ?: (string) config('ai.provider', 'local')) {
            'local' => app(LocalAiProvider::class),
            default => app(LocalAiProvider::class),
        };
    }
}
