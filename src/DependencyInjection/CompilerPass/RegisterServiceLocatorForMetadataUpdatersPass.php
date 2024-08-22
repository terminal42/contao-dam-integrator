<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\ContaoDamIntegrator\Integration\AbstractMetadataUpdater;

class RegisterServiceLocatorForMetadataUpdatersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $locateableServices = [
            'logger' => new Reference('logger'),
            'twig' => new Reference('twig'),
        ];

        foreach (array_keys($container->findTaggedServiceIds(AbstractMetadataUpdater::CONTAINER_TAG_NAME)) as $serviceId) {
            $definition = $container->getDefinition($serviceId);

            if (!is_a($definition->getClass(), AbstractMetadataUpdater::class, true)) {
                continue;
            }

            $definition->addMethodCall('setContainer', [ServiceLocatorTagPass::register($container, $locateableServices)]);
        }
    }
}
