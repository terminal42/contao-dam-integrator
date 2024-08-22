<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\ContaoDamIntegrator\Integration\AbstractIntegration;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;

class RegisterServiceLocatorForIntegrationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $locateableServices = [
            'logger' => new Reference('logger'),
            'security' => new Reference('security.helper'),
            'virtualFilesystem' => new Reference('contao.filesystem.virtual.files'),
        ];

        foreach (array_keys($container->findTaggedServiceIds(IntegrationInterface::CONTAINER_TAG_NAME)) as $serviceId) {
            $definition = $container->getDefinition($serviceId);

            if (!is_a($definition->getClass(), AbstractIntegration::class, true)) {
                continue;
            }

            $definition->addMethodCall('setContainer', [ServiceLocatorTagPass::register($container, $locateableServices)]);
        }
    }
}
