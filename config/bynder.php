<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoDamIntegrator\Integration\Bynder\BynderIntegration;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;
use Terminal42\ContaoDamIntegrator\Picker\Bynder\BynderPickerProvider;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(BynderPickerProvider::class)
        ->parent(AbstractPickerProvider::class)
        ->arg('$integration', service(BynderIntegration::class))
    ;
};
