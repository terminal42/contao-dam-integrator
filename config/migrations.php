<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoDamIntegrator\Migration\UpgradeFromContaoBynderDbafsMigration;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(UpgradeFromContaoBynderDbafsMigration::class)
        ->args([
            service('database_connection'),
        ])
    ;
};
