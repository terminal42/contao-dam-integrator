<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator;

use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;

class AssetHandler
{
    public function __construct(
        private readonly IntegrationCollection $integrationCollection,
        private readonly VirtualFilesystemInterface $virtualFilesystem,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function updateMetadata(Uuid|string $location): void
    {
        [$item, $assetMetadata] = $this->loadAssetMetadataFromLocation($location);

        $this->triggerMetadataUpdate(
            $item->getPath(),
            $assetMetadata,
        );
    }

    public function redownloadAsset(Uuid|string $location): void
    {
        [, $assetMetadata] = $this->loadAssetMetadataFromLocation($location);

        $this->downloadAsset($assetMetadata->integration, $assetMetadata->identifier, true);
    }

    /**
     * @return string|null The DBAFS uuid or null on error
     */
    public function downloadAsset(string $integration, string $identifier, bool $replaceExisting = false): string|null
    {
        $integration = $this->getIntegration($integration);

        $result = $integration->downloadAsset($identifier, $replaceExisting);

        if (!$result->isSuccessful()) {
            return null;
        }

        $this->virtualFilesystem->writeStream($result->getPath(), $result->getStream());
        $item = $this->virtualFilesystem->get($result->getPath());
        $uuid = $item->getUuid()?->toRfc4122();

        if (null === $uuid) {
            $this->logger->error(\sprintf('Could not assign a UUID to the asset ID "%s".', $identifier));

            return null;
        }

        $assetMetadata = new AssetMetadata(
            $result->getIdentifier(),
            $result->getHash(),
            $integration::getKey(),
        );

        // Read only the first 32kb which should be enough to determine if it is an image and if so, its dimensions
        rewind($result->getStream());
        $head = fread($result->getStream(), 32768);

        if (\is_string($head) && \is_array($tmp = @getimagesizefromstring($head))) {
            $assetMetadata = $assetMetadata
                ->withWidth((int) $tmp[0])
                ->withHeight((int) $tmp[1])
            ;
        }

        $this->triggerMetadataUpdate($result->getPath(), $assetMetadata);

        return $uuid;
    }

    /**
     * @return array{0:FilesystemItem, 1: AssetMetadata}
     */
    private function loadAssetMetadataFromLocation(Uuid|string $location): array
    {
        $item = $this->virtualFilesystem->get($location);

        if (null === $item) {
            throw new \InvalidArgumentException('Asset not found.');
        }

        $assetMetadata = $item->getExtraMetadata()->get(AssetMetadata::VIRTUAL_FILESYSTEM_META_KEY);

        if ($assetMetadata instanceof AssetMetadata) {
            return [$item, $assetMetadata];
        }

        throw new \InvalidArgumentException('Asset is not managed.');
    }

    private function triggerMetadataUpdate(string $path, AssetMetadata $assetMetadata): void
    {
        $this->virtualFilesystem->setExtraMetadata($path, new ExtraMetadata([
            AssetMetadata::VIRTUAL_FILESYSTEM_META_KEY => $assetMetadata,
        ]));
    }

    private function getIntegration(string $integration): IntegrationInterface
    {
        if (!$this->integrationCollection->has($integration)) {
            throw new NotFoundHttpException('Integration "'.$integration.'" not found.');
        }

        return $this->integrationCollection->get($integration);
    }
}
