<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\Mbo\Controller\Admin;

use Exception;
use PrestaShop\PrestaShop\Adapter\Module\AdminModuleDataProvider;
use PrestaShop\PrestaShop\Core\Addon\AddonListFilter;
use PrestaShop\PrestaShop\Core\Addon\AddonListFilterStatus;
use PrestaShop\PrestaShop\Core\Addon\AddonListFilterType;
use PrestaShop\PrestaShop\Core\Addon\AddonsCollection;
use PrestaShopBundle\Controller\Admin\Improve\ModuleController as ModuleControllerCore;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Voter\PageVoter;
use PrestaShopBundle\Service\DataProvider\Admin\CategoriesProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleController extends ModuleControllerCore
{
    /**
     * @AdminSecurity("is_granted(['read'], 'ADMINMODULESSF_')")
     *
     * @return Response
     */
    public function catalogAction()
    {
        $moduleUri = __PS_BASE_URI__ . 'modules/ps_mbo/';

        $extraParams = [
            'me_url' => $this->generateUrl('admin_mbo_security'),
            'cdc_error_templating_url' => $moduleUri . 'views/js/cdc-error-templating.js',
            'cdc_error_templating_css' => $moduleUri . 'views/css/cdc-error-templating.css',
        ];

        $cdcJsFile = getenv('MBO_CDC_URL');
        if (false === $cdcJsFile || !is_string($cdcJsFile) || empty($cdcJsFile)) {
            $extraParams['cdc_script_not_found'] = true;
            $extraParams['cdc_error_url'] = $moduleUri . 'views/js/cdc-error.js';
        } else {
            $extraParams['cdc_url'] = $cdcJsFile;
        }

        /*********************
         * PrestaShop Account *
         * *******************/
        $urlAccountsCdn = '';

        try {
            $accountsFacade = $this->get('mbo.ps_accounts.facade');
            $accountsService = $accountsFacade->getPsAccountsService();
            if ($this->ensurePsAccountIsEnabled()) {
                $this->ensurePsEventbusEnabled();
            }
        } catch (Exception $e) {
            try {
                $accountsInstaller = $this->get('mbo.ps_accounts.installer');
                $accountsInstaller->install();
                $accountsFacade = $this->get('mbo.ps_accounts.facade');
                $accountsService = $accountsFacade->getPsAccountsService();
            } catch (Exception $e) {
                $accountsFacade = $accountsService = null;
            }
        }

        if (null !== $accountsFacade && null !== $accountsService) {
            try {
                \Media::addJsDef([
                    'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()
                        ->present('ps_mbo'),
                ]);

                // Retrieve the PrestaShop Account CDN
                $urlAccountsCdn = $accountsService->getAccountsCdn();
            } catch (Exception $e) {
                //osef ?
            }
        }

        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog.html.twig',
            [
                'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
                'layoutTitle' => $this->trans('Marketplace', 'Admin.Navigation.Menu'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
                'shop_context' => $this->get('mbo.cdc.context_builder')->getViewContext(),
                'urlAccountsCdn' => $urlAccountsCdn,
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    'Admin.Notifications.Error'
                ),
            ] + $extraParams
        );
    }

    /**
     * Controller responsible for displaying "Catalog Module Grid" section of Module management pages with ajax.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function refreshCatalogAction(Request $request)
    {
        $deniedAccess = $this->checkPermissions(
            [
                PageVoter::LEVEL_READ,
                PageVoter::LEVEL_CREATE,
                PageVoter::LEVEL_DELETE,
                PageVoter::LEVEL_UPDATE,
            ]
        );
        if (null !== $deniedAccess) {
            return $deniedAccess;
        }

        /**
         * @var AdminModuleDataProvider $modulesProvider
         */
        $modulesProvider = $this->get('prestashop.core.admin.data_provider.module_interface');
        $moduleRepository = $this->get('prestashop.core.admin.module.repository');
        $responseArray = [];

        $filters = new AddonListFilter();
        $filters->setType(AddonListFilterType::MODULE | AddonListFilterType::SERVICE)
            ->setStatus(~AddonListFilterStatus::INSTALLED);

        try {
            $modulesFromRepository = AddonsCollection::createFrom($moduleRepository->getFilteredList($filters));
            $modulesProvider->generateAddonsUrls($modulesFromRepository);

            $modules = $modulesFromRepository->toArray();
            shuffle($modules);
            $categories = $this->getCategories($modulesProvider, $modules);

            $responseArray['domElements'][] = $this->constructJsonCatalogCategoriesMenuResponse($categories);
            $responseArray['domElements'][] = $this->constructJsonCatalogBodyResponse(
                $categories,
                $modules
            );
            $responseArray['status'] = true;
        } catch (Exception $e) {
            $responseArray['msg'] = $this->trans(
                'Cannot get catalog data, please try again later. Reason: %error_details%',
                'Admin.Modules.Notification',
                ['%error_details%' => print_r($e->getMessage(), true)]
            );
            $responseArray['status'] = false;
        }

        return new JsonResponse($responseArray);
    }

    /**
     * Responsible for displaying error block when CDC cannot be loaded.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cdcErrorAction()
    {
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/cdc-error.html.twig'
        );
    }

    /**
     * @AdminSecurity("is_granted(['read'], 'ADMINMODULESSF_')")
     *
     * @param Request $request
     */
    public function uninstalledAction(Request $request)
    {
        $modulesProvider = $this->get('prestashop.core.admin.data_provider.module_interface');
        $moduleRepository = $this->get('prestashop.core.admin.module.repository');

        $filters = new AddonListFilter();
        $filters->setType(AddonListFilterType::MODULE | AddonListFilterType::SERVICE)
            ->setStatus(AddonListFilterStatus::UNINSTALLED);
        $installedProducts = $moduleRepository->getFilteredList($filters);

        $collection = AddonsCollection::createFrom($installedProducts);
        $modulesProvider->generateAddonsUrls($collection);
        $modules = $this->get('prestashop.adapter.presenter.module')
            ->presentCollection($installedProducts);

        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/uninstalled-modules.html.twig',
            [
                'maxModulesDisplayed' => self::MAX_MODULES_DISPLAYED,
                'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
                'layoutTitle' => $this->trans('Module manager', 'Admin.Modules.Feature'),
                'modules' => $modules,
                'requireAddonsSearch' => false,
                'requireBulkActions' => false,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => true,
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
                'errorMessage' => $this->trans('You do not have permission to add this.', 'Admin.Notifications.Error'),
            ]
        );
    }

    /**
     * Construct Json struct for catalog body response.
     *
     * @param array $categories
     * @param array $modules
     *
     * @return array
     */
    private function constructJsonCatalogBodyResponse(
        array $categories,
        array $modules
    ) {
        $formattedContent = [];
        $formattedContent['selector'] = '.module-catalog-page-result';
        $formattedContent['content'] = $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/Includes/sorting.html.twig',
            [
                'totalModules' => count($modules),
            ]
        )->getContent();

        $errorMessage = $this->trans('You do not have permission to add this.', 'Admin.Notifications.Error');

        $psVersion = explode('.', _PS_VERSION_);
        $version = sprintf('%d.%d.%d', (int) $psVersion[0], (int) $psVersion[1], (int) $psVersion[2]);
        $locale = $this->getContext()->language->iso_code;

        $formattedContent['content'] .= $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog_grid.html.twig',
            [
                'categories' => $categories['categories'],
                'requireAddonsSearch' => true,
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
                'errorMessage' => $errorMessage,
                'psVersion' => $version,
                'locale' => $locale,
            ]
        )->getContent();

        return $formattedContent;
    }

    /**
     * Construct json struct from top menu.
     *
     * @param array $categories
     *
     * @return array
     */
    private function constructJsonCatalogCategoriesMenuResponse(array $categories)
    {
        $formattedContent = [];
        $formattedContent['selector'] = '.module-menu-item';
        $formattedContent['content'] = $this->render(
            '@PrestaShop/Admin/Module/Includes/dropdown_categories_catalog.html.twig',
            [
                'topMenuData' => $this->getTopMenuData($categories),
            ]
        )->getContent();

        return $formattedContent;
    }

    /**
     * Check user permission.
     *
     * @param array $pageVoter
     *
     * @return JsonResponse|null
     */
    private function checkPermissions(array $pageVoter)
    {
        if (!in_array(
            $this->authorizationLevel(self::CONTROLLER_NAME),
            $pageVoter
        )) {
            return new JsonResponse(
                [
                    'status' => false,
                    'msg' => $this->trans('You do not have permission to add this.', 'Admin.Notifications.Error'),
                ]
            );
        }

        return null;
    }

    /**
     * Get categories and its modules.
     *
     * @param array $modules List of installed modules
     *
     * @return array
     */
    private function getCategories(AdminModuleDataProvider $modulesProvider, array $modules)
    {
        /** @var CategoriesProvider $categoriesProvider */
        $categoriesProvider = $this->get('prestashop.categories_provider');
        $categories = $categoriesProvider->getCategoriesMenu($modules);

        foreach ($categories['categories']->subMenu as $category) {
            $collection = AddonsCollection::createFrom($category->modules);
            $modulesProvider->generateAddonsUrls($collection);
            $category->modules = $this->get('prestashop.adapter.presenter.module')
                ->presentCollection($category->modules);
        }

        return $categories;
    }

    private function getTopMenuData(array $topMenuData, $activeMenu = null)
    {
        if (isset($activeMenu)) {
            if (!isset($topMenuData[$activeMenu])) {
                throw new Exception(sprintf('Menu \'%s\' not found in Top Menu data', $activeMenu), 1);
            }

            $topMenuData[$activeMenu]->class = 'active';
        }

        return $topMenuData;
    }

    /**
     * @return bool
     */
    private function ensurePsAccountIsEnabled()
    {
        $accountsInstaller = $this->get('mbo.ps_accounts.installer');
        // @phpstan-ignore booleanNot.alwaysFalse
        if (!$accountsInstaller) {
            return false;
        }

        $accountsEnabled = $accountsInstaller->isModuleEnabled();
        if ($accountsEnabled) {
            return true;
        }

        $moduleManager = $this->get('prestashop.module.manager');

        return $moduleManager->enable($accountsInstaller->getModuleName());
    }

    /**
     * @return void
     */
    private function ensurePsEventbusEnabled()
    {
        $installer = $this->get('mbo.ps_eventbus.installer');
        if ($installer->install()) {
            $installer->enable();
        }
    }
}
