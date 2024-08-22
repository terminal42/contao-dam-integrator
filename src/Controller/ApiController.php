<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Controller;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Terminal42\ContaoDamIntegrator\AssetHandler;
use Terminal42\ContaoDamIntegrator\Integration\Dto\Asset;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetCollection;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetFilter;
use Terminal42\ContaoDamIntegrator\Integration\Dto\Filter;
use Terminal42\ContaoDamIntegrator\Integration\Dto\FilterCollection;
use Terminal42\ContaoDamIntegrator\Integration\Dto\FilterOption;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;
use Terminal42\ContaoDamIntegrator\IntegrationCollection;

#[Route(path: '/_dam_api/{integration}/', defaults: ['_scope' => 'backend'])]
class ApiController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $contaoFramework,
        private readonly AssetHandler $assetHandler,
        private readonly IntegrationCollection $integrationCollection,
    ) {
    }

    #[Route(path: 'filters', name: 'dam_integrator_api_filters', methods: ['GET'])]
    public function filtersAction(Request $request, string $integration): JsonResponse
    {
        return new JsonResponse($this->getIntegration($integration)->getPickerFilters(
            PickerConfig::urlDecode($request->query->get('picker')),
        )->toArray());
    }

    #[Route(path: 'assets', name: 'dam_integrator_api_assets', methods: ['GET'])]
    public function assetsAction(Request $request, string $integration): JsonResponse
    {
        $integration = $this->getIntegration($integration);
        $preSelected = explode(',', (string) $request->query->get('preSelected'));
        $limit = (int) $this->contaoFramework->getAdapter(Config::class)->get('resultsPerPage');
        $page = $request->query->getInt('page', 1);

        $preparedAssets = [];
        $assets = $integration->fetchAssets($this->buildAssetFilter($request, $limit, $page));
        $downloaded = $this->fetchDownloaded($integration::getKey(), $assets);

        foreach ($assets->all() as $asset) {
            $preparedAssets[] = $this->prepareImage($asset, $downloaded, $preSelected);
        }

        return new JsonResponse([
            'assets' => $preparedAssets,
            'pagination' => $this->calculatePagination($page, $limit, $assets->totalMatches),
        ]);
    }

    #[Route(path: 'download', name: 'dam_integrator_api_download', methods: ['POST'])]
    public function downloadAction(Request $request, string $integration): JsonResponse
    {
        $identifier = $request->toArray()['identifier'] ?? null;

        if (null === $identifier) {
            throw new NotFoundHttpException();
        }

        $uuid = $this->assetHandler->downloadAsset($integration, $identifier);

        if (null === $uuid) {
            return new JsonResponse([
                'status' => 'FAILED',
                'uuid' => null,
            ]);
        }

        return new JsonResponse([
            'status' => 'OK',
            'uuid' => $uuid,
        ]);
    }

    /**
     * @param array<string, array{uuid: string, dam_asset_hash: string}> $downloaded
     * @param array<string>                                              $preSelected
     *
     * @return array{downloaded: bool, uuid: string, selected: bool}
     */
    private function prepareImage(Asset $asset, array $downloaded, array $preSelected): array
    {
        $data = $asset->toArray();
        $data['selected'] = false;
        $data['downloaded'] = false;
        $data['uuid'] = null;

        if (isset($downloaded[$asset->identifier])) {
            $data['downloaded'] = $downloaded[$asset->identifier]['dam_asset_hash'] === $asset->hash;
            $data['uuid'] = $downloaded[$asset->identifier]['uuid'];
            $data['selected'] = \in_array($data['uuid'], $preSelected, true);
        }

        return $data;
    }

    /**
     * @return array<string, array{uuid: string, dam_asset_hash: string}>
     */
    private function fetchDownloaded(string $integration, AssetCollection $assets): array
    {
        $assetIds = [];

        foreach ($assets->all() as $asset) {
            $assetIds[] = $asset->identifier;
        }

        $stmt = $this->connection->executeQuery(
            'SELECT uuid,dam_asset_id,dam_asset_hash FROM tl_files WHERE dam_asset_integration=? AND dam_asset_id IN (?)',
            [$integration, $assetIds],
            [ParameterType::STRING, ArrayParameterType::STRING],
        );

        $downloaded = [];

        foreach ($stmt->fetchAllAssociative() as $row) {
            $downloaded[$row['dam_asset_id']] = [
                'uuid' => StringUtil::binToUuid($row['uuid']),
                'dam_asset_hash' => $row['dam_asset_hash'],
            ];
        }

        return $downloaded;
    }

    /**
     * @return array{totalImages: int, totalPages: int, perPage: int, currentPage: int, hasPrevious: bool, hasNext: bool, previous: int, next: int}
     */
    private function calculatePagination(int $page, int $limit, int $total): array
    {
        $totalPages = (int) ceil($total / $limit);
        $hasPrevious = $page > 1;
        $hasNext = $page < $totalPages;
        $previous = max(1, $page - 1);
        $next = $page + 1;

        if ($next > $totalPages) {
            $next = $totalPages;
        }

        return [
            'totalImages' => $total,
            'totalPages' => $totalPages,
            'perPage' => $limit,
            'currentPage' => $page,
            'hasPrevious' => $hasPrevious,
            'hasNext' => $hasNext,
            'previous' => $previous,
            'next' => $next,
        ];
    }

    private function getIntegration(string $integration): IntegrationInterface
    {
        if (!$this->integrationCollection->has($integration)) {
            throw new NotFoundHttpException('Integration "'.$integration.'" not found.');
        }

        return $this->integrationCollection->get($integration);
    }

    private function buildAssetFilter(Request $request, int $limit, int $page): AssetFilter
    {
        $filterCollection = new FilterCollection();
        $filters = json_decode($request->query->get('filters', '{}'), true);

        if (\is_array($filters) && \count($filters)) {
            foreach ($filters as $propertyName => $filterValue) {
                $filter = new Filter($propertyName, '');
                $filter->addOption(new FilterOption($filterValue, ''));
                $filterCollection->addFilter($filter);
            }
        }

        return new AssetFilter(
            PickerConfig::urlDecode($request->query->get('picker')),
            $request->query->getString('keyword'),
            $filterCollection,
            $page,
            $limit,
        );
    }
}
