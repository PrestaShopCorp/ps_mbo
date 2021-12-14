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
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Controller\Admin;

use Exception;
use PrestaShop\Module\Mbo\Modules\Collection;
use PrestaShop\Module\Mbo\Modules\Filters;
use PrestaShop\Module\Mbo\Modules\Repository;
use PrestaShopBundle\Controller\Admin\Improve\Modules\ModuleAbstractController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Voter\PageVoter;
use PrestaShopBundle\Service\DataProvider\Admin\CategoriesProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends ModuleAbstractController
{
    public const CONTROLLER_NAME = 'ADMINMODULESSF';

    /**
     * Module Catalog page
     *
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="You do not have permission to update this.",
     *     redirectRoute="admin_administration"
     * )
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
                'level' => $this->authorizationLevel(static::CONTROLLER_NAME),
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
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="You do not have permission to update this.",
     *     redirectRoute="admin_administration"
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function refreshAction(Request $request): JsonResponse
    {
        /** @var Repository $moduleRepository */
        $moduleRepository = $this->get('mbo.modules.repository');

        $filters = $this->get('mbo.modules.filters.factory')->create();
        $filters
            ->setType(Filters\Type::MODULE | Filters\Type::SERVICE)
            ->setStatus(Filters\Status::ALL & ~Filters\Status::INSTALLED);

        $responseArray = [];
        try {
            $modules = $this->get('mbo.modules.collection.factory')->build(
                $moduleRepository->fetchAll(),
                $filters
            );
            $categories = $this->getCategories($modules);

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
     * @param Collection $modules List of installed modules
     *
     * @return array
     */
    private function getCategories(Collection $modules): array
    {
        /** @var CategoriesProvider $categoriesProvider */
        $categoriesProvider = $this->get('prestashop.categories_provider');
        $categories = $categoriesProvider->getCategoriesMenu($modules);

        foreach ($categories['categories']->subMenu as $category) {
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
     * @param Collection $modules
     *
     * @return array
     */
    private function constructJsonCatalogBodyResponse(array $categories, Collection $modules): array
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
                'level' => $this->authorizationLevel(static::CONTROLLER_NAME),
                'errorMessage' => $this->trans('You do not have permission to add this.', 'Admin.Notifications.Error'),
            ]
        )->getContent();

        return [
            'selector' => '.module-catalog-page',
            'content' => $sortingHeaderContent . $gridContent,
        ];
    }

    /**
     * Common method for all module related controller for getting the header buttons.
     *
     * @return array
     */
    protected function getToolbarButtons()
    {
        // toolbarButtons
        $toolbarButtons = [];

        if (!in_array(
            $this->authorizationLevel(static::CONTROLLER_NAME),
            [
                PageVoter::LEVEL_READ,
                PageVoter::LEVEL_UPDATE,
            ]
        )) {
            $toolbarButtons['add_module'] = [
                'href' => '#',
                'desc' => $this->trans('Upload a module', 'Admin.Modules.Feature'),
                'icon' => 'cloud_upload',
                'help' => $this->trans('Upload a module', 'Admin.Modules.Feature'),
            ];
        }

        return $toolbarButtons;
    }
}
