<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoDamIntegrator\AssetHandler;
use Terminal42\ContaoDamIntegrator\Command\UpdateMetadataCommand;
use Terminal42\ContaoDamIntegrator\Controller\ApiController;
use Terminal42\ContaoDamIntegrator\Controller\PickerController;
use Terminal42\ContaoDamIntegrator\EventListener\DbafsMetadataEventListener;
use Terminal42\ContaoDamIntegrator\EventListener\FilesCopyButtonListener;
use Terminal42\ContaoDamIntegrator\EventListener\UsergroupPermissionOptionsListener;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;
use Terminal42\ContaoDamIntegrator\IntegrationCollection;
use Terminal42\ContaoDamIntegrator\Messenger\MessageHandler\UpdateMetadataMessageHandler;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(IntegrationCollection::class)
        ->args([
            tagged_iterator(IntegrationInterface::CONTAINER_TAG_NAME, null, 'getKey'),
        ])
    ;

    $services->set(AssetHandler::class)
        ->args([
            service(IntegrationCollection::class),
            service('contao.filesystem.virtual.files'),
            service('logger'),
        ])
    ;

    $services->set(AbstractPickerProvider::class)
        ->abstract()
        ->args([
            service('knp_menu.factory'),
            service('router'),
            service('security.token_storage'),
            service('assets.packages'),
        ])
        ->autoconfigure(false)
    ;

    $services->set(PickerController::class)
        ->args([
            service('contao.framework'),
            service('contao.menu.renderer'),
            service('contao.picker.builder'),
            service('assets.packages'),
            service('translator'),
            service('router'),
            service(IntegrationCollection::class),
        ])
        ->public()
    ;

    $services->set(ApiController::class)
        ->public()
        ->args([
            service('database_connection'),
            service('contao.framework'),
            service(AssetHandler::class),
            service(IntegrationCollection::class),
        ])
        ->public()
    ;

    $services->set(FilesCopyButtonListener::class);
    $services->set(DbafsMetadataEventListener::class)
        ->args([
            service(IntegrationCollection::class),
        ])
    ;

    $services->set(UpdateMetadataCommand::class)
        ->args([
            service(AssetHandler::class),
        ])
    ;

    $services->set(UsergroupPermissionOptionsListener::class)
        ->args([
            service(IntegrationCollection::class),
        ])
    ;

    $services->set(UpdateMetadataMessageHandler::class)
        ->args([
            service(AssetHandler::class),
        ])
    ;
};
