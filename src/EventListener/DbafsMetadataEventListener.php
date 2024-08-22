<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\EventListener;

use Contao\CoreBundle\Filesystem\Dbafs\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;
use Terminal42\ContaoDamIntegrator\IntegrationCollection;

class DbafsMetadataEventListener
{
    public function __construct(private readonly IntegrationCollection $integrationCollection)
    {
    }

    #[AsEventListener]
    public function onStore(StoreDbafsMetadataEvent $event): void
    {
        if (!$this->supports($event)) {
            return;
        }

        $meta = $event->getExtraMetadata();

        if (!isset($meta[AssetMetadata::VIRTUAL_FILESYSTEM_META_KEY])) {
            return;
        }

        /** @var AssetMetadata $metadata */
        $metadata = $meta[AssetMetadata::VIRTUAL_FILESYSTEM_META_KEY];

        if (!$metadata instanceof AssetMetadata || !$this->integrationCollection->has($metadata->integration)) {
            return;
        }

        $integration = $this->integrationCollection->get($metadata->integration);

        $metadata = $integration->updateAssetMetadata($metadata, $event);

        $event->set('dam_asset_id', $metadata->identifier);
        $event->set('dam_asset_hash', $metadata->hash);
        $event->set('dam_asset_integration', $metadata->integration);
        $event->set('dam_asset_extra', [] === $metadata->getExtra() ? null : json_encode($metadata->getExtra()));

        if ($metadata->getWidth()) {
            $event->set('dam_asset_width', $metadata->getWidth());
        }

        if ($metadata->getHeight()) {
            $event->set('dam_asset_height', $metadata->getHeight());
        }
    }

    #[AsEventListener]
    public function onRetrieve(RetrieveDbafsMetadataEvent $event): void
    {
        if (!$this->supports($event)) {
            return;
        }

        $integration = $event->getRow()['dam_asset_integration'] ?? '';

        if (!$this->integrationCollection->has($integration)) {
            return;
        }

        $assetMetadata = new AssetMetadata(
            $event->getRow()['dam_asset_id'] ?? '',
            $event->getRow()['dam_asset_hash'] ?? '',
            $integration,
        );

        if ($event->getRow()['dam_asset_extra'] ?? null) {
            $assetMetadata = $assetMetadata->withExtra(json_decode((string) $event->getRow()['dam_asset_extra'], true));
        }

        if ($event->getRow()['dam_asset_width'] ?? null) {
            $assetMetadata = $assetMetadata->withWidth($event->getRow()['dam_asset_width']);
        }

        if ($event->getRow()['dam_asset_height'] ?? null) {
            $assetMetadata = $assetMetadata->withHeight($event->getRow()['dam_asset_height']);
        }

        $event->set(AssetMetadata::VIRTUAL_FILESYSTEM_META_KEY, $assetMetadata);
    }

    private function supports(RetrieveDbafsMetadataEvent|StoreDbafsMetadataEvent $event): bool
    {
        return 'tl_files' === $event->getTable();
    }
}
