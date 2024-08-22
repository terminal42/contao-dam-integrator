<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Messenger\MessageBusInterface;
use Terminal42\ContaoDamIntegrator\Cron\UpdateMetadataCron;
use Terminal42\ContaoDamIntegrator\Integration\AbstractMetadataUpdater;
use Terminal42\ContaoDamIntegrator\Integration\Bynder\BynderIntegration;
use Terminal42\ContaoDamIntegrator\Integration\Bynder\BynderMetadataUpdater;
use Terminal42\ContaoDamIntegrator\Integration\Celum\CelumApi;
use Terminal42\ContaoDamIntegrator\Integration\Celum\CelumIntegration;
use Terminal42\ContaoDamIntegrator\Integration\Celum\CelumMetadataUpdater;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;

class Terminal42ContaoDamIntegratorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.php');
        $loader->load('migrations.php');

        $container->registerForAutoconfiguration(IntegrationInterface::class)
            ->addTag(IntegrationInterface::CONTAINER_TAG_NAME)
        ;

        $container->registerForAutoconfiguration(AbstractMetadataUpdater::class)
            ->addTag(AbstractMetadataUpdater::CONTAINER_TAG_NAME)
        ;

        $this->registerBynder($config['bynder'], $container, $loader);
        $this->registerCelum($config['celum'], $container, $loader);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerBynder(array $config, ContainerBuilder $container, PhpFileLoader $loader): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('bynder.php');

        $client = $this->buildHttpClient([
            'base_uri' => 'https://'.$config['domain'].'/api/v4/',
            'auth_bearer' => $config['token'],
        ]);

        if ([] !== $config['metadata']['mapper']) {
            $container->setDefinition(BynderMetadataUpdater::class,
                (new Definition(BynderMetadataUpdater::class))
                    ->setArgument('$httpClient', $client)
                    ->setArgument('$metaConfig', $config['metadata']['mapper'])
                    ->setAutoconfigured(true),
            );
        }

        $container->setDefinition(BynderIntegration::class,
            (new Definition(BynderIntegration::class))
                ->setArgument('$httpClient', $client)
                ->setArgument('$derivativeName', $config['derivative_name'])
                ->setArgument('$targetDir', $config['target_dir'])
                ->setArgument('$metadataUpdater', new Reference(BynderMetadataUpdater::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
                ->setAutoconfigured(true),
        );

        $this->handleCommonMetadataConfiguration($config['metadata'], $container, 'bynder');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerCelum(array $config, ContainerBuilder $container, PhpFileLoader $loader): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('celum.php');

        $client = $this->buildHttpClient([
            'base_uri' => $config['base_uri'],
            'auth_bearer' => $config['token'],
        ]);

        $thumbnailUrlTemplate = $config['base_uri'].'asset.do?token='.$config['token'].'&thumb=';

        $container->setDefinition(CelumApi::class,
            (new Definition(CelumApi::class))
                ->setArgument('$httpClient', $client)
                ->setArgument('$requestStack', new Reference('request_stack')),
        );

        if ([] !== $config['metadata']['mapper']) {
            $container->setDefinition(CelumMetadataUpdater::class,
                (new Definition(CelumMetadataUpdater::class))
                    ->setArgument('$celumApi', new Reference(CelumApi::class))
                    ->setArgument('$metaConfig', $config['metadata']['mapper'])
                    ->setAutoconfigured(true),
            );
        }

        $container->setDefinition(CelumIntegration::class,
            (new Definition(CelumIntegration::class))
                ->setArgument('$celumApi', new Reference(CelumApi::class))
                ->setArgument('$downloadFormatId', $config['download_format_id'])
                ->setArgument('$thumbnailUrlTemplate', $thumbnailUrlTemplate)
                ->setArgument('$targetDir', $config['target_dir'])
                ->setArgument('$metadataUpdater', new Reference(CelumMetadataUpdater::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
                ->setAutoconfigured(true),
        );

        $this->handleCommonMetadataConfiguration($config['metadata'], $container, 'celum');
    }

    /**
     * @param array<string, mixed> $options
     */
    private function buildHttpClient(array $options, bool $retryable = true): Definition
    {
        $client = (new Definition(HttpClient::class))
            ->setFactory([HttpClient::class, 'create'])
            ->setArgument(0, $options)
        ;

        if ($retryable) {
            $client = (new Definition(RetryableHttpClient::class))
                ->setArgument(0, $client)
            ;
        }

        return $client;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function handleCommonMetadataConfiguration(array $config, ContainerBuilder $container, string $integration): void
    {
        // no mapper, no cronjobs
        if ([] === $config['mapper']) {
            return;
        }

        // Mapper defined but cronjob not enabled
        if (!$config['cronjob']['enabled']) {
            return;
        }

        $container->setDefinition('terminal42_contao_dam_integrator.update_metadata_cron.'.$integration,
            (new Definition(UpdateMetadataCron::class))
                ->setArgument('$integration', $integration)
                ->setArgument('$connection', new Reference('database_connection'))
                ->setArgument('$messageBus', new Reference(MessageBusInterface::class))
                ->addTag('contao.cronjob', [
                    'interval' => $config['cronjob']['expression'],
                ]),
        );
    }
}
