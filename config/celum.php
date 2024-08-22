<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoDamIntegrator\Integration\Celum\CelumIntegration;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;
use Terminal42\ContaoDamIntegrator\Picker\Celum\CelumPickerProvider;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(CelumPickerProvider::class)
        ->parent(AbstractPickerProvider::class)
        ->arg('$integration', service(CelumIntegration::class))
    ;
};
