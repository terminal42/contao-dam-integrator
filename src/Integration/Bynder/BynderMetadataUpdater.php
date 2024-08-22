<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Bynder;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Terminal42\ContaoDamIntegrator\Integration\AbstractMetadataUpdater;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;

class BynderMetadataUpdater extends AbstractMetadataUpdater
{
    private const IMPORTANT_PART_SQUARE_LENGTH_IN_PX = 100;

    /**
     * @param array<string, array<string, string>> $metaConfig
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly array $metaConfig,
    ) {
    }

    /**
     * @param array<mixed> $media
     */
    public function update(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event, array $media): AssetMetadata
    {
        $metaPropertyOptions = null;
        $metaProperties = null;

        $this->setMetaOnEvent(
            $assetMetadata,
            $event,
            $this->metaConfig,
            function (string $locale) use ($media, &$metaProperties, &$metaPropertyOptions) {
                if (null === $metaPropertyOptions) {
                    $metaPropertyOptions = $this->httpClient->request(
                        'GET',
                        'metaproperties/options',
                        [
                            'query' => ['ids' => implode(',', $media['propertyOptions'] ?? [])],
                        ],
                    )->toArray();
                }

                if (null === $metaProperties) {
                    $metaProperties = $this->httpClient->request(
                        'GET',
                        'metaproperties',
                        [
                            'query' => [
                                'ids' => implode(',', array_map(static fn (array $option) => $option['metapropertyId'], $metaPropertyOptions)),
                                'options' => 0,
                            ],
                        ],
                    )->toArray();
                }

                return $this->resolveCustomMetaPropertyOptionsForLanguage($media, $metaPropertyOptions, $metaProperties, $locale);
            },
        );

        $this->importImportantPath($assetMetadata, $event, $media);

        return $assetMetadata;
    }

    /**
     * @param array<mixed> $media
     */
    private function importImportantPath(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event, array $media): void
    {
        if (
            null === $assetMetadata->getWidth()
            || null === $assetMetadata->getHeight()
            || !isset($media['width'])
            || !isset($media['height'])
            || !isset($media['activeOriginalFocusPoint']['x'])
            || !isset($media['activeOriginalFocusPoint']['y'])
        ) {
            return;
        }

        // Adjust the focus point of the original file to our relative file dimensions of
        // the derivative
        $x = (int) round($assetMetadata->getWidth() / $media['width'] * $media['activeOriginalFocusPoint']['x']);
        $y = (int) round($assetMetadata->getHeight() / $media['height'] * $media['activeOriginalFocusPoint']['y']);

        $x = $x - self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / 2;
        $y = $y - self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / 2;

        if ($x < 0 || $y < 0) {
            return;
        }

        // Our important part configuration is in percentages
        $event->set('importantPartX', $x / $assetMetadata->getWidth());
        $event->set('importantPartY', $y / $assetMetadata->getHeight());
        $event->set('importantPartWidth', self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / $assetMetadata->getWidth());
        $event->set('importantPartHeight', self::IMPORTANT_PART_SQUARE_LENGTH_IN_PX / $assetMetadata->getHeight());
    }

    /**
     * @param array<mixed> $media
     * @param array<mixed> $metaPropertyOptions
     * @param array<mixed> $metaProperties
     *
     * @return array<string, mixed>
     */
    private function resolveCustomMetaPropertyOptionsForLanguage(array $media, array $metaPropertyOptions, array $metaProperties, string $locale): array
    {
        $mediaResolved = $media;
        $singleSelectProperties = [];

        foreach ($media as $property => $value) {
            if (!str_starts_with($property, 'property_')) {
                continue;
            }

            $propertyName = substr($property, 9);

            if (!isset($metaProperties[$propertyName]) || !\is_array($value)) {
                continue;
            }

            if (!$metaProperties[$propertyName]['isMultiselect']) {
                $singleSelectProperties[$property] = true;
            }

            foreach ($metaPropertyOptions as $metaPropertyOption) {
                if ($metaPropertyOption['metapropertyId'] !== $metaProperties[$propertyName]['id']) {
                    continue;
                }

                foreach ($value as $k => $v) {
                    if ($metaPropertyOption['name'] === $v) {
                        $mediaResolved[$property][$k] = $this->findMatchingLabel($metaPropertyOption['labels'], $locale) ?? $metaPropertyOption['label'];
                    }
                }
            }
        }

        // Flatten the values that are single choices only
        foreach (array_keys($singleSelectProperties) as $singleSelectProperty) {
            $mediaResolved[$singleSelectProperty] = $mediaResolved[$singleSelectProperty][0];
        }

        return $mediaResolved;
    }

    /**
     * @param array<string, string> $labels
     */
    private function findMatchingLabel(array $labels, string $locale): string|null
    {
        // Exact dialect match
        if (isset($labels[$locale])) {
            return $labels[$locale];
        }

        // Primary language match
        foreach ($labels as $langCode => $label) {
            if (LocaleUtil::getPrimaryLanguage($langCode) === $locale) {
                return $label;
            }
        }

        return null;
    }
}
