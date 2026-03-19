<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\ContaoDamIntegrator\Integration\Bynder\BynderIntegration;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;
use Terminal42\ContaoDamIntegrator\Picker\Bynder\BynderPickerProvider;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(BynderPickerProvider::class)
        ->parent(AbstractPickerProvider::class)
        ->arg('$integration', service(BynderIntegration::class))
    ;
};
