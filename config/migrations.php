<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Terminal42\ContaoDamIntegrator\Migration\UpgradeFromContaoBynderDbafsMigration;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(UpgradeFromContaoBynderDbafsMigration::class)
        ->args([
            service('database_connection'),
        ])
    ;
};
