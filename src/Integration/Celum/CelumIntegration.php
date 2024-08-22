<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Celum;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Picker\PickerConfig;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Terminal42\ContaoDamIntegrator\Integration\AbstractIntegration;
use Terminal42\ContaoDamIntegrator\Integration\Dto\Asset;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetCollection;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetFilter;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;
use Terminal42\ContaoDamIntegrator\Integration\Dto\DownloadResult;
use Terminal42\ContaoDamIntegrator\Integration\Dto\Filter;
use Terminal42\ContaoDamIntegrator\Integration\Dto\FilterCollection;
use Terminal42\ContaoDamIntegrator\Integration\Dto\FilterOption;
use Terminal42\ContaoDamIntegrator\Integration\Dto\Thumbnail;

class CelumIntegration extends AbstractIntegration
{
    public function __construct(
        private readonly CelumApi $celumApi,
        private readonly int $downloadFormatId,
        private readonly string $thumbnailUrlTemplate,
        private readonly string $targetDir,
        private readonly CelumMetadataUpdater|null $metadataUpdater,
    ) {
    }

    public static function getKey(): string
    {
        return 'celum';
    }

    public function getPickerLabel(): string
    {
        return 'Celum Asset Management';
    }

    public function getPickerFilters(PickerConfig $pickerConfig): FilterCollection
    {
        $filterCollection = new FilterCollection();
        $fieldTypes = $this->celumApi->request('asset.do', [
            'infofield' => '-1', // Al field types
        ]);
        $facetFilters = [];

        foreach ($fieldTypes->toArray()['data'] ?? [] as $fieldId => $infoField) {
            $filter = new Filter('info_'.$fieldId, $infoField['name']);
            $filter->extras['infoField'] = $infoField;

            if ('NodeReference' === $infoField['kind']) {
                $facetFilters[] = $filter;
                continue;
            }

            if ('DropDown' !== $infoField['kind']) {
                continue;
            }

            foreach ($infoField['values'] ?? [] as $value) {
                $filter->addOption(new FilterOption((string) $value['id'], $value['name']));
            }

            $filterCollection->addFilter($filter);
        }

        $this->addFacetFilters($facetFilters, $filterCollection);

        return $filterCollection;
    }

    public function fetchAssets(AssetFilter $filter): AssetCollection
    {
        $query = [
            'page' => $filter->page,
            'search' => $filter->keywords,
            'paginate' => $filter->limit,
        ];

        $allowedExtensions = $this->getAllowedExtensionsFromPickerConfig($filter->pickerConfig);

        if (null !== $allowedExtensions) {
            $query['search_extensions'] = implode(',', $allowedExtensions);
        }

        // Every filter gets queried using search_infofield where the first ID is the field and everything after are the
        // options - pretty interesting
        if (!$filter->filters->empty()) {
            $query['search_infofield'] = [];

            foreach ($filter->filters->all() as $filter) {
                $field = [preg_replace('/^info_/', '', $filter->propertyName)];

                foreach ($filter->allOptions() as $option) {
                    $field[] = $option->value;
                }

                $query['search_infofield'][] = implode(',', $field);
            }
        }

        $result = $this->celumApi->request('node.do', $query)->toArray();
        $assets = new AssetCollection((int) $result['total']);

        foreach ($result['data'] ?? [] as $assetData) {
            $asset = new Asset((string) $assetData['id'], (string) $assetData['version'], $assetData['name']);
            $asset->thumbnail = new Thumbnail(
                $this->thumbnailUrlTemplate.$assetData['id'],
                $assetData['name'],
            );

            $assets->addAsset($asset);
        }

        return $assets;
    }

    public function downloadAsset(string $identifier, bool $replaceExisting = false): DownloadResult
    {
        $id = (int) $identifier;

        try {
            $asset = $this->celumApi->getAssetDetails($id);
            $stream = $this->celumApi->request('asset.do', [
                'download' => $identifier,
                'format' => $this->downloadFormatId,
            ])->toStream();
        } catch (ExceptionInterface $e) {
            $this->logError(\sprintf('Could not download the asset ID "%s": %s',
                $identifier,
                $e->getMessage(),
            ), $e);

            return DownloadResult::failed($identifier);
        }

        $name = $this->extractValueFromGeneralAssetDetails($asset, 'name');

        if (!\is_string($name)) {
            $this->logError(\sprintf('Asset ID "%s" did not specify any name', $identifier));

            return DownloadResult::failed($identifier);
        }

        $extension = $this->extractValueFromGeneralAssetDetails($asset, 'extension');

        // Extension must be either a string or null, otherwise we force it to be null
        if (!\is_string($extension) && null === $extension) {
            $extension = null;
        }

        // If it is a string we remove it from the name if it exists
        if (\is_string($extension)) {
            $name = preg_replace('/\.'.preg_quote($extension, '/').'$/', '', $name);
        }

        $path = $this->getTargetPath($name, $this->targetDir, $replaceExisting, $extension);

        return DownloadResult::successful($identifier, (string) $this->extractVersionFromAssetDetails($asset), $stream, $path);
    }

    public function updateAssetMetadata(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event): AssetMetadata
    {
        if (null === $this->metadataUpdater) {
            return $assetMetadata;
        }

        return $this->metadataUpdater->update($assetMetadata, $event);
    }

    /**
     * @param array<mixed> $asset
     */
    private function extractValueFromGeneralAssetDetails(array $asset, string $key): mixed
    {
        if (!isset($asset['general'])) {
            return null;
        }

        foreach ($asset['general'] as $value) {
            if ($value['id'] === $key) {
                return $value['value'];
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $asset
     */
    private function extractVersionFromAssetDetails(array $asset): int
    {
        if (!isset($asset['versions'])) {
            return 0;
        }

        foreach ($asset['versions'] as $version) {
            if ($version['active']) {
                return (int) $version['version'];
            }
        }

        return 0;
    }

    /**
     * @param array<int, Filter> $facetFilters
     */
    private function addFacetFilters(array $facetFilters, FilterCollection $filterCollection): void
    {
        if ([] === $facetFilters) {
            return;
        }

        $query = [
            'search' => '',
            'search_facets' => 1,
            'facet_fields' => implode(',', array_map(static fn (Filter $filter) => $filter->propertyName, $facetFilters)),
        ];

        $nodes = $this->celumApi->request('node.do', $query)->toArray();

        foreach ($facetFilters as $filter) {
            if (!isset($nodes['data'][$filter->propertyName]) || [] === $nodes['data'][$filter->propertyName]) {
                continue;
            }

            $query = [
                'tree' => $filter->extras['infoField']['root_node'],
                'depth' => 1,
                'asset_count' => 'true',
            ];

            $options = $this->celumApi->request('node.do', $query)->toArray();

            foreach ($options['data'] as $option) {
                // No assets
                if (0 === $option['assets']) {
                    continue;
                }

                $filter->addOption(new FilterOption((string) $option['id'], $option['name'].' ('.$option['assets'].')'));
            }

            if ($filter->hasOptions()) {
                $filterCollection->addFilter($filter);
            }
        }
    }
}
