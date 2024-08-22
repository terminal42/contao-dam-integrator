<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Bynder;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Picker\PickerConfig;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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

class BynderIntegration extends AbstractIntegration
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $derivativeName,
        private readonly string $targetDir,
        private readonly BynderMetadataUpdater|null $metadataUpdater,
    ) {
    }

    public static function getKey(): string
    {
        return 'bynder';
    }

    public function supportsPicker(PickerConfig $pickerConfig): bool
    {
        return parent::supportsPicker($pickerConfig) && [] !== $this->extractBynderMediaTypeFromAllowedExtensions($pickerConfig);
    }

    public function getPickerLabel(): string
    {
        return 'Bynder Asset Management';
    }

    public function fetchAssets(AssetFilter $filter): AssetCollection
    {
        $query = [
            'count' => 1,
            'type' => implode(',', $this->extractBynderMediaTypeFromAllowedExtensions($filter->pickerConfig)),
            'orderBy' => 'name asc',
            'limit' => $filter->limit,
            'page' => $filter->page,
        ];

        if (!$filter->filters->empty()) {
            $propertyOptionIds = [];

            foreach ($filter->filters->all() as $filter) {
                foreach ($filter->allOptions() as $option) {
                    $propertyOptionIds[] = $option->value;
                }
            }

            $query['propertyOptionId'] = implode(',', $propertyOptionIds);
        }

        $media = $this->httpClient->request(
            'GET',
            'media',
            [
                'query' => $query,
            ],
        )->toArray();

        $assets = new AssetCollection((int) $media['count']['total']);

        foreach ($media['media'] as $assetData) {
            $asset = new Asset($assetData['id'], $assetData['idHash'], $assetData['name']);
            $asset->fileSize = (int) $assetData['fileSize'];
            $asset->width = (int) $assetData['width'];
            $asset->height = (int) $assetData['height'];
            $asset->thumbnail = new Thumbnail($assetData['thumbnails']['mini'], $assetData['name']);

            $assets->addAsset($asset);
        }

        return $assets;
    }

    public function getPickerFilters(PickerConfig $pickerConfig): FilterCollection
    {
        $filters = new FilterCollection();

        $metaProperties = $this->httpClient->request(
            'GET',
            'metaproperties',
            [
                'query' => [
                    'count' => 1,
                    'type' => implode(',', $this->extractBynderMediaTypeFromAllowedExtensions($pickerConfig)),
                ],
            ],
        )->toArray();

        foreach ($metaProperties as $propertyName => $metaProperty) {
            // Currently, only single selects are supported.
            if (!isset($metaProperty['type']) || !\in_array($metaProperty['type'], ['select', 'select2'], true)) {
                continue;
            }

            $filter = new Filter($propertyName, $metaProperty['label']);

            foreach ($metaProperty['options'] as $option) {
                // No need to show empty filter options
                if (0 === $option['mediaCount']) {
                    continue;
                }

                $filter->addOption(new FilterOption($option['id'], $option['displayLabel']));
            }

            // If there is only one option (or none), that filter doesn't make sense to show
            if ($filter->countOptions() <= 1) {
                continue;
            }

            $filters->addFilter($filter);
        }

        return $filters;
    }

    public function downloadAsset(string $identifier, bool $replaceExisting = false): DownloadResult
    {
        try {
            $media = $this->getMedia($identifier);

            if (null === $media) {
                return DownloadResult::failed($identifier);
            }

            if (!isset($media['thumbnails'][$this->derivativeName])) {
                $this->logError(\sprintf('Could not import the derivative "%s" for media ID "%s" because the derivative does not exist.',
                    $this->derivativeName,
                    $identifier,
                ));

                return DownloadResult::failed($identifier);
            }

            /** @var StreamableInterface $response */
            $response = $this->httpClient->request('GET', $media['thumbnails'][$this->derivativeName]);
            $stream = $response->toStream();
        } catch (ExceptionInterface $e) {
            $this->logError(\sprintf('Could not import the derivative "%s" for media ID "%s": %s.',
                $this->derivativeName,
                $identifier,
                $e->getMessage(),
            ), $e);

            return DownloadResult::failed($identifier);
        }

        $path = $this->getTargetPath($media['name'], $this->targetDir, $replaceExisting, $media['extension'][0] ?? null);

        return DownloadResult::successful($identifier, $media['idHash'], $stream, $path);
    }

    public function updateAssetMetadata(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event): AssetMetadata
    {
        if (null === $this->metadataUpdater) {
            return $assetMetadata;
        }

        $media = $this->getMedia($assetMetadata->identifier);

        if (null === $media) {
            return $assetMetadata;
        }

        return $this->metadataUpdater->update($assetMetadata, $event, $media);
    }

    /**
     * @return array<mixed>|null
     */
    private function getMedia(string $identifier): array|null
    {
        try {
            $media = $this->httpClient->request('GET', \sprintf('media/%s', $identifier))->toArray();
        } catch (ExceptionInterface $e) {
            $this->logError(\sprintf('Could not download asset ID "%s"', $identifier), $e);

            return null;
        }

        return $media;
    }

    /**
     * @return array<string>
     */
    private function extractBynderMediaTypeFromAllowedExtensions(PickerConfig $config): array
    {
        // https://support.bynder.com/hc/en-us/articles/360013936359-Supported-File-Types-for-Upload-and-Preview
        static $bynderMediaTypes = [
            'image' => [
                'ai',
                'bmp',
                'dng',
                'eps',
                'gif',
                'indd',
                'jpg',
                'png',
                'psb',
                'psd',
                'raw',
                'sketch',
                'svg',
                'tif',
                'tiff',
                'webp',
            ],
            'document' => [
                'doc',
                'docx',
                'pdf',
                'ppt',
                'pptx',
            ],
            'audio' => [
                // somehow not documented?
            ],
            'video' => [
                'p4',
                'wmv',
                'mpeg',
                'mov',
                'avi',
                'flv',
                'vob',
                'mkv',
                'm4v',
                'f4v',
                '3gpp',
                '3gp',
                'ogv',
                'ts',
                'mts',
                'm2ts',
                '3g2',
                'm2v',
                'webm',
            ],
            '3d' => [
                'glb',
            ],
        ];

        $extensions = $this->getAllowedExtensionsFromPickerConfig($config);

        if (null === $extensions) {
            return array_keys($bynderMediaTypes);
        }

        $allowedBynderMediaTypes = [];

        foreach ($extensions as $extension) {
            foreach ($bynderMediaTypes as $type => $extensions) {
                if (array_search($extension, $extensions, true)) {
                    $allowedBynderMediaTypes[] = $type;
                }
            }
        }

        return array_unique($allowedBynderMediaTypes);
    }
}
