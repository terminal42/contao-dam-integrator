<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Terminal42\ContaoDamIntegrator\IntegrationCollection;

#[AsCallback('tl_user_group', 'fields.dam_enable.options')]
class UsergroupPermissionOptionsListener
{
    public function __construct(private readonly IntegrationCollection $integrationCollection)
    {
    }

    /**
     * @return array<string>
     */
    public function __invoke(): array
    {
        $options = [];

        foreach ($this->integrationCollection->all() as $key => $integration) {
            $options[$key] = $integration->getPickerLabel();
        }

        natcasesort($options);

        return $options;
    }
}
