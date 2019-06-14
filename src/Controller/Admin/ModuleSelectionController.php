<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Controller\Admin;

use PrestaShop\Module\Mbo\Adapter\RecommendedModulePresenter;
use PrestaShop\Module\Mbo\Tab\TabCollectionProvider;
use PrestaShop\Module\Mbo\Tab\TabInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleSelectionController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $pageContent = @file_get_contents($this->getAddonsUrl($request));

        $template = !$pageContent
            ? '@Modules/ps_mbo/views/templates/admin/error.html.twig'
            : '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/addons_store.html.twig'
        ;

        return $this->render(
            $template,
            [
                'pageContent' => $pageContent,
                'layoutHeaderToolbarBtn' => [],
                'layoutTitle' => $this->trans('Module selection', 'Admin.Navigation.Menu'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel($request->attributes->get('_legacy_controller')),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function recommendedModulesAction(Request $request)
    {
        $tabCollectionProvider = $this->getTabCollectionProvider();
        $tab = $tabCollectionProvider->getTab($request->get('tabClassName'));

        return new JsonResponse([
            'content' => $this->buildJsonRecommendedModulesBodyResponse($tab),
            'status' => true,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function getAddonsUrl(Request $request)
    {
        $psVersion = $this->get('prestashop.core.foundation.version')->getVersion();
        $parent_domain = $request->getSchemeAndHttpHost();
        $context = $this->getContext();
        $currencyCode = $context->currency->iso_code;
        $languageCode = $context->language->iso_code;
        $countryCode = $context->country->iso_code;
        $activity = $this->get('prestashop.adapter.legacy.configuration')->getInt('PS_SHOP_ACTIVITY');

        return "https://addons.prestashop.com/iframe/search-1.7.php?psVersion=$psVersion"
            . "&isoLang=$languageCode"
            . "&isoCurrency=$currencyCode"
            . "&isoCountry=$countryCode"
            . "&activity=$activity"
            . "&parentUrl=$parent_domain"
        ;
    }

    /**
     * @return TabCollectionProvider
     */
    private function getTabCollectionProvider()
    {
        return $this->get('mbo.tab.collection_provider');
    }

    /**
     * @param TabInterface|false $tabRecommendedModules
     *
     * @return string
     */
    private function buildJsonRecommendedModulesBodyResponse($tabRecommendedModules)
    {
        $recommendedModulesInstalledPresented = null;
        $recommendedModulesNotInstalledPresented = null;

        if ($tabRecommendedModules) {
            $recommendedModulePresenter = new RecommendedModulePresenter();
            $recommendedModulesInstalled = $tabRecommendedModules->getRecommendedModulesInstalled();
            $recommendedModulesInstalledPresented = $recommendedModulePresenter->presentCollection($recommendedModulesInstalled);
            $recommendedModulesNotInstalled = $tabRecommendedModules->getRecommendedModulesNotInstalled();
            $recommendedModulesNotInstalledPresented = $recommendedModulePresenter->presentCollection($recommendedModulesNotInstalled);
        }

        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/recommended-modules.html.twig',
            [
                'recommendedModulesInstalled' => $recommendedModulesInstalledPresented,
                'recommendedModulesNotInstalled' => $recommendedModulesNotInstalledPresented,
            ]
        )->getContent();
    }
}
