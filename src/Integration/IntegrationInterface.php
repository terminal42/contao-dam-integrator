<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Picker\PickerConfig;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetCollection;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetFilter;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;
use Terminal42\ContaoDamIntegrator\Integration\Dto\DownloadResult;
use Terminal42\ContaoDamIntegrator\Integration\Dto\FilterCollection;

interface IntegrationInterface
{
    public const CONTAINER_TAG_NAME = 'terminal42_contao_dam_integrator.integration';

    public static function getKey(): string;

    public function supportsPicker(PickerConfig $pickerConfig): bool;

    public function fetchAssets(AssetFilter $filter): AssetCollection;

    public function getPickerLabel(): string;

    public function getPickerFilters(PickerConfig $pickerConfig): FilterCollection;

    public function downloadAsset(string $identifier, bool $replaceExisting = false): DownloadResult;

    public function updateAssetMetadata(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event): AssetMetadata;
}
