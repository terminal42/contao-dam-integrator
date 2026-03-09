<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Util\LocaleUtil;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Terminal42\ContaoDamIntegrator\Integration\Dto\AssetMetadata;
use Twig\Environment;

abstract class AbstractMetadataUpdater implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;

    public const CONTAINER_TAG_NAME = 'terminal42_contao_dam_integrator.metadata_updater';

    protected function logError(string $message, \Throwable|null $exception = null): void
    {
        $context = null === $exception ? [] : ['exception' => $exception];

        $this->logger()->error($message, $context);
    }

    /**
     * @param array<string, array<string, string>>  $metaConfig
     * @param \Closure(string):array<string, mixed> $fetchContextForLanguage
     */
    protected function setMetaOnEvent(AssetMetadata $assetMetadata, StoreDbafsMetadataEvent $event, array $metaConfig, \Closure $fetchContextForLanguage): void
    {
        if ([] === $metaConfig) {
            return;
        }

        $meta = [];
        $relevantLocales = $this->getRelevantLocales();
        $contextCache = [];

        foreach ($relevantLocales as $locale) {
            $languageConfig = $this->resolveMetaConfigForLocale($locale, $metaConfig);

            if ([] === $languageConfig) {
                continue;
            }

            $contextCache[$locale] ??= $fetchContextForLanguage($locale);

            foreach ($languageConfig as $field => $valueTemplate) {
                $template = $this->twig()->createTemplate($valueTemplate);

                $meta[$locale][$field] = $this->twig()->render(
                    $template,
                    $contextCache[$locale],
                );
            }
        }

        try {
            $event->set('meta', serialize($meta));
        } catch (\Throwable $e) {
            $this->logError(\sprintf('Could not automatically add the meta data for asset ID "%s". Reason: %s',
                $assetMetadata->identifier,
                $e->getMessage(),
            ), $e);
        }
    }

    /**
     * Resolution order:
     *  1. exact locale match, e.g. "de_CH"
     *  2. family match, e.g. "de+"
     *
     * Examples:
     *  - locale "de" => "de" first, then "de+"
     *  - locale "de_CH" => "de_CH" first, then "de+"
     */
    private function resolveMetaConfigForLocale(string $locale, array $metaConfig): array
    {
        if (isset($metaConfig[$locale]) && \is_array($metaConfig[$locale])) {
            return $metaConfig[$locale];
        }

        $primaryLanguage = LocaleUtil::getPrimaryLanguage($locale);
        $familyKey = $primaryLanguage.'+';

        if (isset($metaConfig[$familyKey]) && \is_array($metaConfig[$familyKey])) {
            return $metaConfig[$familyKey];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    private function getRelevantLocales(): array
    {
        $locales = $this->connection()->fetchFirstColumn("SELECT DISTINCT language FROM tl_page WHERE type='root' AND language!=''");

        return array_unique(array_merge($locales, array_map(LocaleUtil::getPrimaryLanguage(...), $locales)));
    }

    #[SubscribedService]
    private function logger(): LoggerInterface
    {
        return $this->container->get('logger');
    }

    #[SubscribedService]
    private function twig(): Environment
    {
        return $this->container->get('twig');
    }

    #[SubscribedService]
    private function connection(): Connection
    {
        return $this->container->get('database_connection');
    }
}
