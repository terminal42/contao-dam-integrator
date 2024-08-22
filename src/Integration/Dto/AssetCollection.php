<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

class AssetCollection
{
    /**
     * @param array<Asset> $assets
     */
    public function __construct(
        public int $totalMatches,
        private array $assets = [],
    ) {
    }

    public function addAsset(Asset $asset): self
    {
        $this->assets[] = $asset;

        return $this;
    }

    /**
     * @return array<Asset>
     */
    public function all(): array
    {
        return $this->assets;
    }
}
