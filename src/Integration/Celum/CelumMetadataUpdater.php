<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Celum;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Terminal42\ContaoDamIntegrator\Integration\AbstractMetadataUpdater;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;

class CelumMetadataUpdater extends AbstractMetadataUpdater
{
    /**
     * @param array<string, array<string, string>> $metaConfig
     */
    public function __construct(
        private readonly CelumApi $celumApi,
        private readonly array $metaConfig,
    ) {
    }

    public function update(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event): AssetMetadata
    {
        $this->setMetaOnEvent($assetMetadata, $event, $this->metaConfig, fn (string $locale) => $this->extractContext($this->celumApi->getAssetDetails((int) $assetMetadata->identifier, $locale)));

        return $assetMetadata;
    }

    /**
     * @param array{general: array<array{id: string, value: string}>, infofields: array<array{id: string, value: string}>} $asset
     *
     * @return array<string, string>
     */
    private function extractContext(array $asset): array
    {
        $context = [];

        // Prepare general properties
        foreach ($asset['general'] as $data) {
            $context[$data['id']] = $data['value'];
        }

        // Infofields
        foreach ($asset['infofields'] as $data) {
            $context[$data['id']] = $data['value'];
        }

        return $context;
    }
}
