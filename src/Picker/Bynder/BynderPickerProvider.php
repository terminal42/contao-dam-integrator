<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Picker\Bynder;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Terminal42\ContaoDamIntegrator\Integration\Bynder\BynderIntegration;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;

class BynderPickerProvider extends AbstractPickerProvider
{
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        TokenStorageInterface $tokenStorage,
        Packages $packages,
        private readonly BynderIntegration $integration,
    ) {
        parent::__construct($menuFactory, $router, $tokenStorage, $packages);
    }

    protected function getIntegration(): BynderIntegration
    {
        return $this->integration;
    }
}
