<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Picker\Celum;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\ContaoDamIntegrator\Integration\Celum\CelumIntegration;
use Terminal42\ContaoDamIntegrator\Picker\AbstractPickerProvider;

class CelumPickerProvider extends AbstractPickerProvider
{
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        Packages $packages,
        private readonly CelumIntegration $integration,
    ) {
        parent::__construct($menuFactory, $router, $packages);
    }

    protected function getIntegration(): CelumIntegration
    {
        return $this->integration;
    }
}
