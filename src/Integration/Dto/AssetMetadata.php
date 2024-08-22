<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

class AssetMetadata
{
    public const VIRTUAL_FILESYSTEM_META_KEY = 't42_dam_integrator_asset_metadata';

    private int|null $width = null;

    private int|null $height = null;

    /**
     * @var array<mixed>
     */
    private array $extra = [];

    public function __construct(
        public string $identifier,
        public string $hash,
        public string $integration,
    ) {
    }

    public function getWidth(): int|null
    {
        return $this->width;
    }

    public function withWidth(int|null $width): self
    {
        $clone = clone $this;
        $clone->width = $width;

        return $clone;
    }

    public function getHeight(): int|null
    {
        return $this->height;
    }

    public function withHeight(int|null $height): self
    {
        $clone = clone $this;
        $clone->height = $height;

        return $clone;
    }

    /**
     * @return array<mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @param array<mixed> $extra
     */
    public function withExtra(array $extra): self
    {
        $clone = clone $this;
        $clone->extra = $extra;

        return $clone;
    }
}
