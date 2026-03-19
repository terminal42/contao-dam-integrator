<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Controller;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\Environment;
use Contao\StringUtil;
use Contao\System;
use Knp\Menu\Renderer\RendererInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;
use Terminal42\ContaoDamIntegrator\IntegrationCollection;
use Twig\Environment as TwigEnvironment;

#[Route(defaults: ['_scope' => 'backend'])]
class PickerController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RendererInterface $menuRenderer,
        private readonly PickerBuilderInterface $pickerBuilder,
        private readonly Packages $packages,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        private readonly IntegrationCollection $integrationCollection,
        private readonly TwigEnvironment $twig,
        private readonly bool $isDebug,
    ) {
    }

    #[Route(path: '/_dam_asset_picker/{integration}', name: 'dam_asset_picker')]
    public function pickerAction(Request $request, string $integration): Response
    {
        if (!$request->query->has('picker')) {
            throw new BadRequestHttpException('DAM asset picker is supposed to be used within the Contao picker.');
        }

        $picker = $this->pickerBuilder->createFromData($request->query->get('picker'));

        if (null === $picker) {
            throw new BadRequestHttpException('DAM asset picker is supposed to have some data.');
        }

        if (!$this->integrationCollection->has($integration)) {
            throw new BadRequestHttpException('Integration "'.$integration.'" does not exist.');
        }

        $integration = $this->integrationCollection->get($integration);

        $this->framework->initialize();

        System::loadLanguageFile('default');

        $template = new BackendTemplate('be_main');

        $picker = $this->pickerBuilder->createFromData($request->query->get('picker'));

        if (null === $picker) {
            throw new NotFoundHttpException();
        }

        if ($picker->getMenu()->count() > 1) {
            $template->pickerMenu = $this->menuRenderer->render($picker->getMenu());
        }

        $renderMainOnly = $request->query->has('popup') || 'contao-main' === $request->headers->get('turbo-frame');

        $template->attributes = ' data-dam-asset-picker="'.$integration::getKey().'"';
        $template->main = $this->getInitHtml($picker, $integration);
        $template->title = StringUtil::specialchars($integration->getPickerLabel());
        $template->headline = StringUtil::specialchars($integration->getPickerLabel());
        $template->isPopup = true;
        $template->isDebug = $this->isDebug;
        $template->backendWidth = BackendUser::getInstance()->backendWidth;
        $template->host = Backend::getDecodedHostname();
        $template->renderMainOnly = $renderMainOnly;
        $template->theme = Backend::getTheme();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->charset = Config::get('characterSet');

        $GLOBALS['TL_JAVASCRIPT'][] = $this->packages->getUrl('app.js', 'terminal42_contao_dam_integrator');

        return $template->getResponse();
    }

    private function getInitHtml(PickerInterface $picker, IntegrationInterface $integration): string
    {
        $config = $picker->getConfig();

        return $this->twig->render('@Contao/backend/dam_picker.html.twig', [
            'js_config' => [
                'fieldType' => $config->getExtra('fieldType'),
                'pickerConfig' => $config->urlEncode(),
                'preSelected' => explode(',', $config->getValue()),
                'api' => [
                    'filters' => $this->router->generate('dam_integrator_api_filters', ['integration' => $integration::getKey()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'assets' => $this->router->generate('dam_integrator_api_assets', ['integration' => $integration::getKey()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'download' => $this->router->generate('dam_integrator_api_download', ['integration' => $integration::getKey()], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                'labels' => [
                    'reset' => $this->translator->trans('MSC.reset', [], 'contao_default'),
                    'apply' => $this->translator->trans('MSC.apply', [], 'contao_default'),
                    'filter' => $this->translator->trans('MSC.filter', [], 'contao_default'),
                    'toggleFilter' => $this->translator->trans('DCA.toggleFilter.0', [], 'contao_default'),
                    'toggleFilterShow' => $this->translator->trans('DCA.toggleFilter.1', [], 'contao_default'),
                    'toggleFilterHide' => $this->translator->trans('DCA.toggleFilter.2', [], 'contao_default'),
                    'field' => $this->translator->trans('MSC.field', [], 'contao_default'),
                    'search' => $this->translator->trans('MSC.search', [], 'contao_default'),
                    'keyword' => $this->translator->trans('MSC.keyword', [], 'contao_default'),
                    'keywords' => $this->translator->trans('MSC.keywords', [], 'contao_default'),
                    'loadingData' => $this->translator->trans('MSC.loadingData', [], 'contao_default'),
                    'noResult' => $this->translator->trans('MSC.noResult', [], 'contao_default'),
                    'showOnly' => $this->translator->trans('MSC.showOnly', [], 'contao_default'),
                    'download' => $this->translator->trans('MSC.download', [], 'contao_default'),
                    'downloadFailed' => $this->translator->trans('MSC.damDownloadFailed', [], 'contao_default'),
                    'pickerLabel' => $integration->getPickerLabel(),
                ],
            ],
        ]);
    }
}
