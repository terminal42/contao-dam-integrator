<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\ContaoDamIntegrator\Integration\Celum\CelumIntegration;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;
use Terminal42\ContaoDamIntegrator\Picker\Celum\CelumPickerProvider;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(CelumPickerProvider::class)
        ->parent(AbstractPickerProvider::class)
        ->arg('$integration', service(CelumIntegration::class))
    ;
};
