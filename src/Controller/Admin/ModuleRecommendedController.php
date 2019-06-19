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
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleRecommendedController extends FrameworkBundleAdminController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request)
    {
        $tabCollectionProvider = $this->getTabCollectionProvider();
        $tab = $tabCollectionProvider->getTab($request->get('tabClassName'));
        $recommendedModulePresenter = new RecommendedModulePresenter();
        $recommendedModulesInstalled = $tab->getRecommendedModulesInstalled();
        $recommendedModulesNotInstalled = $tab->getRecommendedModulesNotInstalled();
        $response = $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/recommended-modules.html.twig',
            [
                'recommendedModulesInstalled' => $recommendedModulePresenter->presentCollection($recommendedModulesInstalled),
                'recommendedModulesNotInstalled' => $recommendedModulePresenter->presentCollection($recommendedModulesNotInstalled),
            ]
        );

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'content' => $response->getContent(),
                'status' => true,
            ]);
        }

        return $response;
    }

    /**
     * @return TabCollectionProvider
     */
    private function getTabCollectionProvider()
    {
        return $this->get('mbo.tab.collection_provider');
    }
}
