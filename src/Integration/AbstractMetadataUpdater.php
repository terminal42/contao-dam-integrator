<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration;

use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
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

        foreach ($metaConfig as $language => $languageConfig) {
            foreach ($languageConfig as $field => $valueTemplate) {
                $template = $this->twig()->createTemplate($valueTemplate);
                $meta[$language][$field] = $this->twig()->render(
                    $template,
                    $fetchContextForLanguage($language),
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

    #[SubscribedService]
    private function logger(): LoggerInterface
    {
        return $this->container->get(__FUNCTION__);
    }

    #[SubscribedService]
    private function twig(): Environment
    {
        return $this->container->get(__FUNCTION__);
    }
}
