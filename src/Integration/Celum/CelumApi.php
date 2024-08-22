<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Celum;

use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CelumApi
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<mixed>
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getAssetDetails(int $id, string|null $forceLocale = null): array
    {
        return $this->request('asset.do', ['details' => $id, 'details_versions' => 'true'], $forceLocale)->toArray()['data'] ?? [];
    }

    /**
     * We need to build the URL ourselves because Celum cannot handle array query parameters. So multiple "search_infofield"
     * parameters do not work when using the proper array ([]) notation in the query. Instead, "search_infofield" has
     * to be added to the URL multiple times which Symfony's HttpClient (or probably no HttpClient) does not support.
     * And even if the array notation were supported, it seems like Celum is hosted behind Apache Tomcat which denies
     * square brackets in query parameters as per some RFC although all browsers and Symfony's HttpClient behave differently
     * and do not encode square brackets by default.
     *
     * @param array<string, mixed> $query
     *
     * @throws TransportExceptionInterface
     */
    public function request(string $url, array $query = [], string|null $forceLocale = null): ResponseInterface&StreamableInterface
    {
        if (null !== $forceLocale) {
            $query = array_merge($query, ['locale' => $forceLocale]);
        } else {
            $request = $this->requestStack->getCurrentRequest();

            if (null !== $request) {
                $query = array_merge($query, ['locale' => LocaleUtil::getPrimaryLanguage($request->getLocale())]);
            }
        }

        if ([] !== $query) {
            $url .= '?';
            $queryArray = [];

            foreach ($query as $k => $v) {
                if (\is_array($v)) {
                    foreach ($v as $vv) {
                        $queryArray[] = $k.'='.urlencode((string) $vv);
                    }
                    continue;
                }
                $queryArray[] = urlencode($k).'='.urlencode((string) $v);
            }

            $url .= implode('&', $queryArray);
        }

        $response = $this->httpClient->request('GET', $url);

        if ($response instanceof StreamableInterface) {
            /** @var StreamableInterface&ResponseInterface $response */
            return $response;
        }

        throw new \RuntimeException('Expected a streamable response');
    }
}
