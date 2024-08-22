<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terminal42\ContaoDamIntegrator\DependencyInjection\CompilerPass\RegisterServiceLocatorForIntegrationsPass;
use Terminal42\ContaoDamIntegrator\DependencyInjection\CompilerPass\RegisterServiceLocatorForMetadataUpdatersPass;

class Terminal42ContaoDamIntegratorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterServiceLocatorForIntegrationsPass());
        $container->addCompilerPass(new RegisterServiceLocatorForMetadataUpdatersPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
