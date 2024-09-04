<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Picker;

use Contao\BackendUser;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\Validator;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;

abstract class AbstractPickerProvider implements PickerProviderInterface
{
    public function __construct(
        private readonly FactoryInterface $menuFactory,
        private readonly RouterInterface $router,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly Packages $packages,
    ) {
    }

    /**
     * Returns the unique name for this picker.
     */
    public function getName(): string
    {
        return $this->getIntegration()::getKey().'DamAssetPicker';
    }

    /**
     * Returns the URL to the picker based on the current value.
     */
    public function getUrl(PickerConfig $config): string
    {
        return $this->generateUrl($config);
    }

    /**
     * Creates the menu item for this picker.
     */
    public function createMenuItem(PickerConfig $config): ItemInterface
    {
        $GLOBALS['TL_CSS'][] = $this->packages->getUrl('app.css', 'terminal42_contao_dam_integrator');

        $name = $this->getName();

        return $this->menuFactory->createItem($name, [
            'label' => $this->getIntegration()->getPickerLabel(),
            'linkAttributes' => ['class' => $name],
            'current' => $this->isCurrent($config),
            'uri' => $this->generateUrl($config),
            'display' => $this->getIntegration()->supportsPicker($config),
        ]);
    }

    /**
     * Returns whether the picker supports the given context.
     */
    public function supportsContext(string $context): bool
    {
        return $this->getIntegration()->supportsPicker(new PickerConfig($context));
    }

    /**
     * Returns whether the picker supports the given value.
     */
    public function supportsValue(PickerConfig $config): bool
    {
        if ('file' === $config->getContext()) {
            return Validator::isUuid($config->getValue());
        }

        return false;
    }

    /**
     * Returns whether the picker is currently active.
     */
    public function isCurrent(PickerConfig $config): bool
    {
        return $config->getCurrent() === $this->getName();
    }

    abstract protected function getIntegration(): IntegrationInterface;

    /**
     * Generates the URL for the picker.
     */
    private function generateUrl(PickerConfig $config): string
    {
        $params = array_merge(
            [
                'integration' => $this->getIntegration()::getKey(),
                'popup' => '1',
                'picker' => $config->cloneForCurrent($this->getName())->urlEncode(),
            ],
        );

        return $this->router->generate('dam_asset_picker', $params);
    }

    /**
     * Returns the back end user object.
     *
     * @throws \RuntimeException
     */
    private function getUser(): BackendUser
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('No token provided');
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            throw new \RuntimeException('The token does not contain a back end user object');
        }

        return $user;
    }
}
