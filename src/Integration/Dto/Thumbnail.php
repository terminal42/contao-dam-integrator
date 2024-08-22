<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

class Thumbnail
{
    public function __construct(
        public readonly string $url,
        public readonly string $alt,
    ) {
    }

    /**
     * @return array{url: string, alt: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'alt' => $this->alt,
        ];
    }
}
