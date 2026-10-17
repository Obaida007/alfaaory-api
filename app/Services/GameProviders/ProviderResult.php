<?php

namespace App\Services\GameProviders;

final readonly class ProviderResult
{
    /**
     * @param  'completed'|'processing'|'failed'  $status
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public string $status,
        public array $response = [],
        public ?string $code = null,
        public ?string $message = null,
    ) {}
}
