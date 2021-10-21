<?php
/**
 * 2007-2021 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Controller\Admin;

use Exception;
use PrestaShop\Module\Mbo\Addons\AddonsCollection;
use PrestaShop\Module\Mbo\Addons\ListFilter;
use PrestaShop\Module\Mbo\Addons\ListFilterStatus;
use PrestaShop\Module\Mbo\Addons\ListFilterType;
use PrestaShop\Module\Mbo\Addons\Module\AdminModuleDataProvider;
use PrestaShop\Module\Mbo\Addons\Module\ModuleRepository;
use PrestaShopBundle\Controller\Admin\Improve\Modules\ModuleAbstractController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Service\DataProvider\Admin\CategoriesProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends ModuleAbstractController
{
    /**
     * Module Catalog page
     *
     * @AdminSecurity("is_granted(['read', 'create', 'update', 'delete'], request.get('_legacy_controller'))")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog.html.twig',
            [
                'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
                'layoutTitle' => $this->trans('Modules catalog', 'Admin.Navigation.Menu'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    'Admin.Notifications.Error'
                ),
            ]
        );
    }

    /**
     * Controller responsible for displaying "Catalog Module Grid" section of Module management pages with ajax.
     *
     * @AdminSecurity("is_granted(['read', 'create', 'update', 'delete'], request.get('_legacy_controller'))")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function refreshAction(Request $request): JsonResponse
    {
        /** @var $modulesProvider AdminModuleDataProvider */
        $modulesProvider = $this->get('mbo.addon.module.data_provider.admin_module');
        /** @var $moduleRepository ModuleRepository */
        $moduleRepository = $this->get('mbo.addon.module.repository');
        $responseArray = [];

        $filters = new ListFilter();
        $filters->setType(ListFilterType::MODULE | ListFilterType::SERVICE)
            ->setStatus(~ListFilterStatus::INSTALLED);

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
     * Get categories and its modules.
     *
     * @param AdminModuleDataProvider $modulesProvider
     * @param array $modules List of installed modules
     *
     * @return array
     */
    private function getCategories(AdminModuleDataProvider $modulesProvider, array $modules): array
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

    /**
     * Build template for the categories dropdown on the header of page.
     *
     * @param array $categories
     *
     * @return array
     */
    private function constructJsonCatalogCategoriesMenuResponse(array $categories): array
    {
        return [
            'selector' => '.module-menu-item',
            'content' => $this->render(
                '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/includes/dropdown_categories_catalog.html.twig',
                [
                    'topMenuData' => $categories,
                ]
            )->getContent(),
        ];
    }

    /**
     * Build templade for the grid view and the info header with count of modules & sort dropdown.
     *
     * @param array $categories
     * @param array $modules
     *
     * @return array
     */
    private function constructJsonCatalogBodyResponse(array $categories, array $modules): array
    {
        $sortingHeaderContent = $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/includes/sorting.html.twig',
            [
                'totalModules' => count($modules),
            ]
        )->getContent();

        $gridContent = $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog-refresh.html.twig',
            [
                'categories' => $categories['categories'],
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
                'errorMessage' => $this->trans('You do not have permission to add this.', 'Admin.Notifications.Error'),
            ]
        )->getContent();

        return [
            'selector' => '.module-catalog-page',
            'content' => $sortingHeaderContent . $gridContent,
        ];
    }
}
